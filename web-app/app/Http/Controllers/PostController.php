<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use App\Models\ActivityLog;
use App\Models\GroupMembership;
use App\Models\Notification;
use App\Models\PostReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Save a new post (reply) under a topic
    public function store(Request $request, $topicId)
    {
        $validated = $request->validate([
            'content'        => ['required_without:attachment', 'nullable', 'string'],
            'parent_post_id' => ['nullable', 'exists:posts,post_id'],
            'attachment'     => ['nullable', 'file', 'max:10240'],
            'excluded_users'   => ['nullable', 'array'],
            'excluded_users.*' => ['exists:users,user_id'],
        ]);

        $attachmentData = $this->storeAttachment($request);

        $post = Post::create([
            'topic_id'       => $topicId,
            'author_id'      => Auth::id(),
            'parent_post_id' => $validated['parent_post_id'] ?? null,
            'content'        => $validated['content'] ?? '',
            ...$attachmentData,
        ]);

        // Never let a poster accidentally exclude themselves
        $excludedIds = collect($validated['excluded_users'] ?? [])
            ->reject(fn ($id) => (int) $id === Auth::id())
            ->values();

        if ($excludedIds->isNotEmpty()) {
            $post->excludedUsers()->attach($excludedIds);
        }

        $topic = Topic::find($topicId);

        // Notify whoever should hear about this reply: the post it's
        // directly replying to, or otherwise the person who started the
        // topic. Never notify someone about their own reply.
        if ($validated['parent_post_id'] ?? null) {
            $parentPost = Post::find($validated['parent_post_id']);
            if ($parentPost && $parentPost->author_id !== Auth::id()) {
                Notification::notify($parentPost->author_id, 'reply', $post->post_id, (int) $topicId);
            }
        } elseif ($topic && $topic->creator_id !== Auth::id()) {
            Notification::notify($topic->creator_id, 'reply', $post->post_id, (int) $topicId);
        }

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'group_id'    => $topic?->group_id,
            'action_type' => 'reply',
            'meta'        => ['topic_id' => (int) $topicId],
            'logged_at'   => now(),
        ]);
        Auth::user()->update(['last_active_at' => now()]);

        return redirect()->route('topics.show', $topicId)
            ->withFragment('post-' . $post->post_id)
            ->with('success', 'Reply posted!');
    }

    // Report a post to the system admin — only someone who shares the
    // post's group can report it, matching the same scoping used
    // throughout the group-admin tools
    public function flag(Request $request, Post $post)
    {
        $post->load('topic');
        $groupId = $post->topic->group_id ?? null;

        $isMember = $groupId && GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $groupId)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            abort(403);
        }

        if ($post->author_id === Auth::id()) {
            return back()->with('error', 'You cannot report your own post.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $alreadyReported = PostReport::where('post_id', $post->post_id)
            ->where('reported_by', Auth::id())
            ->exists();

        if ($alreadyReported) {
            return back()->with('error', 'You have already reported this post.');
        }

        PostReport::create([
            'post_id'     => $post->post_id,
            'reported_by' => Auth::id(),
            'reason'      => $validated['reason'],
            'reviewed'    => false,
        ]);

        $post->update(['is_flagged' => true]);

        return back()->with('success', 'Post reported. A system admin will review it.');
    }

    public function edit(Post $post)
    {
        // Only the author or a system admin can edit
        if (
            auth()->id() !== $post->author_id &&
            auth()->user()->system_role !== 'system_admin'
        ) {
            abort(403);
        }

        return view('posts.edit', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        // Only the author or a system admin can update
        if (
            auth()->id() !== $post->author_id &&
            auth()->user()->system_role !== 'system_admin'
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'content'        => ['required', 'string'],
            'parent_post_id' => ['nullable', 'exists:posts,post_id'],
            'attachment'     => ['nullable', 'file', 'max:10240'],
        ]);

        $attachmentData = $this->storeAttachment($request, $post);

        $post->update([
            'content' => $validated['content'],
            ...$attachmentData,
        ]);

        return redirect()
            ->route('topics.show', $post->topic_id)
            ->with('success', 'Reply updated successfully.');
    }

    // Store an uploaded attachment (if any) on the public disk and return
    // the fields to save on the post. Replacing an attachment on update()
    // deletes the old file so orphaned uploads don't pile up.
    private function storeAttachment(Request $request, ?Post $existingPost = null): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        if ($existingPost && $existingPost->attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($existingPost->attachment);
        }

        $file = $request->file('attachment');
        $path = $file->store('attachments', 'public');

        return [
            'attachment'      => $path,
            'attachment_type' => $file->getMimeType(),
            'attachment_name' => $file->getClientOriginalName(),
        ];
    }

    // Delete a post (only the author or an admin)
    public function destroy(Post $post)
    {
        if (Auth::id() !== $post->author_id && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $post->delete();

        return back()->with('success', 'Post deleted.');
    }

    public function markSolution(Post $post)
    {
        $topic = $post->topic;
        if (Auth::id() !== $topic->creator_id && !Auth::user()->isAdmin()) {
            abort(403);
        }
        return back()->with('success', 'Post marked as solution.');
    }
}
