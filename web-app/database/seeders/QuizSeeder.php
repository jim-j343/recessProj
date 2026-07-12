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
        $groupA   = Group::where('name', 'Computer Science Year 2')->first();
        $groupB   = Group::where('name', 'Software Engineering Year 3')->first();
        $namukasa = User::where('username', 'dr_namukasa')->first();
        $opio     = User::where('username', 'prof_opio')->first();

        // ---- Group A: Database Systems ----
        $quizA1 = $this->makeQuiz($groupA, $namukasa, 'SQL Fundamentals Quiz', now()->subDays(10), 30, [
            ['What does SQL stand for?', ['Structured Query Language', 'Sequential Query Logic', 'Simple Question Language', 'Server Query Link'], 0],
            ['Which keyword removes duplicate rows?', ['UNIQUE', 'DISTINCT', 'FILTER', 'GROUP'], 1],
            ['A primary key can contain NULL values.', ['True', 'False'], 1],
            ['Which clause filters grouped results?', ['WHERE', 'HAVING', 'FILTER', 'ON'], 1],
            ['Which join returns unmatched rows from both tables?', ['INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL OUTER JOIN'], 3],
        ]);

        $quizA2 = $this->makeQuiz($groupA, $namukasa, 'Normalization & Keys', now()->subDays(3), 20, [
            ['What does 1NF require?', ['No repeating groups', 'No transitive dependency', 'No partial dependency', 'No foreign keys'], 0],
            ['A foreign key references a...', ['Unique index', 'Primary key of another table', 'Composite column', 'View'], 1],
            ['2NF removes what kind of dependency?', ['Transitive', 'Partial', 'Circular', 'None'], 1],
            ['The main identifier chosen from the candidate keys is the...', ['Foreign key', 'Composite key', 'Primary key', 'Surrogate key'], 2],
        ]);

        // Upcoming + published — for the quiz_announced notification / countdown demo
        $this->makeQuiz($groupA, $namukasa, 'REST API Concepts', now()->addDays(2), 25, [
            ['REST stands for Representational State Transfer.', ['True', 'False'], 0],
            ['Which HTTP method is idempotent?', ['POST', 'PUT', 'PATCH', 'CONNECT'], 1],
            ['Which status code means "Created"?', ['200', '201', '204', '301'], 1],
        ]);

        // Draft — for the "Draft" badge on the lecturer dashboard
        Quiz::create([
            'lecturer_id'      => $namukasa->user_id,
            'group_id'         => $groupA->group_id,
            'title'            => 'Indexing & Query Performance (Draft)',
            'target_category'  => null,
            'start_time'       => now()->addDays(9),
            'duration_minutes' => 30,
            'is_published'     => false,
        ]);

        // ---- Group B: Software Architecture ----
        $quizB1 = $this->makeQuiz($groupB, $opio, 'OOP vs FP Basics', now()->subDays(8), 30, [
            ['Which principle is core to OOP?', ['Encapsulation', 'Statelessness', 'Currying', 'Monads'], 0],
            ['Pure functions avoid...', ['Loops', 'Side effects', 'Return values', 'Parameters'], 1],
            ['Inheritance is a feature of...', ['Functional programming', 'Object-oriented programming', 'Both equally', 'Neither'], 1],
            ['Which is an example of polymorphism?', ['A variable holding two types at once', 'A single interface, multiple implementations', 'A loop with two exits', 'A class with no methods'], 1],
            ['Immutability is emphasised more in...', ['OOP', 'Functional programming', 'Procedural programming', 'Assembly'], 1],
        ]);

        $quizB2 = $this->makeQuiz($groupB, $opio, 'MVC Design Pattern', now()->subDays(4), 25, [
            ['What does the "M" in MVC stand for?', ['Method', 'Model', 'Middleware', 'Module'], 1],
            ['The Controller is responsible for...', ['Storing data', 'Rendering HTML directly', 'Handling input and coordinating Model/View', 'Styling the page'], 2],
            ['Which layer should contain business logic?', ['View', 'Controller only', 'Model', 'Routes'], 2],
            ['MVC primarily helps with...', ['Separation of concerns', 'Faster network requests', 'Smaller database size', 'Browser compatibility'], 0],
        ]);

        // Draft
        Quiz::create([
            'lecturer_id'      => $opio->user_id,
            'group_id'         => $groupB->group_id,
            'title'            => 'Unit Testing in Java (Draft)',
            'target_category'  => null,
            'start_time'       => now()->addDays(6),
            'duration_minutes' => 20,
            'is_published'     => false,
        ]);

        // ---- Submissions ----
        $groupAStudents = GroupMembership::where('group_id', $groupA->group_id)
            ->whereHas('user', fn ($q) => $q->where('system_role', 'student'))
            ->pluck('user_id');

        $groupBStudents = GroupMembership::where('group_id', $groupB->group_id)
            ->whereHas('user', fn ($q) => $q->where('system_role', 'student'))
            ->pluck('user_id');

        // Leave the last student in each group with no attempts, so the
        // "No quizzes yet" empty state still has something real to show
        $this->submitQuizFor($quizA1, $groupAStudents->slice(0, -1));
        $this->submitQuizFor($quizA2, $groupAStudents->slice(0, -1));
        $this->submitQuizFor($quizB1, $groupBStudents->slice(0, -1));
        $this->submitQuizFor($quizB2, $groupBStudents->slice(0, -1));
    }

    /**
     * Create a quiz with MCQ questions.
     * $questions = [[questionText, [option, option, ...], correctOptionIndex], ...]
     */
    private function makeQuiz(Group $group, User $lecturer, string $title, $startTime, int $duration, array $questions): Quiz
    {
        $quiz = Quiz::create([
            'lecturer_id'      => $lecturer->user_id,
            'group_id'         => $group->group_id,
            'title'            => $title,
            'target_category'  => null,
            'start_time'       => $startTime,
            'duration_minutes' => $duration,
            'is_published'     => true,
        ]);

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

        return $quiz;
    }

    /**
     * Submit realistic, varied answers for each student so scores differ —
     * makes group averages and "vs peer avg" comparisons meaningful.
     */
    private function submitQuizFor(Quiz $quiz, $userIds): void
    {
        $questions = Question::where('quiz_id', $quiz->quiz_id)->with('answers')->get();
        $totalMarks = $questions->sum('marks');

        foreach ($userIds as $userId) {
            // Each student gets a random number of correct answers, biased
            // toward doing reasonably well so the demo data looks plausible
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
