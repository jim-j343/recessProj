<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use App\Models\ActivityLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Save a new post (reply) under a topic
    public function store(Request $request, $topicId)
    {
        $validated = $request->validate([
            'content'        => ['required', 'string'],
            'parent_post_id' => ['nullable', 'exists:posts,post_id'],
        ]);

        $post = Post::create([
            'topic_id'       => $topicId,
            'author_id'      => Auth::id(),
            'parent_post_id' => $validated['parent_post_id'] ?? null,
            'content'        => $validated['content'],
        ]);

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
            ->with('success', 'Reply posted!');
    }

    // Flag a post as irrelevant (requirement 1)
    public function flag(Post $post)
    {
        $post->update(['is_flagged' => true]);

        return back()->with('success', 'Post flagged for review.');
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
