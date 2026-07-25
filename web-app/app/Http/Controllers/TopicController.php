<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Post;
use App\Models\ActivityLog;
use App\Models\GroupMembership;
use App\Models\UserEngagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TopicController extends Controller
{
    // Show all topics
    public function index(Request $request)
    {
        // Get the groups the current user belongs to
        $userGroupIds = \App\Models\GroupMembership::where('user_id', auth()->id())
            ->where('status', 'active')
            ->pluck('group_id');

        $search = $request->input('search');

        // Show only topics from the user's groups
        $topics = Topic::with('creator')
            ->withCount('posts')
            ->whereIn('group_id', $userGroupIds)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Live search fetches just the list and swaps it in, so the page
        // doesn't reload on every keystroke. Same query, same results.
        if ($request->ajax()) {
            return view('forum._topic-list', compact('topics', 'search'));
        }

        return view('forum.index', compact('topics', 'search'));

    }

    // Show form to create a new topic
    public function create()
    {
        $groups = \App\Models\GroupMembership::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with('group')
            ->get()
            ->pluck('group');

        return view('forum.create', compact('groups'));
    }

    // Save new topic
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'group_id' => ['required', 'exists:groups,group_id'],
        ]);

        // Only auto-classify if the user didn't pick a category themselves —
        // a manual choice always takes priority over the ML prediction.
        // Falls back to 'General' if the ML service is unreachable or slow,
        // so topic creation never breaks because Flask isn't running.
        $category = $validated['category'] ?? null;
        if (!$category) {
            try {
                $response = Http::timeout(3)->post('http://localhost:5001/classify', [
                    'text' => $validated['title'] . ' ' . $validated['content'],
                ]);
                $category = $response->successful() ? $response->json('category') : 'General';
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $category = 'General';
            }
        }

        $topic = Topic::create([
            'group_id' => $validated['group_id'],
            'creator_id' => Auth::id(),
            'title' => $validated['title'],
            'category' => $category,
        ]);

        Post::create([
            'topic_id' => $topic->topic_id,
            'author_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'group_id'    => $validated['group_id'],
            'action_type' => 'post',
            'meta'        => ['topic_id' => $topic->topic_id],
            'logged_at'   => now(),
        ]);
        Auth::user()->update(['last_active_at' => now()]);

        // Feed the recommender: creating a topic is the strongest engagement signal
        UserEngagement::create([
            'user_id'          => Auth::id(),
            'topic_id'         => $topic->topic_id,
            'engagement_type'  => 'post',
            'engaged_at'       => now(),
        ]);

        return redirect()->route('topics.show', $topic->topic_id)
            ->with('success', 'Topic created successfully!');
    }

    // Show one topic
    public function show(Topic $topic)
    {
        $allPosts = $topic->posts()
            ->with(['author', 'excludedUsers'])
            ->orderBy('created_at')
            ->get();

        // A post excludes specific people from seeing it. The author and a
        // system admin always see everything they wrote/oversee — everyone
        // else loses visibility of a post they're specifically excluded from.
        if (!Auth::user()->isAdmin()) {
            $allPosts = $allPosts->reject(function ($post) {
                return $post->author_id !== Auth::id()
                    && $post->excludedUsers->contains('user_id', Auth::id());
            })->values();
        }

        // The topic's opening post (created alongside the topic itself) is
        // shown separately as the "original post" card — everything else
        // is a reply. parent_post_id exists in the schema for true threaded
        // replies, but no UI sets it yet, so the opening post is identified
        // as whichever post came first instead.
        $firstPost = $allPosts->whereNull('parent_post_id')->first() ?? $allPosts->first();

        $posts = $firstPost
            ? $allPosts->reject(fn ($post) => $post->post_id === $firstPost->post_id)->values()
            : $allPosts;

        // Other members of this topic's group, for the "exclude from this
        // reply" picker in the composer
        $groupMembers = GroupMembership::where('group_id', $topic->group_id)
            ->where('status', 'active')
            ->where('user_id', '!=', Auth::id())
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        // Feed the recommender: viewing a topic is the lightest engagement signal
        UserEngagement::create([
            'user_id'          => Auth::id(),
            'topic_id'         => $topic->topic_id,
            'engagement_type'  => 'view',
            'engaged_at'       => now(),
        ]);

        return view('forum.show', compact(
            'topic',
            'posts',
            'firstPost',
            'groupMembers'
        ));
    }

    // ===========================
    // EDIT TOPIC
    // ===========================
    public function edit(Topic $topic)
    {
        if (
            auth()->id() !== $topic->creator_id &&
            auth()->user()->system_role !== 'system_admin'
        ) {
            abort(403);
        }

        $groups = \App\Models\GroupMembership::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with('group')
            ->get()
            ->pluck('group');

        $firstPost = $topic->posts()
            ->whereNull('parent_post_id')
            ->first();

        return view('forum.edit', compact(
            'topic',
            'groups',
            'firstPost'
        ));
    }

    // ===========================
    // UPDATE TOPIC
    // ===========================
    public function update(Request $request, Topic $topic)
    {
        if (
            auth()->id() !== $topic->creator_id &&
            auth()->user()->system_role !== 'system_admin'
        ) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:80',
            'group_id' => 'required|exists:groups,group_id',
            'content' => 'required|string',
        ]);

        // A blank category on edit shouldn't silently wipe what's there.
        // Re-classify the same way store() does; if the ML service is
        // unreachable, keep whatever the topic already had.
        $category = $validated['category'] ?: null;
        if (!$category) {
            try {
                $response = Http::timeout(3)->post('http://localhost:5001/classify', [
                    'text' => $validated['title'] . ' ' . $validated['content'],
                ]);
                $category = $response->successful()
                    ? $response->json('category')
                    : $topic->category;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $category = $topic->category;
            }
        }

        $topic->update([
            'title' => $validated['title'],
            'category' => $category,
            'group_id' => $validated['group_id'],
        ]);

        $firstPost = $topic->posts()
            ->whereNull('parent_post_id')
            ->first();

        if ($firstPost) {
            $firstPost->update([
                'content' => $validated['content'],
            ]);
        }

        return redirect()
            ->route('topics.show', $topic)
            ->with('success', 'Topic updated successfully!');
    }

    // Save reply
    public function reply(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'parent_post_id' => ['nullable', 'exists:posts,post_id'],
        ]);

        Post::create([
            'topic_id' => $topic->topic_id,
            'author_id' => Auth::id(),
            'parent_post_id' => $validated['parent_post_id'] ?? null,
            'content' => $validated['content'],
        ]);

        ActivityLog::create([
            'user_id'     => Auth::id(),
            'group_id'    => $topic->group_id,
            'action_type' => 'reply',
            'meta'        => ['topic_id' => $topic->topic_id],
            'logged_at'   => now(),
        ]);
        Auth::user()->update(['last_active_at' => now()]);


        return redirect()->route('topics.show', $topic->topic_id)
            ->with('success', 'Reply posted!');
    }

    // Delete topic
    public function destroy(Topic $topic)
    {
        if (
            Auth::id() !== $topic->creator_id &&
            auth()->user()->system_role !== 'system_admin'
        ) {
            abort(403);
        }

        $topic->delete();

        return redirect()
            ->route('forum.index')
            ->with('success', 'Topic deleted.');
    }

    // Export topic as a downloadable PDF
    public function exportPdf(Topic $topic)
    {
        $posts = $topic->posts()
            ->with(['author', 'excludedUsers'])
            ->orderBy('created_at')
            ->get();

        // Same exclusion rule as the live thread — exporting to PDF must
        // not become a way around being excluded from a post
        if (!Auth::user()->isAdmin()) {
            $posts = $posts->reject(function ($post) {
                return $post->author_id !== Auth::id()
                    && $post->excludedUsers->contains('user_id', Auth::id());
            })->values();
        }

        $firstPost = $posts->whereNull('parent_post_id')->first() ?? $posts->first();

        $replies = $firstPost
            ? $posts->reject(fn ($post) => $post->post_id === $firstPost->post_id)->values()
            : $posts;

        $pdf = Pdf::loadView('exports.topic-pdf', compact('topic', 'firstPost', 'replies'));

        // Feed the recommender: exporting signals strong interest in a topic
        UserEngagement::create([
            'user_id'          => Auth::id(),
            'topic_id'         => $topic->topic_id,
            'engagement_type'  => 'export',
            'engaged_at'       => now(),
        ]);

        return $pdf->download(Str::slug($topic->title) . '.pdf');
    }
}
