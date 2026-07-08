<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function create()
    {
        return view('quiz.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'duration'   => ['required', 'integer', 'min:1'],
            'target'     => ['nullable', 'string', 'max:80'],
        ]);

        \App\Models\Quiz::create([
            'lecturer_id'     => auth()->id(),
            'group_id'        => 1,
            'title'           => $validated['title'],
            'target_category' => $validated['target'] ?? null,
            'start_time'      => $validated['start_time'],
            'duration_minutes'=> $validated['duration'],
            'is_published'    => false,
        ]);

        return redirect()->route('lecturer.dashboard')
            ->with('success', 'Quiz configuration saved!');
    }

    public function show($id)
    {
        return view('quiz.show');
    }
}
