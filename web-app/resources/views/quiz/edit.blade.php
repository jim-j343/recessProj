<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-900">Edit Draft Quiz</h2>
            <a href="{{ route('quiz.preview', $quiz->quiz_id) }}"
               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 self-start sm:self-auto">
                ← Back to Preview
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            @if($errors->any())
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-amber-50 border-l-4 border-amber-400 px-4 py-3 text-sm text-amber-800 rounded">
                This quiz is still a draft, so it can be changed freely. Once published it becomes read-only,
                because students may already have started it.
            </div>

            <form method="POST" action="{{ route('quiz.update', $quiz->quiz_id) }}" id="quiz-form">
                @csrf
                @method('PUT')

                {{-- General Details --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-5">⚙ General Details</h3>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Quiz Title *</label>
                        <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Course Unit *</label>
                        <input type="text" name="course_name" list="course-options"
                            value="{{ old('course_name', $quiz->course_name) }}" required
                            placeholder="e.g. CS201: Database Systems"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400" />
                        <datalist id="course-options">
                            @foreach($courseNames as $courseName)
                                <option value="{{ $courseName }}">
                            @endforeach
                        </datalist>
                        <p class="text-xs text-gray-400 mt-1">
                            Applies to every group teaching this course — you don't need to be a member of each one.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Start Date &amp; Time *</label>
                            <input type="datetime-local" name="start_time" required
                                value="{{ old('start_time', $quiz->start_time?->format('Y-m-d\TH:i')) }}"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Duration (Minutes) *</label>
                            <input type="number" name="duration" min="1" required
                                value="{{ old('duration', $quiz->duration_minutes) }}"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Student Category</label>
                        <input type="text" name="target" value="{{ old('target', $quiz->target_category) }}"
                            placeholder="e.g. Year 2 Computer Science"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400" />
                    </div>
                </div>

                {{-- Questions --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center mb-5">
                        <h3 class="font-semibold text-gray-900">📝 Questions</h3>
                        <button type="button" onclick="addQuestion()"
                            class="text-sm bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                            + Add Question
                        </button>
                    </div>

                    <div id="questions-container" class="space-y-6">
                        {{-- Existing questions are injected by the script below --}}
                    </div>

                    <p id="no-questions" class="text-sm text-gray-400 text-center py-4 hidden">
                        No questions yet. Click "Add Question" to begin.
                    </p>
                </div>

                {{-- Submit buttons --}}
                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit"
                        class="flex-1 bg-white border border-gray-200 text-gray-700 py-4 rounded-lg font-semibold hover:bg-gray-50">
                        💾 Save Changes
                    </button>
                    <button type="submit" name="publish" value="1"
                        onclick="return confirm('Publish this quiz? Group members will be notified immediately and it can no longer be edited.')"
                        class="flex-1 bg-gray-900 text-white py-4 rounded-lg font-semibold hover:bg-gray-700">
                        🚀 Save &amp; Publish
                    </button>
                </div>

            </form>
        </div>
    </div>

    @php
        // Built here rather than inline in @json(), because Blade's directive
        // parser mis-reads a multi-line array literal inside the parentheses
        $existingQuestions = $quiz->questions->map(function ($q) {
            $correctIndex = $q->answers->search(fn ($a) => $a->is_correct);
            return [
                'text'    => $q->content,
                'answers' => $q->answers->pluck('content')->values(),
                'correct' => $correctIndex === false ? 0 : (int) $correctIndex,
            ];
        })->values();
    @endphp

    <script>
    let questionCount = 0;

    // Same builder as the create form, but able to start from existing data
    function addQuestion(prefill) {
        const container = document.getElementById('questions-container');
        document.getElementById('no-questions').classList.add('hidden');

        const index = questionCount++;
        const text  = prefill ? prefill.text : '';
        const answers = prefill ? prefill.answers : ['', '', '', ''];
        const correct = prefill ? prefill.correct : 0;

        const div = document.createElement('div');
        div.className = 'border border-gray-200 rounded-lg p-5 bg-gray-50';
        div.id = `question-${index}`;
        div.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-semibold text-gray-700">Question ${index + 1}</span>
                <button type="button" onclick="removeQuestion(${index})"
                    class="text-red-500 text-xs hover:underline">Remove</button>
            </div>
            <div class="mb-3">
                <input type="text" name="questions[${index}][text]" required
                    value="${escapeHtml(text)}"
                    placeholder="Enter your question here..."
                    class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gray-400 bg-white" />
            </div>
            <div class="space-y-2 mb-3">
                ${answers.map((a, i) => `
                <div class="flex items-center gap-3">
                    <input type="radio" name="questions[${index}][correct_answer]" value="${i}" ${i === correct ? 'checked' : ''} class="w-4 h-4 accent-indigo-600" />
                    <input type="text" name="questions[${index}][answers][${i}]" required
                        value="${escapeHtml(a)}"
                        placeholder="Answer option ${i + 1}"
                        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gray-400 bg-white" />
                </div>`).join('')}
            </div>
            <p class="text-xs text-gray-400">Select the radio button next to the correct answer.</p>
        `;
        container.appendChild(div);
    }

    function removeQuestion(index) {
        const el = document.getElementById(`question-${index}`);
        if (el) el.remove();
        if (document.getElementById('questions-container').children.length === 0) {
            document.getElementById('no-questions').classList.remove('hidden');
        }
    }

    // Escape existing content so a question containing quotes or markup
    // can't break out of the value attribute
    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML.replace(/"/g, '&quot;');
    }

    // Load the quiz's existing questions into the form
    const existingQuestions = @json($existingQuestions);

    if (existingQuestions.length) {
        existingQuestions.forEach(q => addQuestion(q));
    } else {
        addQuestion();
    }
    </script>
</x-app-layout>
