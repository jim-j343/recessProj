<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostExclusionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'student'): User
    {
        return User::create([
            'username'      => 'user_'.uniqid(),
            'email'         => uniqid().'@test.com',
            'password_hash' => bcrypt('password'),
            'system_role'   => $role,
            'status'        => 'active',
        ]);
    }

    private function joinGroup(User $user, Group $group): void
    {
        GroupMembership::create([
            'user_id'   => $user->user_id,
            'group_id'  => $group->group_id,
            'role'      => 'member',
            'status'    => 'active',
            'joined_at' => now(),
        ]);
    }

    /** @return array{group: Group, topic: Topic, author: User, excluded: User, other: User, admin: User} */
    private function makeScenario(): array
    {
        $admin = $this->makeUser('system_admin');
        $author = $this->makeUser();
        $excluded = $this->makeUser();
        $other = $this->makeUser();

        $group = Group::create(['admin_id' => $admin->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        foreach ([$author, $excluded, $other] as $u) {
            $this->joinGroup($u, $group);
        }

        $topic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $author->user_id,
            'title'      => 'Test Topic',
            'category'   => null,
        ]);

        // opening post
        Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $author->user_id,
            'content'   => 'Opening post',
            'is_synced' => true,
        ]);

        return compact('group', 'topic', 'author', 'excluded', 'other', 'admin');
    }

    public function test_excluded_member_cannot_see_the_post(): void
    {
        $s = $this->makeScenario();

        $reply = Post::create([
            'topic_id'  => $s['topic']->topic_id,
            'author_id' => $s['author']->user_id,
            'content'   => 'Secret reply',
            'is_synced' => true,
        ]);
        $reply->excludedUsers()->attach($s['excluded']->user_id);

        Sanctum::actingAs($s['excluded']);

        $response = $this->getJson('/api/topics/'.$s['topic']->topic_id);

        $response->assertOk();
        $contents = collect($response->json('posts'))->pluck('content');
        $this->assertFalse($contents->contains('Secret reply'));
    }

    public function test_author_sees_their_own_excluded_post_with_excluded_usernames(): void
    {
        $s = $this->makeScenario();

        $reply = Post::create([
            'topic_id'  => $s['topic']->topic_id,
            'author_id' => $s['author']->user_id,
            'content'   => 'Secret reply',
            'is_synced' => true,
        ]);
        $reply->excludedUsers()->attach($s['excluded']->user_id);

        Sanctum::actingAs($s['author']);

        $response = $this->getJson('/api/topics/'.$s['topic']->topic_id);

        $response->assertOk();
        $posts = collect($response->json('posts'));
        $secret = $posts->firstWhere('content', 'Secret reply');

        $this->assertNotNull($secret);
        $this->assertEquals([$s['excluded']->username], $secret['excluded_usernames']);
    }

    public function test_admin_sees_excluded_post_regardless(): void
    {
        $s = $this->makeScenario();

        $reply = Post::create([
            'topic_id'  => $s['topic']->topic_id,
            'author_id' => $s['author']->user_id,
            'content'   => 'Secret reply',
            'is_synced' => true,
        ]);
        $reply->excludedUsers()->attach($s['excluded']->user_id);

        Sanctum::actingAs($s['admin']);

        $response = $this->getJson('/api/topics/'.$s['topic']->topic_id);

        $response->assertOk();
        $contents = collect($response->json('posts'))->pluck('content');
        $this->assertTrue($contents->contains('Secret reply'));
    }

    public function test_non_excluded_member_sees_post_without_excluded_usernames_field(): void
    {
        $s = $this->makeScenario();

        $reply = Post::create([
            'topic_id'  => $s['topic']->topic_id,
            'author_id' => $s['author']->user_id,
            'content'   => 'Secret reply',
            'is_synced' => true,
        ]);
        $reply->excludedUsers()->attach($s['excluded']->user_id);

        Sanctum::actingAs($s['other']);

        $response = $this->getJson('/api/topics/'.$s['topic']->topic_id);

        $response->assertOk();
        $posts = collect($response->json('posts'));
        $secret = $posts->firstWhere('content', 'Secret reply');

        // visible (not the excluded viewer), but no exclusion info leaked to a non-author
        $this->assertNotNull($secret);
        $this->assertArrayNotHasKey('excluded_usernames', $secret);
    }

    public function test_group_members_roster_excludes_the_viewer_themselves(): void
    {
        $s = $this->makeScenario();

        Sanctum::actingAs($s['author']);

        $response = $this->getJson('/api/topics/'.$s['topic']->topic_id);

        $response->assertOk();
        $roster = collect($response->json('group_members'))->pluck('user_id');

        $this->assertFalse($roster->contains($s['author']->user_id));
        $this->assertTrue($roster->contains($s['excluded']->user_id));
        $this->assertTrue($roster->contains($s['other']->user_id));
    }

    public function test_store_post_with_excluded_users_attaches_exclusions(): void
    {
        $s = $this->makeScenario();

        Sanctum::actingAs($s['author']);

        $response = $this->postJson('/api/topics/'.$s['topic']->topic_id.'/posts', [
            'content'        => 'A reply with an exclusion',
            'excluded_users' => [$s['excluded']->user_id],
        ]);

        $response->assertCreated();
        $postId = $response->json('post_id');

        $this->assertDatabaseHas('post_exclusions', [
            'post_id'           => $postId,
            'excluded_user_id'  => $s['excluded']->user_id,
        ]);
        $this->assertEquals([$s['excluded']->username], $response->json('excluded_usernames'));
    }

    public function test_poster_cannot_exclude_themselves(): void
    {
        $s = $this->makeScenario();

        Sanctum::actingAs($s['author']);

        $response = $this->postJson('/api/topics/'.$s['topic']->topic_id.'/posts', [
            'content'        => 'Trying to hide from myself',
            'excluded_users' => [$s['author']->user_id],
        ]);

        $response->assertCreated();
        $postId = $response->json('post_id');

        $this->assertDatabaseMissing('post_exclusions', [
            'post_id'          => $postId,
            'excluded_user_id' => $s['author']->user_id,
        ]);
    }
}