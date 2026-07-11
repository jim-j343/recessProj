<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use App\Models\ActivityLog;
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

    $post->update([
        'content' => $validated['content'],
    ]);

    return redirect()
        ->route('topics.show', $post->topic_id)
        ->with('success', 'Reply updated successfully.');
}
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
