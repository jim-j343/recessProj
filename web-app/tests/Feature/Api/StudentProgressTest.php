<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\ParticipationScore;
use App\Models\Post;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProgressTest extends TestCase
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

    public function test_post_and_reply_counts_and_participation_pct(): void
    {
        $student = $this->makeUser();
        $other = $this->makeUser();
        $group = Group::create(['admin_id' => $other->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);
        $this->joinGroup($other, $group);

        // Student opens a topic themselves (counts as a post, not a reply)
        $ownTopic = Topic::create(['group_id' => $group->group_id, 'creator_id' => $student->user_id, 'title' => 'My Topic']);
        Post::create(['topic_id' => $ownTopic->topic_id, 'author_id' => $student->user_id, 'content' => 'Opening', 'is_synced' => true]);

        // Student replies twice in someone else's topic
        $othersTopic = Topic::create(['group_id' => $group->group_id, 'creator_id' => $other->user_id, 'title' => 'Their Topic']);
        Post::create(['topic_id' => $othersTopic->topic_id, 'author_id' => $other->user_id, 'content' => 'Opening', 'is_synced' => true]);
        Post::create(['topic_id' => $othersTopic->topic_id, 'author_id' => $student->user_id, 'content' => 'Reply 1', 'is_synced' => true]);
        Post::create(['topic_id' => $othersTopic->topic_id, 'author_id' => $student->user_id, 'content' => 'Reply 2', 'is_synced' => true]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/progress');

        $response->assertOk();
        $this->assertEquals(3, $response->json('post_count'));   // 1 opening + 2 replies
        $this->assertEquals(2, $response->json('reply_count'));  // opening post excluded
        $this->assertEquals(20, $response->json('participation_pct')); // min(2,10)*10
    }

    public function test_activity_by_day_counts_todays_posts(): void
    {
        $student = $this->makeUser();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        $topic = Topic::create(['group_id' => $group->group_id, 'creator_id' => $student->user_id, 'title' => 'Topic']);
        Post::create(['topic_id' => $topic->topic_id, 'author_id' => $student->user_id, 'content' => 'A', 'is_synced' => true]);
        Post::create(['topic_id' => $topic->topic_id, 'author_id' => $student->user_id, 'content' => 'B', 'is_synced' => true]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/progress');

        $response->assertOk();
        $days = collect($response->json('activity_by_day'));
        $this->assertCount(7, $days);
        // last entry is always "today" (range(6,0) counts down to 0 days ago)
        $this->assertEquals(2, $days->last()['count']);
        $this->assertEquals(now()->format('D'), $days->last()['label']);
    }

    public function test_assessment_history_score_and_peer_comparison(): void
    {
        $student = $this->makeUser();
        $peer = $this->makeUser();
        $lecturer = $this->makeUser('lecturer');
        $group = Group::create(['admin_id' => $lecturer->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);
        $this->joinGroup($peer, $group);

        $quiz = Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => $group->group_id,
            'title'            => 'Midterm',
            'course_name'      => null,
            'start_time'       => now()->subHour(),
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        Question::create(['quiz_id' => $quiz->quiz_id, 'content' => 'Q1', 'type' => 'mcq', 'marks' => 5, 'order_index' => 1]);
        Question::create(['quiz_id' => $quiz->quiz_id, 'content' => 'Q2', 'type' => 'mcq', 'marks' => 5, 'order_index' => 2]);
        // total marks = 10

        Submission::create([
            'quiz_id'      => $quiz->quiz_id,
            'user_id'      => $student->user_id,
            'started_at'   => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(10),
            'score'        => 8, // 80%
        ]);
        Submission::create([
            'quiz_id'      => $quiz->quiz_id,
            'user_id'      => $peer->user_id,
            'started_at'   => now()->subMinutes(20),
            'submitted_at' => now()->subMinutes(10),
            'score'        => 4, // 40%
        ]);
        // avg of both (80+40)/2 = 60 -> vs_peer for student = 80-60 = 20

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/progress');

        $response->assertOk();
        $history = collect($response->json('assessment_history'));
        $this->assertCount(1, $history);
        $entry = $history->first();

        $this->assertEquals('Midterm', $entry['title']);
        $this->assertEquals(80.0, $entry['score_pct']);
        $this->assertEquals(20.0, $entry['vs_peer_pct']);
    }

    public function test_latest_remark_returned(): void
    {
        $student = $this->makeUser();
        $lecturer = $this->makeUser('lecturer');
        $group = Group::create(['admin_id' => $lecturer->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        $older = ParticipationScore::create([
            'user_id'    => $student->user_id,
            'group_id'   => $group->group_id,
            'criteria'   => 'Old remark',
            'score'      => 5,
            'awarded_by' => $lecturer->user_id,
        ]);
        $older->forceFill(['created_at' => now()->subWeek()])->save();

        ParticipationScore::create([
            'user_id'    => $student->user_id,
            'group_id'   => $group->group_id,
            'criteria'   => 'Great engagement this week',
            'score'      => 9,
            'awarded_by' => $lecturer->user_id,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/progress');

        $response->assertOk();
        $this->assertEquals('Great engagement this week', $response->json('latest_remark.criteria'));
        $this->assertEquals(9, $response->json('latest_remark.score'));
    }
}