<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\GroupMembership;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    /** GET /api/quizzes — student: available quizzes in their groups */
    public function index(Request $request): JsonResponse
    {
        $groupIds = GroupMembership::where('user_id', $request->user()->user_id)
            ->where('status', 'active')->pluck('group_id');

        $quizzes = Quiz::whereIn('group_id', $groupIds)
            ->where('is_published', true)
            ->latest()->get()
            ->map(fn($q) => $this->quizShape($q));

        return response()->json($quizzes);
    }

    /** GET /api/quizzes/my — lecturer: their own quizzes */
    public function myQuizzes(Request $request): JsonResponse
    {
        $quizzes = Quiz::where('lecturer_id', $request->user()->user_id)
            ->latest()->get()
            ->map(fn($q) => $this->quizShape($q));

        return response()->json($quizzes);
    }

    /** GET /api/quizzes/{id} — quiz with questions and answers */
    public function show($id): JsonResponse
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);
        return response()->json([
            'quiz'      => $this->quizShape($quiz),
            'questions' => $quiz->questions->map(fn($q) => [
                'question_id' => $q->question_id,
                'content'     => $q->content,
                'marks'       => $q->marks,
                'answers'     => $q->answers->map(fn($a) => [
                    'answer_id' => $a->answer_id,
                    'content'   => $a->content,
                ]),
            ]),
        ]);
    }

    /** POST /api/quizzes/{id}/submit */
    public function submit(Request $request, $id): JsonResponse
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);
        $userId = $request->user()->user_id;

        $submission = Submission::where('quiz_id', $id)
            ->where('user_id', $userId)->first();

        if ($submission?->submitted_at) {
            return response()->json(['message' => 'Already submitted.'], 409);
        }

        if (!$submission) {
            $submission = Submission::create([
                'quiz_id'    => $id,
                'user_id'    => $userId,
                'started_at' => now(),
            ]);
        }

        $totalScore = 0;
        foreach ($quiz->questions as $question) {
            $selectedId = $request->input('answers.' . $question->question_id);
            $isCorrect  = false;
            $marks      = 0;
            if ($selectedId) {
                $answer    = Answer::find($selectedId);
                $isCorrect = $answer && $answer->is_correct;
                $marks     = $isCorrect ? $question->marks : 0;
                $totalScore += $marks;
            }
            SubmissionAnswer::create([
                'submission_id' => $submission->submission_id,
                'question_id'   => $question->question_id,
                'answer_id'     => $selectedId,
                'is_correct'    => $isCorrect,
                'marks_awarded' => $marks,
            ]);
        }

        $submission->update([
            'submitted_at'   => now(),
            'score'          => $totalScore,
            'auto_submitted' => $request->boolean('auto_submit'),
        ]);

        return response()->json(['score' => $totalScore, 'total' => $quiz->questions->sum('marks')]);
    }

    /** GET /api/quizzes/{id}/results — student: their own result */
    public function myResult(Request $request, $id): JsonResponse
    {
        $submission = Submission::with('answers')
            ->where('quiz_id', $id)
            ->where('user_id', $request->user()->user_id)
            ->firstOrFail();

        $quiz = Quiz::with('questions')->findOrFail($id);

        return response()->json([
            'score'          => $submission->score,
            'total'          => $quiz->questions->sum('marks'),
            'auto_submitted' => $submission->auto_submitted,
            'submitted_at'   => $submission->submitted_at?->toIso8601String(),
        ]);
    }

    /** GET /api/quizzes/{id}/all-results — lecturer: all student results */
    public function allResults(Request $request, $id): JsonResponse
    {
        $quiz = Quiz::with('questions')->findOrFail($id);

        if ($quiz->lecturer_id !== $request->user()->user_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $submissions = Submission::with('user')
            ->where('quiz_id', $id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')->get()
            ->map(fn($s) => [
                'username'       => $s->user->username,
                'score'          => $s->score,
                'total'          => $quiz->questions->sum('marks'),
                'auto_submitted' => $s->auto_submitted,
                'submitted_at'   => $s->submitted_at?->toIso8601String(),
            ]);

        return response()->json($submissions);
    }

    private function quizShape(Quiz $q): array
    {
        return [
            'quiz_id'          => (int) $q->quiz_id,
            'title'            => $q->title,
            'group_id'         => (int) $q->group_id,
            'start_time'       => $q->start_time?->toIso8601String(),
            'duration_minutes' => $q->duration_minutes,
            'is_published'     => (bool) $q->is_published,
            'target_category'  => $q->target_category,
        ];
    }
}