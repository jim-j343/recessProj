<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAutoSubmitTest extends TestCase
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

    public function test_late_quiz_submission_is_auto_submitted_with_zero_score(): void
    {
        $lecturer = $this->makeUser('lecturer');
        $student = $this->makeUser();
        $group = Group::create(['admin_id' => $lecturer->user_id, 'name' => 'Databases', 'course_name' => 'CS201']);

        $this->joinGroup($student, $group);

        $quiz = Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => $group->group_id,
            'title'            => 'Week 5 Quiz',
            'course_name'      => null,
            'target_category'  => 'student',
            'start_time'       => now()->subHour(),
            'duration_minutes' => 10,
            'is_published'     => true,
        ]);

        $question = Question::create([
            'quiz_id'     => $quiz->quiz_id,
            'content'     => 'What is a primary key?',
            'type'        => 'mcq',
            'marks'       => 5,
            'order_index' => 1,
        ]);

        $correctAnswer = Answer::create([
            'question_id' => $question->question_id,
            'content'     => 'Unique row identifier',
            'is_correct'  => true,
        ]);

        Submission::create([
            'quiz_id'    => $quiz->quiz_id,
            'user_id'    => $student->user_id,
            'started_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($student)->post('/quiz/'.$quiz->quiz_id.'/submit', [
            'answers' => [
                $question->question_id => $correctAnswer->answer_id,
            ],
        ]);

        $response->assertRedirect('/quiz/'.$quiz->quiz_id.'/results');

        $submission = Submission::where('quiz_id', $quiz->quiz_id)
            ->where('user_id', $student->user_id)
            ->firstOrFail();

        $this->assertTrue($submission->auto_submitted);
        $this->assertSame(0, (int) $submission->score);
    }
}
