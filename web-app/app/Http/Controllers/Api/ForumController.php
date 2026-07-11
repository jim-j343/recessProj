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
        ]);

        $post = Post::create([
            'topic_id'       => $topic->topic_id,
            'author_id'      => $request->user()->user_id,
            'parent_post_id' => $data['parent_post_id'] ?? null,
            'content'        => $data['content'],
            'is_synced'      => true,
        ]);

        $request->user()->forceFill(['last_active_at' => now()])->save();

        $post->load('author');

        return response()->json($this->postShape($post), 201);
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

    private function postShape(Post $p): array
    {
        return [
            'post_id'        => (int) $p->post_id,
            'topic_id'       => (int) $p->topic_id,
            'author_id'      => (int) $p->author_id,
            'parent_post_id' => $p->parent_post_id ? (int) $p->parent_post_id : null,
            'content'        => $p->content,
            'created_at'     => optional($p->created_at)->toIso8601String(),
            'author'         => $p->author->username ?? null,
        ];
    }
}
