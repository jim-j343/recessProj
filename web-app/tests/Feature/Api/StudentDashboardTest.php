<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
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

    private function createTopicWithOpeningPost(Group $group, User $creator, string $title = 'Topic'): Topic
    {
        $topic = Topic::create([
            'group_id'   => $group->group_id,
            'creator_id' => $creator->user_id,
            'title'      => $title,
        ]);
        Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $creator->user_id,
            'content'   => 'Opening post',
            'is_synced' => true,
        ]);
        return $topic;
    }

    private function addReply(Topic $topic, User $author, string $content = 'Reply'): Post
    {
        return Post::create([
            'topic_id'  => $topic->topic_id,
            'author_id' => $author->user_id,
            'content'   => $content,
            'is_synced' => true,
        ]);
    }

    public function test_participation_avg_averages_across_groups(): void
    {
        $student = $this->makeUser();
        $creator = $this->makeUser();

        $groupA = Group::create(['admin_id' => $creator->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $groupB = Group::create(['admin_id' => $creator->user_id, 'name' => 'Group B', 'course_name' => 'CS102']);
        $this->joinGroup($student, $groupA);
        $this->joinGroup($student, $groupB);

        $topicA = $this->createTopicWithOpeningPost($groupA, $creator, 'Topic A');
        $topicB = $this->createTopicWithOpeningPost($groupB, $creator, 'Topic B');

        // Group A: 2 replies -> pct = 20
        $this->addReply($topicA, $student);
        $this->addReply($topicA, $student);

        // Group B: 12 replies -> capped at 10 -> pct = 100
        for ($i = 0; $i < 12; $i++) {
            $this->addReply($topicB, $student);
        }

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertOk();
        $this->assertEquals(60.0, $response->json('participation_avg'));

        $byGroup = collect($response->json('participation_by_group'));
        $this->assertEquals(20, $byGroup->firstWhere('group_name', 'Group A')['pct']);
        $this->assertEquals(100, $byGroup->firstWhere('group_name', 'Group B')['pct']);
    }

    public function test_standing_is_good_when_no_warning(): void
    {
        $student = $this->makeUser();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertOk();
        $this->assertEquals('good', $response->json('standing.status'));
        $this->assertNull($response->json('standing.warning_number'));
    }

    public function test_standing_is_warning_when_unheeded_warning_exists(): void
    {
        $student = $this->makeUser();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        Warning::create([
            'user_id'        => $student->user_id,
            'group_id'       => $group->group_id,
            'warning_number' => 1,
            'issued_at'      => now(),
            'deadline'       => now()->addDays(7),
            'is_heeded'      => false,
        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertOk();
        $this->assertEquals('warning', $response->json('standing.status'));
        $this->assertEquals(1, $response->json('standing.warning_number'));
    }

    public function test_latest_topic_returns_most_recently_created(): void
    {
        $student = $this->makeUser();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        $older = $this->createTopicWithOpeningPost($group, $student, 'Older Topic');
        $older->forceFill(['created_at' => now()->subDay()])->save();

        $this->createTopicWithOpeningPost($group, $student, 'Newer Topic');

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertOk();
        $this->assertEquals('Newer Topic', $response->json('latest_topic.title'));
    }

    public function test_recommended_topic_excludes_topics_student_has_posted_in(): void
    {
        $student = $this->makeUser();
        $other = $this->makeUser();
        $group = Group::create(['admin_id' => $other->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);
        $this->joinGroup($other, $group);

        $postedIn = $this->createTopicWithOpeningPost($group, $other, 'Already Participated');
        $this->addReply($postedIn, $student);
        // give it lots of replies so it WOULD win by posts_count if not excluded
        for ($i = 0; $i < 5; $i++) {
            $this->addReply($postedIn, $other, "extra $i");
        }

        $notPostedIn = $this->createTopicWithOpeningPost($group, $other, 'Not Yet Joined');
        $this->addReply($notPostedIn, $other);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertOk();
        $this->assertEquals('Not Yet Joined', $response->json('recommended_topic.title'));
    }
}