<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Topic;
use App\Models\TopicRecommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationRefreshTest extends TestCase
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

    public function test_refresh_replaces_existing_recommendations_with_ml_results(): void
    {
        $user = $this->makeUser();
        $group = Group::create(['admin_id' => $user->user_id, 'name' => 'AI Club', 'course_name' => 'CS101']);

        $oldTopic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $user->user_id,
            'title'      => 'Old recommendation',
            'category'   => 'Old',
        ]);

        $firstNewTopic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $user->user_id,
            'title'      => 'New recommendation one',
            'category'   => 'ML',
        ]);

        $secondNewTopic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $user->user_id,
            'title'      => 'New recommendation two',
            'category'   => 'ML',
        ]);

        TopicRecommendation::create([
            'user_id'      => $user->user_id,
            'topic_id'     => $oldTopic->topic_id,
            'score'        => 0.1,
            'generated_at' => now()->subDay(),
            'is_dismissed' => false,
        ]);

        $otherUser = $this->makeUser();
        TopicRecommendation::create([
            'user_id'      => $otherUser->user_id,
            'topic_id'     => $oldTopic->topic_id,
            'score'        => 0.2,
            'generated_at' => now()->subDay(),
            'is_dismissed' => false,
        ]);

        Http::fake([
            'http://localhost:5001/recommend/*' => Http::response([
                'recommendations' => [
                    ['topic_id' => $firstNewTopic->topic_id, 'score' => 0.91],
                    ['topic_id' => $secondNewTopic->topic_id, 'score' => 0.84],
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->from('/dashboard')
            ->get('/recommendations/refresh')
            ->assertRedirect('/dashboard')
            ->assertSessionHas('success', 'Recommendations updated!');

        $this->assertDatabaseMissing('topic_recommendations', [
            'user_id'  => $user->user_id,
            'topic_id' => $oldTopic->topic_id,
        ]);

        $this->assertDatabaseHas('topic_recommendations', [
            'user_id'  => $user->user_id,
            'topic_id' => $firstNewTopic->topic_id,
        ]);

        $this->assertDatabaseHas('topic_recommendations', [
            'user_id'  => $user->user_id,
            'topic_id' => $secondNewTopic->topic_id,
        ]);

        $this->assertDatabaseHas('topic_recommendations', [
            'user_id'  => $otherUser->user_id,
            'topic_id' => $oldTopic->topic_id,
        ]);
    }
}
