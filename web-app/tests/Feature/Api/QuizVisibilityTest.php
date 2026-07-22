<?php

namespace Tests\Feature\Api;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(): User
    {
        return User::create([
            'username'      => 'student_'.uniqid(),
            'email'         => uniqid().'@test.com',
            'password_hash' => bcrypt('password'),
            'system_role'   => 'student',
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

    public function test_quiz_visible_by_matching_group_id(): void
    {
        $student = $this->makeStudent();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        Quiz::create([
            'lecturer_id'      => $student->user_id,
            'group_id'         => $group->group_id,
            'title'            => 'Group Quiz',
            'course_name'      => null,
            'start_time'       => now(),
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Group Quiz']);
    }

    public function test_quiz_visible_by_matching_course_name_even_with_different_group_id(): void
    {
        $student = $this->makeStudent();
        $lecturer = $this->makeStudent();

        $myGroup = Group::create(['admin_id' => $lecturer->user_id, 'name' => 'My Group', 'course_name' => 'CS101']);
        $otherGroup = Group::create(['admin_id' => $lecturer->user_id, 'name' => 'Other Group', 'course_name' => 'CS101']);
        $this->joinGroup($student, $myGroup);

        // Pinned to a DIFFERENT group, but shares the student's course_name —
        // this is exactly the bug QuizApiController::index() used to miss.
        Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => $otherGroup->group_id,
            'title'            => 'Course-wide Quiz',
            'course_name'      => 'CS101',
            'start_time'       => now(),
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Course-wide Quiz']);
    }

    public function test_unpublished_quiz_is_not_visible(): void
    {
        $student = $this->makeStudent();
        $group = Group::create(['admin_id' => $student->user_id, 'name' => 'Group A', 'course_name' => 'CS101']);
        $this->joinGroup($student, $group);

        Quiz::create([
            'lecturer_id'      => $student->user_id,
            'group_id'         => $group->group_id,
            'title'            => 'Draft Quiz',
            'course_name'      => null,
            'start_time'       => now(),
            'duration_minutes' => 30,
            'is_published'     => false,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/quizzes')->assertOk()->assertJsonCount(0);
    }

    public function test_unrelated_quiz_is_not_visible(): void
    {
        $student = $this->makeStudent();
        $otherLecturer = $this->makeStudent();

        $myGroup = Group::create(['admin_id' => $student->user_id, 'name' => 'My Group', 'course_name' => 'CS101']);
        $unrelatedGroup = Group::create(['admin_id' => $otherLecturer->user_id, 'name' => 'Unrelated', 'course_name' => 'MATH201']);
        $this->joinGroup($student, $myGroup);

        Quiz::create([
            'lecturer_id'      => $otherLecturer->user_id,
            'group_id'         => $unrelatedGroup->group_id,
            'title'            => 'Unrelated Quiz',
            'course_name'      => 'MATH201',
            'start_time'       => now(),
            'duration_minutes' => 30,
            'is_published'     => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/quizzes')->assertOk()->assertJsonCount(0);
    }
}