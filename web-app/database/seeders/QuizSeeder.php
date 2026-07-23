<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        $streamA   = Group::where('name', 'BSSE Year 1 - Stream A')->first();
        $streamB   = Group::where('name', 'BSSE Year 1 - Stream B')->first();
        $webDev    = Group::where('name', 'Web Development Cohort')->first();
        $oop       = Group::where('name', 'OOP Study Group')->first();
        $dataMgmt  = Group::where('name', 'Data Management Group')->first();
        $numerical = Group::where('name', 'Numerical Methods Group')->first();

        $namukasa = User::where('username', 'dr_namukasa')->first();
        $opio     = User::where('username', 'prof_opio')->first();
        $ssali    = User::where('username', 'dr_ssali')->first();

        // ---- THE key demo: one quiz targeted by COURSE, not group.
        // BSE1206 has two groups (Stream A and Stream B) — this single
        // quiz reaches students in both, without the lecturer needing to
        // be a member of both, and without creating the quiz twice. ----
        $courseQuiz = $this->makeQuizForCourse(
            'BSE1206: Software Development Principles',
            $namukasa, 'SDLC Fundamentals Quiz', now()->subDays(5), 25,
            [
                ['Which SDLC model is strictly sequential?', ['Agile', 'Waterfall', 'Scrum', 'Kanban'], 1],
                ['A user story primarily describes...', ['Database schema', 'A feature from the user\'s perspective', 'Server configuration', 'A test case'], 1],
                ['Code review is mainly used to...', ['Slow down releases', 'Catch defects early and share knowledge', 'Replace testing entirely', 'Assign blame'], 1],
                ['Which is a core Agile value?', ['Extensive documentation over working software', 'Responding to change over following a plan', 'Fixed scope over flexibility', 'Individual work over collaboration'], 1],
            ]
        );

        $this->submitQuizForGroups($courseQuiz, [$streamA, $streamB]);

        // Upcoming, published — for the countdown / notification demo
        $this->makeQuizForCourse(
            'BSE1206: Software Development Principles',
            $namukasa, 'Requirements Engineering', now()->addDays(2), 20,
            [
                ['Functional requirements describe...', ['How fast the system runs', 'What the system should do', 'Where it is hosted', 'Who funds it'], 1],
                ['A non-functional requirement example is...', ['Login with email', 'Response time under 2 seconds', 'Export to PDF', 'Password reset'], 1],
            ]
        );

        // ---- Web Development ----
        $quizWeb = $this->makeQuiz($webDev, $namukasa, 'REST API Concepts', now()->subDays(8), 25, [
            ['REST stands for Representational State Transfer.', ['True', 'False'], 0],
            ['Which HTTP method is idempotent?', ['POST', 'PUT', 'PATCH', 'CONNECT'], 1],
            ['Which status code means "Created"?', ['200', '201', '204', '301'], 1],
        ]);
        $this->submitQuizFor($quizWeb, $this->studentIdsFor($webDev));

        Quiz::create([
            'lecturer_id' => $namukasa->user_id, 'group_id' => $webDev->group_id, 'course_name' => null,
            'title' => 'HTML & CSS Basics (Draft)', 'target_category' => null,
            'start_time' => now()->addDays(9), 'duration_minutes' => 20, 'is_published' => false,
        ]);

        // ---- OOP ----
        $quizOop = $this->makeQuiz($oop, $opio, 'OOP vs FP Basics', now()->subDays(6), 30, [
            ['Which principle is core to OOP?', ['Encapsulation', 'Statelessness', 'Currying', 'Monads'], 0],
            ['Pure functions avoid...', ['Loops', 'Side effects', 'Return values', 'Parameters'], 1],
            ['Which is an example of polymorphism?', ['A variable holding two types at once', 'A single interface, multiple implementations', 'A loop with two exits', 'A class with no methods'], 1],
        ]);
        $this->submitQuizFor($quizOop, $this->studentIdsFor($oop));

        // ---- Data Management ----
        $quizData = $this->makeQuiz($dataMgmt, $opio, 'Normalization & Keys', now()->subDays(3), 20, [
            ['What does 1NF require?', ['No repeating groups', 'No transitive dependency', 'No partial dependency', 'No foreign keys'], 0],
            ['A foreign key references a...', ['Unique index', 'Primary key of another table', 'Composite column', 'View'], 1],
            ['2NF removes what kind of dependency?', ['Transitive', 'Partial', 'Circular', 'None'], 1],
        ]);
        $this->submitQuizFor($quizData, $this->studentIdsFor($dataMgmt));

        Quiz::create([
            'lecturer_id' => $opio->user_id, 'group_id' => $dataMgmt->group_id, 'course_name' => null,
            'title' => 'Indexing & Query Performance (Draft)', 'target_category' => null,
            'start_time' => now()->addDays(7), 'duration_minutes' => 30, 'is_published' => false,
        ]);

        // ---- Numerical Analysis ----
        $quizNum = $this->makeQuiz($numerical, $ssali, 'Root-Finding Methods', now()->subDays(4), 25, [
            ['The bisection method requires...', ['A single starting guess', 'Two points with opposite function signs', 'A matrix', 'A derivative'], 1],
            ['Newton-Raphson uses the...', ['Second derivative only', 'Function and its first derivative', 'Integral of the function', 'Bisection midpoint'], 1],
            ['Which method converges fastest near the root, when it converges?', ['Bisection', 'Newton-Raphson', 'False position', 'Linear search'], 1],
        ]);
        $this->submitQuizFor($quizNum, $this->studentIdsFor($numerical));

        // Draft
        Quiz::create([
            'lecturer_id' => $ssali->user_id, 'group_id' => $numerical->group_id, 'course_name' => null,
            'title' => 'Numerical Integration (Draft)', 'target_category' => null,
            'start_time' => now()->addDays(10), 'duration_minutes' => 25, 'is_published' => false,
        ]);
    }

    private function studentIdsFor(Group $group)
    {
        return GroupMembership::where('group_id', $group->group_id)
            ->whereHas('user', fn ($q) => $q->where('system_role', 'student'))
            ->pluck('user_id');
    }

    /** Create a group-targeted quiz (legacy style — one group_id) */
    private function makeQuiz(Group $group, User $lecturer, string $title, $startTime, int $duration, array $questions): Quiz
    {
        $quiz = Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => $group->group_id,
            'course_name'      => null,
            'title'            => $title,
            'target_category'  => null,
            'start_time'       => $startTime,
            'duration_minutes' => $duration,
            'is_published'     => true,
        ]);

        $this->addQuestions($quiz, $questions);

        return $quiz;
    }

    /** Create a course-targeted quiz — applies to every group sharing that course_name */
    private function makeQuizForCourse(string $courseName, User $lecturer, string $title, $startTime, int $duration, array $questions): Quiz
    {
        $quiz = Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => null,
            'course_name'      => $courseName,
            'title'            => $title,
            'target_category'  => null,
            'start_time'       => $startTime,
            'duration_minutes' => $duration,
            'is_published'     => true,
        ]);

        $this->addQuestions($quiz, $questions);

        return $quiz;
    }

    private function addQuestions(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $index => [$text, $options, $correctIndex]) {
            $question = Question::create([
                'quiz_id'     => $quiz->quiz_id,
                'content'     => $text,
                'type'        => 'mcq',
                'marks'       => 1,
                'order_index' => $index + 1,
            ]);

            foreach ($options as $optIndex => $optionText) {
                Answer::create([
                    'question_id' => $question->question_id,
                    'content'     => $optionText,
                    'is_correct'  => $optIndex === $correctIndex,
                ]);
            }
        }
    }

    /** Submit realistic answers for students across MULTIPLE groups sharing a course */
    private function submitQuizForGroups(Quiz $quiz, array $groups): void
    {
        $userIds = collect();
        foreach ($groups as $group) {
            $userIds = $userIds->merge($this->studentIdsFor($group));
        }
        $this->submitQuizFor($quiz, $userIds->unique());
    }

    /**
     * Submit realistic, varied answers so scores differ — makes group
     * averages and "vs peer avg" comparisons meaningful. Leaves the last
     * student with no attempt, so the "No quizzes yet" state has something
     * real to show too.
     */
    private function submitQuizFor(Quiz $quiz, $userIds): void
    {
        $userIds = $userIds->count() > 1 ? $userIds->slice(0, -1) : $userIds;

        $questions = Question::where('quiz_id', $quiz->quiz_id)->with('answers')->get();
        $totalMarks = $questions->sum('marks');

        foreach ($userIds as $userId) {
            $correctTarget = min($totalMarks, max(1, (int) round($totalMarks * (rand(50, 100) / 100))));

            $correctIndexes = collect(range(0, $questions->count() - 1))
                ->shuffle()
                ->take($correctTarget);

            $submission = Submission::create([
                'quiz_id'        => $quiz->quiz_id,
                'user_id'        => $userId,
                'started_at'     => $quiz->start_time->copy()->addMinutes(rand(0, 3)),
                'submitted_at'   => $quiz->start_time->copy()->addMinutes(rand(5, max(6, $quiz->duration_minutes - 1))),
                'score'          => 0,
                'auto_submitted' => false,
            ]);

            $score = 0;

            foreach ($questions as $qIndex => $question) {
                $answers = $question->answers;
                $correctAnswer = $answers->firstWhere('is_correct', true);
                $giveCorrect = $correctIndexes->contains($qIndex);

                $chosenAnswer = $giveCorrect
                    ? $correctAnswer
                    : $answers->where('is_correct', false)->random();

                SubmissionAnswer::create([
                    'submission_id' => $submission->submission_id,
                    'question_id'   => $question->question_id,
                    'answer_id'     => $chosenAnswer->answer_id,
                    'is_correct'    => $giveCorrect,
                    'marks_awarded' => $giveCorrect ? $question->marks : 0,
                ]);

                if ($giveCorrect) {
                    $score += $question->marks;
                }
            }

            $submission->update(['score' => $score]);
        }
    }
}
