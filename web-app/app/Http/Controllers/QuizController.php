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

        // Quiz creation will be implemented when QuizModel is ready
        return redirect()->route('lecturer.dashboard')
            ->with('success', 'Quiz configuration saved!');
    }

    public function show($id)
    {
        return view('quiz.show');
    }
}
