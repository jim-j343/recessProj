<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\GroupMembership;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    // Lecturer: show create form
    public function create()
    {
        // Every distinct course unit in the system — a lecturer doesn't
        // need to already be a member of a group to set a quiz for the
        // course it teaches
        $courseNames = \App\Models\Group::whereNotNull('course_name')
            ->distinct()
            ->orderBy('course_name')
            ->pluck('course_name');

        return view('quiz.create', compact('courseNames'));
    }

    // Lecturer: save quiz + questions
    public function store(Request $request)
    {
        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'course_name'      => ['required', 'string', 'max:150'],
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
            'course_name'      => $request->course_name,
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

        if ($quiz->is_published) {
            $memberIds = GroupMembership::whereIn('group_id', $quiz->eligibleGroupIds())
                ->where('status', 'active')
                ->where('user_id', '!=', Auth::id())
                ->pluck('user_id')
                ->unique();

            foreach ($memberIds as $userId) {
                Notification::notify($userId, 'quiz_announced');
            }
        }

        return redirect()->route('lecturer.dashboard')
            ->with('success', 'Quiz created successfully!');
    }

    // Lecturer: read-only preview of their own quiz (draft or published).
    // Deliberately separate from show() — that method creates a real
    // Submission the moment it's opened and blocks drafts entirely, since
    // it's built for a student taking the quiz, not a lecturer reviewing it.
    public function preview($id)
    {
        $quiz = Quiz::with(['questions.answers', 'group'])->findOrFail($id);

        if ($quiz->lecturer_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $totalMarks = $quiz->questions->sum('marks');

        $completedSubmissions = Submission::where('quiz_id', $quiz->quiz_id)
            ->whereNotNull('submitted_at')
            ->get();

        $avgPct = ($totalMarks > 0 && $completedSubmissions->count())
            ? round($completedSubmissions->avg(fn ($s) => ($s->score / $totalMarks) * 100), 1)
            : null;

        $eligibleGroupCount = $quiz->eligibleGroupIds()->count();

        return view('quiz.preview', compact('quiz', 'totalMarks', 'completedSubmissions', 'avgPct', 'eligibleGroupCount'));
    }

    // Lecturer: publish a draft quiz — flips is_published and notifies
    // the group, same notification logic used when publishing at creation
    // time in store()
    public function publish($id)
    {
        $quiz = Quiz::findOrFail($id);

        if ($quiz->lecturer_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        if ($quiz->is_published) {
            return back()->with('error', 'This quiz is already published.');
        }

        $quiz->update(['is_published' => true]);

        $memberIds = GroupMembership::whereIn('group_id', $quiz->eligibleGroupIds())
            ->where('status', 'active')
            ->where('user_id', '!=', Auth::id())
            ->pluck('user_id')
            ->unique();

        foreach ($memberIds as $userId) {
            Notification::notify($userId, 'quiz_announced');
        }

        return redirect()->route('quiz.preview', $quiz->quiz_id)
            ->with('success', 'Quiz published! Group members have been notified.');
    }

    // Lecturer: edit a draft. Only unpublished quizzes are editable — once
    // students can see a quiz, changing its questions underneath them would
    // corrupt submissions already in flight.
    public function edit($id)
    {
        $quiz = Quiz::with('questions.answers')->findOrFail($id);

        if ($quiz->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                ->with('error', 'You can only edit quizzes you created.');
        }

        if ($quiz->is_published) {
            return redirect()->route('quiz.preview', $quiz->quiz_id)
                ->with('error', 'Published quizzes cannot be edited — students may already have submissions.');
        }

        $courseNames = \App\Models\Group::whereNotNull('course_name')
            ->distinct()
            ->orderBy('course_name')
            ->pluck('course_name');

        return view('quiz.edit', compact('quiz', 'courseNames'));
    }

    // Lecturer: save changes to a draft. The question set is replaced
    // wholesale rather than diffed — safe precisely because an unpublished
    // quiz cannot have any submissions yet.
    public function update(Request $request, $id)
    {
        $quiz = Quiz::findOrFail($id);

        if ($quiz->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                ->with('error', 'You can only edit quizzes you created.');
        }

        if ($quiz->is_published) {
            return redirect()->route('quiz.preview', $quiz->quiz_id)
                ->with('error', 'Published quizzes cannot be edited.');
        }

        $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'course_name'      => ['required', 'string', 'max:150'],
            'start_time'       => ['required', 'date'],
            'duration'         => ['required', 'integer', 'min:1'],
            'target'           => ['nullable', 'string', 'max:80'],
            'questions'        => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.answers'        => ['required', 'array', 'min:2'],
            'questions.*.answers.*'      => ['required', 'string'],
            'questions.*.correct_answer' => ['required', 'integer'],
        ]);

        $quiz->update([
            'course_name'      => $request->course_name,
            'title'            => $request->title,
            'target_category'  => $request->target,
            'start_time'       => $request->start_time,
            'duration_minutes' => $request->duration,
            'is_published'     => $request->has('publish'),
        ]);

        // Clear the old question set. Answers are deleted explicitly rather
        // than relying on a cascade, so this works whatever the FK is set to.
        foreach ($quiz->questions as $oldQuestion) {
            Answer::where('question_id', $oldQuestion->question_id)->delete();
        }
        Question::where('quiz_id', $quiz->quiz_id)->delete();

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

        // Publishing straight from the edit screen should notify, same as store()
        if ($quiz->is_published) {
            $memberIds = GroupMembership::whereIn('group_id', $quiz->eligibleGroupIds())
                ->where('status', 'active')
                ->where('user_id', '!=', Auth::id())
                ->pluck('user_id')
                ->unique();

            foreach ($memberIds as $userId) {
                Notification::notify($userId, 'quiz_announced');
            }
        }

        return redirect()->route('quiz.preview', $quiz->quiz_id)
            ->with('success', $quiz->is_published ? 'Quiz updated and published!' : 'Draft updated.');
    }

    // Student: show quiz to attempt
    public function show($id)
    {
        $quiz = Quiz::with(['questions.answers'])->findOrFail($id);

        // Guard: must be published
        abort_unless($quiz->is_published, 403, 'This quiz is not available.');

        // Guard: user must belong to a group this quiz applies to
        $isMember = GroupMembership::where('user_id', Auth::id())
            ->whereIn('group_id', $quiz->eligibleGroupIds())
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
        $timeLeft = (int) max(0, min(
            ($quiz->duration_minutes * 60) - $elapsed,
            $now->diffInSeconds($endsAt, false)
        ));

        return view('quiz.show', compact('quiz', 'submission', 'timeLeft'));
    }

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
