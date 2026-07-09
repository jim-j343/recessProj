<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\GroupMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    // Lecturer: show create form
    public function create()
    {
        $groups = GroupMembership::where('user_id', auth()->id())
            ->where('status', 'active')
            ->with('group')
            ->get()
            ->pluck('group');

        return view('quiz.create', compact('groups'));
    }

    // Lecturer: save quiz + questions
    public function store(Request $request)
    {
        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'group_id'         => ['required', 'exists:groups,group_id'],
            'start_time'       => ['required', 'date'],
            'duration'         => ['required', 'integer', 'min:1'],
            'target'           => ['nullable', 'string', 'max:80'],
            'questions'        => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.answers'           => ['required', 'array', 'min:2'],
            'questions.*.answers.*'         => ['required', 'string'],
            'questions.*.correct_answer'    => ['required', 'integer'],
        ]);

        $quiz = Quiz::create([
            'lecturer_id'      => Auth::id(),
            'group_id'         => $request->group_id,
            'title'            => $request->title,
            'target_category'  => $request->target,
            'start_time'       => $request->start_time,
            'duration_minutes' => $request->duration,
            'is_published'     => $request->has('publish'),
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

        return redirect()->route('lecturer.dashboard')
            ->with('success', 'Quiz created successfully!');
    }

    // Student: show quiz to attempt
    // Student: show quiz to attempt
    public function show($id)
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);

        // Guard: must be published
        abort_unless($quiz->is_published, 403, 'This quiz is not available.');

        // Guard: user must belong to the quiz's group
        $isMember = GroupMembership::where('user_id', Auth::id())
            ->where('group_id', $quiz->group_id)
            ->where('status', 'active')
            ->exists();
        abort_unless($isMember, 403, 'You are not in this quiz\'s group.');

        // Guard: quiz time window
        $now    = now();
        $endsAt = $quiz->start_time->copy()->addMinutes($quiz->duration_minutes);

        if ($now->lt($quiz->start_time)) {
            return redirect()->route('dashboard')
                ->with('info', 'This quiz has not started yet.');
        }
        if ($now->gt($endsAt)) {
            return redirect()->route('dashboard')
                ->with('info', 'This quiz has closed.');
        }

        $submission = Submission::where('quiz_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($submission && $submission->submitted_at) {
            return redirect()->route('quiz.results', $id)
                ->with('info', 'You have already submitted this quiz.');
        }

        if (!$submission) {
            $submission = Submission::create([
                'quiz_id'    => $id,
                'user_id'    => Auth::id(),
                'started_at' => now(),
            ]);
        }

        // Timer counts from FIRST open (survives refresh),
        // and never extends past the quiz's own closing time
        $elapsed  = $submission->started_at->diffInSeconds(now());
        $timeLeft = max(0, min(
            ($quiz->duration_minutes * 60) - $elapsed,
            $now->diffInSeconds($endsAt, false)
        ));

        return view('quiz.show', compact('quiz', 'submission', 'timeLeft'));
    }

    // Student: submit quiz answers
    // Student: submit quiz answers
    public function submit(Request $request, $id)
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);

        $submission = Submission::where('quiz_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($submission->submitted_at) {
            return redirect()->route('quiz.results', $id);
        }

        // Reject submissions more than 2 minutes past the quiz close
        // (small grace window for the auto-submit to arrive)
        $endsAt = $quiz->start_time->copy()->addMinutes($quiz->duration_minutes);
        if (now()->gt($endsAt->copy()->addMinutes(2))) {
            $submission->update([
                'submitted_at'   => now(),
                'score'          => 0,
                'auto_submitted' => true,
            ]);
            return redirect()->route('quiz.results', $id)
                ->with('error', 'Time expired — quiz was closed before submission.');
        }

        $totalScore = 0;

        foreach ($quiz->questions as $question) {
            $selectedAnswerId = $request->input('answers.' . $question->question_id);

            $isCorrect = false;
            $marksAwarded = 0;

            if ($selectedAnswerId) {
                $answer = Answer::find($selectedAnswerId);
                $isCorrect = $answer && $answer->is_correct;
                $marksAwarded = $isCorrect ? $question->marks : 0;
                $totalScore += $marksAwarded;
            }

            SubmissionAnswer::create([
                'submission_id' => $submission->submission_id,
                'question_id'   => $question->question_id,
                'answer_id'     => $selectedAnswerId,
                'is_correct'    => $isCorrect,
                'marks_awarded' => $marksAwarded,
            ]);
        }

        $submission->update([
            'submitted_at'   => now(),
            'score'          => $totalScore,
            'auto_submitted' => $request->has('auto_submit'),
        ]);

        return redirect()->route('quiz.results', $id)
            ->with('success', 'Quiz submitted successfully!');
    }

    // Show results after submission
    public function results($id)
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);

        $submission = Submission::with('answers')
            ->where('quiz_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $totalMarks = $quiz->questions->sum('marks');

        return view('quiz.results', compact('quiz', 'submission', 'totalMarks'));
    }
}
