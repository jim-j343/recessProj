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
        $topics = Topic::with('creator')
            ->withCount('posts')
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
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'content'  => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'group_id' => ['required', 'exists:groups,group_id'],
        ]);

        $topic = Topic::create([
            'group_id'   => $validated['group_id'],
            'creator_id' => Auth::id(),
            'title'      => $validated['title'],
            'category'   => $validated['category'] ?? null,
        ]);

        // The first post IS the topic content — stored in the posts table
        Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => Auth::id(),
            'content'   => $validated['content'],
        ]);

        return redirect()->route('topics.show', $topic->topic_id)
            ->with('success', 'Topic created successfully!');
    }

    // Show a single topic and its posts
    public function show(Topic $topic)
    {
        $posts = $topic->posts()
            ->with('author')
            ->orderBy('created_at')
            ->get();

        return view('forum.show', compact('topic', 'posts'));
    }

    // Save a reply (post) to a topic
    public function reply(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'content'         => ['required', 'string'],
            'parent_post_id'  => ['nullable', 'exists:posts,post_id'],
        ]);

        Post::create([
            'topic_id'        => $topic->topic_id,
            'author_id'       => Auth::id(),
            'parent_post_id'  => $validated['parent_post_id'] ?? null,
            'content'         => $validated['content'],
        ]);

        return redirect()->route('topics.show', $topic->topic_id)
            ->with('success', 'Reply posted!');
    }

    // Delete a topic (only owner or admin)
    public function destroy(Topic $topic)
    {
        if (Auth::id() !== $topic->creator_id && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $topic->delete();

        return redirect()->route('forum.index')
            ->with('success', 'Topic deleted.');
    }
}
