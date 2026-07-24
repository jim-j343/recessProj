<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Token-protected forum endpoints for the desktop client.
 * Returns flat JSON shapes that map 1:1 to the JavaFX DTOs.
 */
class ForumController extends Controller
{
    /** GET /api/topics — topics from the caller's active groups. */
    public function index(Request $request): JsonResponse
    {
        $groupIds = GroupMembership::where('user_id', $request->user()->user_id)
            ->where('status', 'active')
            ->pluck('group_id');

        $topics = Topic::with('creator')
            ->withCount('posts')
            ->whereIn('group_id', $groupIds)
            ->latest()
            ->get()
            ->map(fn (Topic $t) => $this->topicShape($t));

        return response()->json($topics);
    }

    /** POST /api/topics — create a topic; its body becomes the first post. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'content'  => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:80'],
            'group_id' => ['nullable', 'integer'],
        ]);

        // Resolve a valid group: use the one sent if it exists, else the user's
        // first active group, else any group as a last resort.
        $groupId = $data['group_id'] ?? null;
        if (! $groupId || ! Group::where('group_id', $groupId)->exists()) {
            $groupId = GroupMembership::where('user_id', $request->user()->user_id)
                    ->where('status', 'active')
                    ->value('group_id')
                ?? Group::query()->value('group_id');
        }
        if (! $groupId) {
            return response()->json([
                'message' => 'No group is available. Create or join a group before posting a topic.',
            ], 422);
        }

        $topic = Topic::create([
            'group_id'   => $groupId,
            'creator_id' => $request->user()->user_id,
            'title'      => $data['title'],
            'category'   => $data['category'] ?? null,
        ]);

        Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $request->user()->user_id,
            'content'   => $data['content'],
            'is_synced' => true,
        ]);

        $request->user()->forceFill(['last_active_at' => now()])->save();

        $topic->loadCount('posts')->load('creator');

        return response()->json($this->topicShape($topic), 201);
    }

<<<<<<< HEAD
    /** GET /api/topics/{topic} — the topic, its posts, and the group roster
     *  (for the "exclude from this reply" picker). */
    public function show(Request $request, Topic $topic): JsonResponse
    {
        $topic->loadCount('posts')->load('creator');
        $viewerId = $request->user()->user_id;

        $allPosts = $topic->posts()
            ->with(['author', 'excludedUsers'])
            ->orderBy('created_at')
            ->orderBy('post_id')
            ->get();

        // A post excludes specific people from seeing it. The author and a
        // system admin always see everything — everyone else loses
        // visibility of a post they're specifically excluded from. Mirrors
        // TopicController::show() on web.
        if (! $request->user()->isAdmin()) {
            $allPosts = $allPosts->reject(function (Post $p) use ($viewerId) {
                return $p->author_id !== $viewerId
                    && $p->excludedUsers->contains('user_id', $viewerId);
            })->values();
        }

        $posts = $allPosts->map(fn (Post $p) => $this->postShape($p, $viewerId));

        // Other members of this topic's group, for the exclude picker —
        // same query as web's $groupMembers in TopicController::show().
        $groupMembers = GroupMembership::where('group_id', $topic->group_id)
            ->where('status', 'active')
            ->where('user_id', '!=', $viewerId)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->map(fn ($u) => ['user_id' => (int) $u->user_id, 'username' => $u->username])
            ->values();

        return response()->json([
            'topic'         => $this->topicShape($topic),
            'posts'         => $posts,
            'group_members' => $groupMembers,
        ]);
    }

    /** POST /api/topics/{topic}/posts — add a reply, optionally hidden from specific members. */
    public function storePost(Request $request, Topic $topic): JsonResponse
    {
        $data = $request->validate([
            'content'          => ['required', 'string'],
            'parent_post_id'   => ['nullable', 'integer', 'exists:posts,post_id'],
            'excluded_users'   => ['nullable', 'array'],
            'excluded_users.*' => ['integer', 'exists:users,user_id'],
=======
    /** GET /api/topics/{topic} — the topic plus its posts. */
    public function show(Topic $topic): JsonResponse
    {
        $topic->loadCount('posts')->load('creator');

        $posts = $topic->posts()
            ->with('author')
            ->orderBy('created_at')
            ->orderBy('post_id')
            ->get()
            ->map(fn (Post $p) => $this->postShape($p));

        return response()->json([
            'topic' => $this->topicShape($topic),
            'posts' => $posts,
        ]);
    }

    /** POST /api/topics/{topic}/posts — add a reply. */
    public function storePost(Request $request, Topic $topic): JsonResponse
    {
        $data = $request->validate([
            'content'        => ['required', 'string'],
            'parent_post_id' => ['nullable', 'integer', 'exists:posts,post_id'],
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
        ]);

        $post = Post::create([
            'topic_id'       => $topic->topic_id,
            'author_id'      => $request->user()->user_id,
            'parent_post_id' => $data['parent_post_id'] ?? null,
            'content'        => $data['content'],
            'is_synced'      => true,
        ]);

<<<<<<< HEAD
        // Never let a poster accidentally exclude themselves — mirrors
        // PostController::store() on web.
        $excludedIds = collect($data['excluded_users'] ?? [])
            ->reject(fn ($id) => (int) $id === $request->user()->user_id)
            ->values();

        if ($excludedIds->isNotEmpty()) {
            $post->excludedUsers()->attach($excludedIds);
        }

        $request->user()->forceFill(['last_active_at' => now()])->save();

        $post->load(['author', 'excludedUsers']);

        return response()->json($this->postShape($post, $request->user()->user_id), 201);
    }

    /** PUT /api/topics/{topic} — edit title/category/group + the opening post's content.
     *  Mirrors TopicController::update() on web. */
    public function update(Request $request, Topic $topic): JsonResponse
    {
        if ($request->user()->user_id !== $topic->creator_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You cannot edit this topic.'], 403);
        }

        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'group_id' => ['required', 'exists:groups,group_id'],
            'content'  => ['required', 'string'],
        ]);

        $topic->update([
            'title'    => $data['title'],
            'category' => $data['category'] ?? null,
            'group_id' => $data['group_id'],
        ]);

        $firstPost = $topic->posts()->whereNull('parent_post_id')->first();
        if ($firstPost) {
            $firstPost->update(['content' => $data['content']]);
        }

        $topic->loadCount('posts')->load('creator');
        return response()->json($this->topicShape($topic));
    }

    /** DELETE /api/topics/{topic} — mirrors TopicController::destroy() on web. */
    public function destroy(Request $request, Topic $topic): JsonResponse
    {
        if ($request->user()->user_id !== $topic->creator_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You cannot delete this topic.'], 403);
        }

        $topic->delete();
        return response()->json(['message' => 'Topic deleted.']);
    }

    /** POST /api/posts/{post}/flag — report a post to a system admin.
     *  Mirrors PostController::flag() on web. */
    public function flagPost(Request $request, Post $post): JsonResponse
    {
        $post->load('topic');
        $groupId = $post->topic->group_id ?? null;
        $viewerId = $request->user()->user_id;

        $isMember = $groupId && GroupMembership::where('user_id', $viewerId)
            ->where('group_id', $groupId)
            ->where('status', 'active')
            ->exists();

        if (! $isMember) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }
        if ($post->author_id === $viewerId) {
            return response()->json(['message' => 'You cannot report your own post.'], 422);
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $alreadyReported = \App\Models\PostReport::where('post_id', $post->post_id)
            ->where('reported_by', $viewerId)
            ->exists();
        if ($alreadyReported) {
            return response()->json(['message' => 'You have already reported this post.'], 422);
        }

        \App\Models\PostReport::create([
            'post_id'     => $post->post_id,
            'reported_by' => $viewerId,
            'reason'      => $data['reason'],
            'reviewed'    => false,
        ]);
        $post->update(['is_flagged' => true]);

        return response()->json(['message' => 'Post reported. A system admin will review it.']);
    }

    /** PUT /api/posts/{post} — edit a reply's content (text only — attachment
     *  editing isn't supported from the desktop client). Mirrors
     *  PostController::update() on web, minus the file-upload branch. */
    public function updatePost(Request $request, Post $post): JsonResponse
    {
        $viewerId = $request->user()->user_id;
        if ($viewerId !== $post->author_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You cannot edit this post.'], 403);
        }

        $data = $request->validate(['content' => ['required', 'string']]);
        $post->update(['content' => $data['content']]);
        $post->load(['author', 'excludedUsers']);

        return response()->json($this->postShape($post, $viewerId));
    }

    /** DELETE /api/posts/{post} — mirrors PostController::destroy() on web. */
    public function destroyPost(Request $request, Post $post): JsonResponse
    {
        if ($request->user()->user_id !== $post->author_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You cannot delete this post.'], 403);
        }

        $post->delete();
        return response()->json(['message' => 'Post deleted.']);
=======
        $request->user()->forceFill(['last_active_at' => now()])->save();

        $post->load('author');

        return response()->json($this->postShape($post), 201);
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
    }

    private function topicShape(Topic $t): array
    {
        return [
            'topic_id'   => (int) $t->topic_id,
            'group_id'   => (int) $t->group_id,
            'creator_id' => (int) $t->creator_id,
            'title'      => $t->title,
            'category'   => $t->category,
            'created_at' => optional($t->created_at)->toIso8601String(),
            'author'     => $t->creator->username ?? null,
            'replies'    => (int) ($t->posts_count ?? 0),
        ];
    }

<<<<<<< HEAD
    private function postShape(Post $p, ?int $viewerId = null): array
    {
        $shape = [
=======
    private function postShape(Post $p): array
    {
        return [
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
            'post_id'        => (int) $p->post_id,
            'topic_id'       => (int) $p->topic_id,
            'author_id'      => (int) $p->author_id,
            'parent_post_id' => $p->parent_post_id ? (int) $p->parent_post_id : null,
            'content'        => $p->content,
<<<<<<< HEAD
            'is_flagged'     => (bool) $p->is_flagged,
            'created_at'     => optional($p->created_at)->toIso8601String(),
            'author'         => $p->author->username ?? null,
        ];

        // Only the post's own author sees who it's hidden from — mirrors
        // the @if($isOwn && ...) badge in forum/show.blade.php.
        if ($viewerId !== null && $viewerId === (int) $p->author_id && $p->relationLoaded('excludedUsers')) {
            $excludedNames = $p->excludedUsers->pluck('username')->values();
            if ($excludedNames->isNotEmpty()) {
                $shape['excluded_usernames'] = $excludedNames;
            }
        }

        return $shape;
    }
}
=======
            'created_at'     => optional($p->created_at)->toIso8601String(),
            'author'         => $p->author->username ?? null,
        ];
    }
}
>>>>>>> c0a0fe073da5b40940d7bd0bb2ce0c10d655d5ed
