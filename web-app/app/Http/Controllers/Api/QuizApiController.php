<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    /** GET /api/quizzes — student: available quizzes in their groups
     *  (including course-targeted quizzes visible to every group sharing
     *  that course unit — mirrors StudentController::dashboard() on web) */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->user_id;
        $groupIds = GroupMembership::where('user_id', $userId)
            ->where('status', 'active')->pluck('group_id');

        $courseNames = Group::whereIn('group_id', $groupIds)->pluck('course_name')->filter();

        $quizzes = Quiz::with(['questions', 'submissions' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->where(function ($q) use ($groupIds, $courseNames) {
                $q->whereIn('group_id', $groupIds);
                if ($courseNames->isNotEmpty()) {
                    $q->orWhereIn('course_name', $courseNames);
                }
            })
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

    /** POST /api/quizzes — lecturer: create a quiz + its questions/answers.
     *  Mirrors QuizController::store() on web exactly. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'                      => ['required', 'string', 'max:255'],
            'course_name'                => ['required', 'string', 'max:150'],
            'start_time'                 => ['required', 'date'],
            'duration'                   => ['required', 'integer', 'min:1'],
            'target'                     => ['nullable', 'string', 'max:80'],
            'questions'                  => ['required', 'array', 'min:1'],
            'questions.*.text'           => ['required', 'string'],
            'questions.*.answers'        => ['required', 'array', 'min:2'],
            'questions.*.answers.*'      => ['required', 'string'],
            'questions.*.correct_answer' => ['required', 'integer'],
        ]);

        $quiz = Quiz::create([
            'lecturer_id'      => $request->user()->user_id,
            'course_name'      => $request->course_name,
            'title'            => $request->title,
            'target_category'  => $request->target,
            'start_time'       => $request->start_time,
            'duration_minutes' => $request->duration,
            'is_published'     => $request->boolean('publish'),
        ]);

        foreach ($request->questions as $index => $q) {
            $question = Question::create([
                'quiz_id'     => $quiz->quiz_id,
                'content'     => $q['text'],
                'type'        => 'mcq',
                'marks'       => 1,
                'order_index' => $index + 1,
            ]);

            foreach ($q['answers'] as $aIndex => $answerText) {
                Answer::create([
                    'question_id' => $question->question_id,
                    'content'     => $answerText,
                    'is_correct'  => ($aIndex == $q['correct_answer']),
                ]);
            }
        }

        if ($quiz->is_published) {
            $memberIds = GroupMembership::whereIn('group_id', $quiz->eligibleGroupIds())
                ->where('status', 'active')
                ->where('user_id', '!=', $request->user()->user_id)
                ->pluck('user_id')
                ->unique();

            foreach ($memberIds as $userId) {
                Notification::notify($userId, 'quiz_announced');
            }
        }

        return response()->json($this->quizShape($quiz->fresh()), 201);
    }

    /** POST /api/quizzes/{id}/publish — lecturer: publish a draft quiz.
     *  Mirrors QuizController::publish() on web. */
    public function publish(Request $request, $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);

        if ($quiz->lecturer_id !== $request->user()->user_id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You cannot publish this quiz.'], 403);
        }
        if ($quiz->is_published) {
            return response()->json(['message' => 'This quiz is already published.'], 422);
        }

        $quiz->update(['is_published' => true]);

        $memberIds = GroupMembership::whereIn('group_id', $quiz->eligibleGroupIds())
            ->where('status', 'active')
            ->where('user_id', '!=', $request->user()->user_id)
            ->pluck('user_id')
            ->unique();

        foreach ($memberIds as $userId) {
            Notification::notify($userId, 'quiz_announced');
        }

        return response()->json($this->quizShape($quiz->fresh()));
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
        $data = [
            'quiz_id'          => (int) $q->quiz_id,
            'title'            => $q->title,
            'group_id'         => (int) $q->group_id,
            'start_time'       => $q->start_time?->toIso8601String(),
            'duration_minutes' => $q->duration_minutes,
            'is_published'     => (bool) $q->is_published,
            'target_category'  => $q->target_category,
        ];

        if ($q->relationLoaded('submissions')) {
            $submission = $q->submissions->first();
            if ($submission && $submission->submitted_at) {
                $data['my_result'] = [
                    'score'          => $submission->score,
                    'total'          => $q->questions->sum('marks'),
                    'auto_submitted' => $submission->auto_submitted,
                    'submitted_at'   => $submission->submitted_at?->toIso8601String(),
                ];
            }
        }

        return $data;
    }
}