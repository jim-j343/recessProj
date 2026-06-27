<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">🧪 Create Quiz</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="POST" action="/quiz/store">
                @csrf

                {{-- SECTION 1: Basic Info --}}
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-2">
                        📋 Quiz Details
                    </h3>

                    {{-- Quiz Title --}}
                    <div>
                        <x-input-label for="title" :value="__('Quiz Title')" />
                        <x-text-input id="title" name="title" type="text"
                            class="block mt-1 w-full"
                            placeholder="e.g. Week 3 Quiz"
                            :value="old('title')" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    {{-- Target Group/Year --}}
                    <div>
                        <x-input-label for="target" :value="__('Target Audience')" />
                        <select id="target" name="target" required
                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                   focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="" disabled selected>Select who takes this quiz</option>
                            <option value="all" {{ old('target') == 'all' ? 'selected' : '' }}>
                                All Students
                            </option>
                            <option value="year1" {{ old('target') == 'year1' ? 'selected' : '' }}>
                                Year 1 Students
                            </option>
                            <option value="year2" {{ old('target') == 'year2' ? 'selected' : '' }}>
                                Year 2 Students
                            </option>
                            <option value="year3" {{ old('target') == 'year3' ? 'selected' : '' }}>
                                Year 3 Students
                            </option>
                        </select>
                        <x-input-error :messages="$errors->get('target')" class="mt-2" />
                    </div>

                    {{-- Start Date & Time --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_time" :value="__('Start Date & Time')" />
                            <x-text-input id="start_time" name="start_time" type="datetime-local"
                                class="block mt-1 w-full"
                                :value="old('start_time')" required />
                            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="duration" :value="__('Duration (minutes)')" />
                            <x-text-input id="duration" name="duration" type="number"
                                class="block mt-1 w-full"
                                placeholder="e.g. 30"
                                min="5" max="180"
                                :value="old('duration')" required />
                            <x-input-error :messages="$errors->get('duration')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- SECTION 2: Questions --}}
                <div class="bg-white rounded-lg shadow p-6 space-y-6" id="questions-container">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">
                            ❓ Questions
                        </h3>
                        <button type="button" onclick="addQuestion()"
                            class="text-xs bg-indigo-50 text-indigo-600 border border-indigo-300
                                   px-3 py-1.5 rounded-lg hover:bg-indigo-100">
                            + Add Question
                        </button>
                    </div>

                    {{-- Question 1 (default) --}}
                    <div class="question-block border border-gray-200 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="text-sm font-semibold text-gray-700">Question 1</p>
                            <button type="button" onclick="removeQuestion(this)"
                                class="text-xs text-red-400 hover:text-red-600">
                                ✕ Remove
                            </button>
                        </div>

                        <div>
                            <x-input-label :value="__('Question Text')" />
                            <x-text-input name="questions[0][text]" type="text"
                                class="block mt-1 w-full"
                                placeholder="Enter your question here" required />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            @foreach(['A', 'B', 'C', 'D'] as $opt)
                            <div>
                                <x-input-label :value="'Option ' . $opt" />
                                <x-text-input name="questions[0][options][{{ $opt }}]"
                                    type="text" class="block mt-1 w-full"
                                    placeholder="Option {{ $opt }}" required />
                            </div>
                            @endforeach
                        </div>

                        <div>
                            <x-input-label :value="__('Correct Answer')" />
                            <select name="questions[0][answer]"
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                       focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                required>
                                <option value="">Select correct option</option>
                                @foreach(['A', 'B', 'C', 'D'] as $opt)
                                <option value="{{ $opt }}">Option {{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>

                {{-- SECTION 3: Announcement Preview --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm font-semibold text-yellow-700 mb-1">
                        📢 Announcement Preview
                    </p>
                    <p class="text-sm text-gray-600" id="preview-text">
                        Fill in the details above to see the announcement preview.
                    </p>
                </div>

                {{-- Submit --}}
                <div class="flex justify-end gap-3">
                    <a href="{{ route('dashboard') }}"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300
                               rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm
                               font-semibold hover:bg-indigo-700">
                        Save & Publish Quiz
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- Script: Add/Remove questions + Preview --}}
    <script>
        let questionCount = 1;

        function addQuestion() {
            const container = document.getElementById('questions-container');
            const idx = questionCount;
            const block = document.createElement('div');
            block.className = 'question-block border border-gray-200 rounded-lg p-4 space-y-3';
            block.innerHTML = `
                <div class="flex justify-between items-center">
                    <p class="text-sm font-semibold text-gray-700">Question ${idx + 1}</p>
                    <button type="button" onclick="removeQuestion(this)"
                        class="text-xs text-red-400 hover:text-red-600">✕ Remove</button>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Question Text</label>
                    <input type="text" name="questions[${idx}][text]"
                        placeholder="Enter your question here" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    ${['A','B','C','D'].map(opt => `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Option ${opt}</label>
                        <input type="text" name="questions[${idx}][options][${opt}]"
                            placeholder="Option ${opt}" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>`).join('')}
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Correct Answer</label>
                    <select name="questions[${idx}][answer]" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">Select correct option</option>
                        ${['A','B','C','D'].map(opt =>
                            `<option value="${opt}">Option ${opt}</option>`).join('')}
                    </select>
                </div>
            `;
            container.appendChild(block);
            questionCount++;
        }

        function removeQuestion(btn) {
            const block = btn.closest('.question-block');
            if (document.querySelectorAll('.question-block').length > 1) {
                block.remove();
            } else {
                alert('A quiz must have at least one question.');
            }
        }

        // Live announcement preview
        const titleEl = document.getElementById('title');
        const targetEl = document.getElementById('target');
        const startEl = document.getElementById('start_time');
        const durationEl = document.getElementById('duration');
        const previewEl = document.getElementById('preview-text');

        function updatePreview() {
            const title = titleEl.value || 'Untitled Quiz';
            const target = targetEl.options[targetEl.selectedIndex]?.text || 'Students';
            const start = startEl.value
                ? new Date(startEl.value).toLocaleString('en-UG', {
                    dateStyle: 'medium', timeStyle: 'short'
                  })
                : 'TBD';
            const duration = durationEl.value || '?';
            previewEl.innerHTML = `A quiz titled <strong>"${title}"</strong> has been scheduled
                for <strong>${target}</strong> on <strong>${start}</strong>.
                Duration: <strong>${duration} minutes</strong>.`;
        }

        [titleEl, targetEl, startEl, durationEl].forEach(el =>
            el.addEventListener('input', updatePreview)
        );
    </script>

</x-app-layout>