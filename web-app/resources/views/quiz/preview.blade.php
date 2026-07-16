<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-900">Quiz Preview</h2>
            <a href="{{ route('lecturer.dashboard') }}"
               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- Quiz meta --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $quiz->title }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $quiz->group->name ?? 'Unknown group' }}
                            @if($quiz->group?->course_name)
                                · {{ $quiz->group->course_name }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs px-2 py-1 rounded-full font-medium {{ $quiz->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $quiz->is_published ? 'Published' : 'Draft' }}
                        </span>
                        @unless($quiz->is_published)
                            <form method="POST" action="{{ route('quiz.publish', $quiz->quiz_id) }}"
                                  onsubmit="return confirm('Publish this quiz? Group members will be notified immediately.')">
                                @csrf
                                <button type="submit"
                                    class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-xs font-semibold hover:bg-indigo-700">
                                    Publish Quiz
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Opens</p>
                        <p class="text-gray-800 font-medium">{{ $quiz->start_time->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Duration</p>
                        <p class="text-gray-800 font-medium">{{ $quiz->duration_minutes }} mins</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wide">Total Marks</p>
                        <p class="text-gray-800 font-medium">{{ $totalMarks }}</p>
                    </div>
                </div>
            </div>

            {{-- Completion stats --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Completion</h3>
                @if($avgPct !== null)
                    <p class="text-2xl font-bold text-gray-900">{{ $avgPct }}%</p>
                    <p class="text-xs text-gray-400 mt-1">
                        Average across {{ $completedSubmissions->count() }} completed {{ Str::plural('submission', $completedSubmissions->count()) }}
                    </p>
                @else
                    <p class="text-sm text-gray-400">No completed submissions yet.</p>
                @endif
            </div>

            {{-- Questions --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-5">Questions ({{ $quiz->questions->count() }})</h3>
                <div class="space-y-6">
                    @foreach($quiz->questions as $index => $question)
                        <div class="{{ !$loop->last ? 'pb-6 border-b border-gray-100' : '' }}">
                            <p class="text-sm font-semibold text-gray-800 mb-3">
                                {{ $index + 1 }}. {{ $question->content }}
                                <span class="text-xs text-gray-400 font-normal">({{ $question->marks }} {{ Str::plural('mark', $question->marks) }})</span>
                            </p>
                            <div class="space-y-1.5 pl-4">
                                @foreach($question->answers as $answer)
                                    <div class="flex items-center gap-2 text-sm {{ $answer->is_correct ? 'text-green-700 font-medium' : 'text-gray-600' }}">
                                        <span>{{ $answer->is_correct ? '✓' : '○' }}</span>
                                        <span>{{ $answer->content }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
