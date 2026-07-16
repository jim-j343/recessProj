<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $quiz->title }} | ACES</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-indigo-50 min-h-screen overflow-hidden">

    <div class="fixed inset-0 z-40 flex flex-col items-center justify-center p-6">

        {{-- Timer — calm by default, only turns urgent in the final minute --}}
        <div class="fixed top-8 right-8 z-50 text-right">
            <div id="timer-pill" class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-gray-200 shadow-md transition-colors duration-500">
                <span id="timer-icon">⏱</span>
                <span id="countdown" class="text-2xl font-bold text-gray-800 tracking-tight font-mono">
                    {{ str_pad(intdiv($timeLeft, 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad($timeLeft % 60, 2, '0', STR_PAD_LEFT) }}
                </span>
            </div>
            <p id="timer-label" class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1 text-right">Time Remaining</p>
        </div>

        {{-- Quiz form --}}
        <form id="quiz-form" method="POST" action="{{ route('quiz.submit', $quiz->quiz_id) }}">
            @csrf
            <input type="hidden" name="auto_submit" id="auto-submit-flag" value="0">

            <section class="w-full max-w-3xl bg-white shadow-2xl shadow-indigo-100 rounded-2xl border border-gray-100 overflow-hidden flex flex-col">

                {{-- Progress bar --}}
                <div class="w-full h-1.5 bg-gray-100">
                    <div class="h-full bg-indigo-600 transition-all duration-500" id="progress-bar" style="width: 0%"></div>
                </div>

                {{-- Header --}}
                <header class="px-8 py-5 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-gray-900 text-lg">{{ $quiz->title }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Question <span id="current-q">1</span> of {{ $quiz->questions->count() }} · Multiple Choice
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 rounded-full border border-indigo-100">
                        <span class="text-xs">🔒</span>
                        <span class="text-xs font-bold uppercase tracking-wide text-indigo-700">Locked</span>
                    </div>
                </header>

                {{-- Questions --}}
                @foreach($quiz->questions as $qIndex => $question)
                <div class="question-slide px-10 py-10 flex-grow {{ $qIndex > 0 ? 'hidden' : '' }}"
                     data-index="{{ $qIndex }}">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-8 leading-snug">
                        {{ $question->content }}
                    </h3>
                    <div class="space-y-3">
                        @foreach($question->answers as $answer)
                        <label class="flex items-center p-4 border-2 border-gray-100 rounded-xl cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/50 transition-all has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                            <input type="radio"
                                name="answers[{{ $question->question_id }}]"
                                value="{{ $answer->answer_id }}"
                                class="w-5 h-5 accent-indigo-600" />
                            <span class="ml-4 text-sm text-gray-700">{{ $answer->content }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach

                {{-- Footer --}}
                <footer class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" onclick="prevQuestion()"
                        class="px-4 py-2.5 text-sm text-gray-500 font-semibold rounded-lg hover:bg-gray-100 transition-colors" id="prev-btn">
                        ← Previous
                    </button>
                    <div class="flex gap-3">
                        <button type="button" onclick="nextQuestion()" id="next-btn"
                            class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                            Next →
                        </button>
                        <button type="submit" id="submit-btn"
                            class="hidden px-6 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm"
                            onclick="return confirm('Submit your quiz? This cannot be undone.')">
                            ✅ Submit Quiz
                        </button>
                    </div>
                </footer>

            </section>
        </form>

        <p class="mt-5 text-xs text-gray-400">
            👁 Navigation disabled during assessment
        </p>
    </div>

    {{-- Lock toast --}}
    <div id="lock-toast"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white px-6 py-2
               rounded-full text-xs font-semibold opacity-0 transition-opacity duration-300">
        🔒 Navigation is disabled during the assessment
    </div>

    <script>
        const totalQuestions = {{ $quiz->questions->count() }};
        // Cast defensively on the JS side too, so this can never regress
        // even if the server-side value isn't a clean int for any reason
        let timeLeft = Math.floor({{ $timeLeft }});
        let currentQuestion = 0;

        const countdownEl = document.getElementById('countdown');
        const timerPill = document.getElementById('timer-pill');
        const timerIcon = document.getElementById('timer-icon');
        const timerLabel = document.getElementById('timer-label');

        function renderTimer() {
            const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const s = String(Math.floor(timeLeft % 60)).padStart(2, '0');
            countdownEl.textContent = `${m}:${s}`;

            // Calm by default — only turns urgent in the final 60 seconds,
            // instead of glowing red for the entire quiz
            if (timeLeft <= 60) {
                timerPill.classList.add('border-red-300', 'bg-red-50');
                countdownEl.classList.add('text-red-600', 'animate-pulse');
                timerLabel.classList.add('text-red-500');
                timerIcon.textContent = '⚠';
            }
        }

        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('auto-submit-flag').value = '1';
                document.getElementById('quiz-form').submit();
                return;
            }
            timeLeft--;
            renderTimer();
        }, 1000);

        function updateView() {
            document.querySelectorAll('.question-slide').forEach((el, i) => {
                el.classList.toggle('hidden', i !== currentQuestion);
            });
            document.getElementById('current-q').textContent = currentQuestion + 1;
            document.getElementById('progress-bar').style.width =
                ((currentQuestion + 1) / totalQuestions * 100) + '%';

            document.getElementById('prev-btn').style.opacity = currentQuestion === 0 ? '0.3' : '1';

            const isLast = currentQuestion === totalQuestions - 1;
            document.getElementById('next-btn').classList.toggle('hidden', isLast);
            document.getElementById('submit-btn').classList.toggle('hidden', !isLast);
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) { currentQuestion++; updateView(); }
        }

        function prevQuestion() {
            if (currentQuestion > 0) { currentQuestion--; updateView(); }
        }

        // Prevent tab/escape
        const toast = document.getElementById('lock-toast');
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' || e.key === 'Escape') {
                e.preventDefault();
                toast.classList.remove('opacity-0');
                setTimeout(() => toast.classList.add('opacity-0'), 3000);
            }
        });
        document.addEventListener('contextmenu', e => e.preventDefault());

        renderTimer();
        updateView();
    </script>
</body>
</html>
