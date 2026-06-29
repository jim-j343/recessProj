<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopicController extends Controller
{
    // Show all topics
    public function index()
    {
        $topics = Topic::with('user')
            ->withCount('posts')
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->get();

        return view('forum.index', compact('topics'));
    }

    // Show form to create a new topic
    public function create()
    {
        return view('forum.create');
    }

    // Save new topic to database
    public function store(Request $request)
    {
        $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        Topic::create([
            'user_id'  => Auth::id(),
            'title'    => $request->title,
            'body'     => $request->body,
            'category' => $request->category,
        ]);

        return redirect()->route('forum.index')
            ->with('success', 'Topic created successfully!');
    }

    // Show a single topic and its posts
    public function show(Topic $topic)
    {
        // Increment view count
        $topic->increment('views');

        $posts = $topic->posts()->with('user')->get();

        return view('forum.show', compact('topic', 'posts'));
    }

    // Save a reply (post) to a topic
    public function reply(Request $request, Topic $topic)
    {
        $request->validate([
            'body' => ['required', 'string'],
        ]);

        // Prevent replies on locked topics
        if ($topic->is_locked) {
            return back()->with('error', 'This topic is locked.');
        }

        Post::create([
            'topic_id' => $topic->id,
            'user_id'  => Auth::id(),
            'body'     => $request->body,
        ]);

        return redirect()->route('topics.show', $topic)
            ->with('success', 'Reply posted!');
    }

    // Delete a topic (only owner or admin)
    public function destroy(Topic $topic)
    {
        if (Auth::id() !== $topic->user_id && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $topic->delete();

        return redirect()->route('topics.index')
            ->with('success', 'Topic deleted.');
    }
}
