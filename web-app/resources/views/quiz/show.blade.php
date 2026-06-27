<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Quiz | Smart Discussion Forum</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    {{-- TOP BAR: Quiz title + Timer --}}
    <div class="bg-indigo-700 text-white px-8 py-4 flex justify-between items-center shadow">
        <div>
            <h1 class="text-lg font-bold">📝 Week 3 Quiz</h1>
            <p class="text-xs text-indigo-200">Answer all questions before time runs out</p>
        </div>

        {{-- Countdown Timer --}}
        <div class="text-right">
            <p class="text-xs text-indigo-200 mb-1">Time Remaining</p>
            <p id="timer" class="text-3xl font-mono font-bold text-yellow-300">
                30:00
            </p>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-8">

            {{-- Progress --}}
            <div class="flex justify-between items-center mb-6">
                <p class="text-sm text-gray-500">Question <span id="current-q">1</span> of 10</p>
                <div class="w-2/3 bg-gray-200 rounded-full h-2">
                    <div id="progress-bar"
                         class="bg-indigo-500 h-2 rounded-full transition-all"
                         style="width: 10%"></div>
                </div>
            </div>

            {{-- Question --}}
            <h2 class="text-lg font-semibold text-gray-800 mb-6" id="question-text">
                What does PHP stand for?
            </h2>

            {{-- Answer Options --}}
            <div class="space-y-3" id="options-container">
                @foreach([
                    'A' => 'Personal Home Page',
                    'B' => 'PHP: Hypertext Preprocessor',
                    'C' => 'Private Hypertext Processor',
                    'D' => 'Personal Hypertext Protocol'
                ] as $key => $option)
                <label class="flex items-center gap-3 p-4 border border-gray-200 rounded-lg
                              hover:bg-indigo-50 hover:border-indigo-300 cursor-pointer transition">
                    <input type="radio" name="answer" value="{{ $key }}"
                           class="accent-indigo-600" />
                    <span class="text-sm text-gray-700">
                        <strong>{{ $key }}.</strong> {{ $option }}
                    </span>
                </label>
                @endforeach
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex justify-between items-center mt-8">
                <button onclick="prevQuestion()"
                    class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2
                           border border-gray-300 rounded-lg hover:bg-gray-50">
                    ← Previous
                </button>

                <button onclick="nextQuestion()"
                    class="bg-indigo-600 text-white text-sm px-6 py-2
                           rounded-lg hover:bg-indigo-700 font-medium">
                    Next →
                </button>
            </div>

            {{-- Submit button (hidden until last question) --}}
            <div class="mt-4 text-center hidden" id="submit-container">
                <form method="POST" action="/quiz/{{ $quiz->id ?? 1 }}/submit">
                    @csrf
                    <button type="submit"
                        class="bg-green-600 text-white px-8 py-3 rounded-lg
                               font-semibold hover:bg-green-700 w-full">
                        ✅ Submit Quiz
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- FOOTER: Question number pills --}}
    <div class="bg-white border-t px-8 py-3 flex gap-2 flex-wrap justify-center">
        @for($i = 1; $i <= 10; $i++)
        <button onclick="goToQuestion({{ $i }})"
            class="w-8 h-8 rounded-full text-xs font-medium border
                   question-pill
                   {{ $i === 1 ? 'bg-indigo-600 text-white border-indigo-600'
                               : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ $i }}
        </button>
        @endfor
    </div>

    {{-- TIMER + NAVIGATION SCRIPT --}}
    <script>
        // ── Timer ──────────────────────────────────────────
        let totalSeconds = 30 * 60; // 30 minutes

        const timerEl = document.getElementById('timer');

        const countdown = setInterval(() => {
            if (totalSeconds <= 0) {
                clearInterval(countdown);
                timerEl.textContent = '00:00';
                timerEl.classList.add('text-red-400');
                document.getElementById('submit-container').classList.remove('hidden');
                return;
            }
            totalSeconds--;
            const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const s = String(totalSeconds % 60).padStart(2, '0');
            timerEl.textContent = `${m}:${s}`;

            // Turn red when under 5 minutes
            if (totalSeconds < 300) {
                timerEl.classList.add('text-red-400');
                timerEl.classList.remove('text-yellow-300');
            }
        }, 1000);

        // ── Question Navigation ────────────────────────────
        let currentQuestion = 1;
        const totalQuestions = 10;

        function updateProgress() {
            document.getElementById('current-q').textContent = currentQuestion;
            const pct = (currentQuestion / totalQuestions) * 100;
            document.getElementById('progress-bar').style.width = pct + '%';

            // Show submit on last question
            const submitEl = document.getElementById('submit-container');
            if (currentQuestion === totalQuestions) {
                submitEl.classList.remove('hidden');
            } else {
                submitEl.classList.add('hidden');
            }

            // Update pill styles
            document.querySelectorAll('.question-pill').forEach((pill, i) => {
                if (i + 1 === currentQuestion) {
                    pill.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600');
                    pill.classList.remove('bg-white', 'text-gray-600', 'border-gray-300');
                } else {
                    pill.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600');
                    pill.classList.add('bg-white', 'text-gray-600', 'border-gray-300');
                }
            });
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                currentQuestion++;
                updateProgress();
            }
        }

        function prevQuestion() {
            if (currentQuestion > 1) {
                currentQuestion--;
                updateProgress();
            }
        }

        function goToQuestion(n) {
            currentQuestion = n;
            updateProgress();
        }
    </script>

</body>
</html>