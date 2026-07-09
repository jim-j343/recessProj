<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz Results</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            {{-- Score card --}}
            <div class="bg-white rounded-lg shadow-sm p-8 text-center mb-6">
                <div class="text-5xl font-bold {{ $submission->score >= ($totalMarks * 0.5) ? 'text-green-600' : 'text-red-600' }} mb-2">
                    {{ $submission->score }} / {{ $totalMarks }}
                </div>
                <p class="text-gray-500 text-sm mb-1">{{ $quiz->title }}</p>
                <p class="text-gray-400 text-xs">
                    Submitted {{ $submission->submitted_at->diffForHumans() }}
                    {{ $submission->auto_submitted ? '(auto-submitted)' : '' }}
                </p>

                <div class="mt-6 grid grid-cols-3 gap-4 text-center">
                    <div class="bg-green-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-green-600">
                            {{ $submission->answers->where('is_correct', true)->count() }}
                        </p>
                        <p class="text-xs text-gray-500">Correct</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-red-600">
                            {{ $submission->answers->where('is_correct', false)->count() }}
                        </p>
                        <p class="text-xs text-gray-500">Incorrect</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xl font-bold text-gray-700">{{ $quiz->duration_minutes }}m</p>
                        <p class="text-xs text-gray-500">Duration</p>
                    </div>
                </div>
            </div>

            {{-- Per question breakdown --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Question Breakdown</h3>
                @foreach($quiz->questions as $index => $question)
                    @php
                        $submissionAnswer = $submission->answers
                            ->where('question_id', $question->question_id)->first();
                    @endphp
                    <div class="py-4 border-b border-gray-100 last:border-0">
                        <div class="flex items-start gap-3">
                            <span class="{{ $submissionAnswer?->is_correct ? 'text-green-500' : 'text-red-500' }} text-lg mt-0.5">
                                {{ $submissionAnswer?->is_correct ? '✓' : '✗' }}
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $index + 1 }}. {{ $question->content }}</p>
                                @foreach($question->answers as $answer)
                                    <p class="text-xs mt-1 {{ $answer->is_correct ? 'text-green-600 font-semibold' : 'text-gray-400' }}">
                                        {{ $answer->is_correct ? '✓ ' : '  ' }}{{ $answer->content }}
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 text-center">
                <a href="{{ route('dashboard') }}"
                   class="bg-indigo-600 text-white px-6 py-3 rounded-lg text-sm font-medium hover:bg-indigo-700">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
