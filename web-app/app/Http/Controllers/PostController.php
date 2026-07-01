<?php

namespace App\Http\Controllers;

use App\Models\Post;
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
}
