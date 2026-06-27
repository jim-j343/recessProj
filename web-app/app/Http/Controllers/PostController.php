<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    // Store a new reply to a topic
    public function store(Request $request, Topic $topic)
    {
        $request->validate([
            'content' => ['required', 'string'],
        ]);

        // Prevent replies on locked topics
        if ($topic->is_locked) {
            return back()->with('error', 'This topic is locked and cannot receive replies.');
        }

        Post::create([
            'topic_id'    => $topic->id,
            'user_id'     => Auth::id(),
            'body'        => $request->content,
            'is_solution' => false,
        ]);

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Reply posted successfully!');
    }

    // Mark a post as the accepted solution
    public function markSolution(Post $post)
    {
        $topic = $post->topic;

        // Only the topic owner or admin can mark a solution
        if (Auth::id() !== $topic->user_id && !Auth::user()->isAdmin()) {
            abort(403);
        }

        // Unmark any existing solution first
        $topic->posts()->update(['is_solution' => false]);

        // Mark this post as solution
        $post->update(['is_solution' => true]);

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Solution marked!');
    }

    // Delete a post
    public function destroy(Post $post)
    {
        $topic = $post->topic;

        if (Auth::id() !== $post->user_id && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $post->delete();

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Reply deleted.');
    }
}