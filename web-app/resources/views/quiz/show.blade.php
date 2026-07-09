<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ $quiz->title }} | Smart Discussion Forum</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .timer-glow { text-shadow: 0 0 10px rgba(186, 26, 26, 0.3); }
        .quiz-shadow { box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden">

    {{-- Blurred background --}}
    <div class="fixed inset-0 p-10 grid grid-cols-12 gap-6 opacity-40 blur-xl pointer-events-none select-none">
        <div class="col-span-full h-16 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-8 h-64 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-4 h-64 bg-white border border-gray-200 rounded-lg"></div>
    </div>

    <div class="fixed inset-0 bg-white/60 z-40 flex flex-col items-center justify-center p-6">

        {{-- Timer --}}
        <div class="fixed top-8 right-8 z-50 text-right">
            <div class="flex items-center gap-2 bg-white/80 px-4 py-2 rounded-full border border-red-200 quiz-shadow">
                <span class="text-red-600">⏱</span>
                <span id="countdown" class="text-3xl font-bold text-red-600 tracking-tight timer-glow font-mono">
                    {{ str_pad(floor($timeLeft / 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad($timeLeft % 60, 2, '0', STR_PAD_LEFT) }}
                </span>
            </div>
            <p class="text-red-600 text-xs font-bold uppercase tracking-widest mt-1">Time Remaining</p>
        </div>

        {{-- Quiz form --}}
        <form id="quiz-form" method="POST" action="{{ route('quiz.submit', $quiz->quiz_id) }}">
            @csrf
            <input type="hidden" name="auto_submit" id="auto-submit-flag" value="0">

            <section class="w-full max-w-xl bg-white quiz-shadow rounded-xl border border-gray-200 overflow-hidden flex flex-col">

                {{-- Progress bar --}}
                <div class="w-full h-1 bg-gray-100">
                    <div class="h-full bg-gray-900 transition-all duration-500" id="progress-bar" style="width: 0%"></div>
                </div>

                {{-- Header --}}
                <header class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ $quiz->title }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Question <span id="current-q">1</span> of {{ $quiz->questions->count() }} • Multiple Choice
                        </p>
                    </div>
                    <div class="flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full border border-gray-200">
                        <span class="text-xs">🔒</span>
                        <span class="text-xs font-bold uppercase tracking-wide text-gray-700">Locked</span>
                    </div>
                </header>

                {{-- Questions --}}
                @foreach($quiz->questions as $qIndex => $question)
                <div class="question-slide px-8 py-8 flex-grow {{ $qIndex > 0 ? 'hidden' : '' }}"
                     data-index="{{ $qIndex }}">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6 leading-snug">
                        {{ $question->content }}
                    </h3>
                    <div class="space-y-3">
                        @foreach($question->answers as $answer)
                        <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition-all">
                            <input type="radio"
                                name="answers[{{ $question->question_id }}]"
                                value="{{ $answer->answer_id }}"
                                class="w-5 h-5 accent-gray-900" />
                            <span class="ml-4 text-sm text-gray-700">{{ $answer->content }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach

                {{-- Footer --}}
                <footer class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <button type="button" onclick="prevQuestion()"
                        class="px-4 py-2 text-sm text-gray-500 font-semibold rounded-lg hover:bg-gray-100" id="prev-btn">
                        ← Previous
                    </button>
                    <div class="flex gap-3">
                        <button type="button" onclick="nextQuestion()" id="next-btn"
                            class="px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700">
                            Next →
                        </button>
                        <button type="submit" id="submit-btn"
                            class="hidden px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700"
                            onclick="return confirm('Submit your quiz? This cannot be undone.')">
                            ✅ Submit Quiz
                        </button>
                    </div>
                </footer>

            </section>
        </form>

        <p class="mt-4 text-xs text-gray-400">
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
        const timeLeft_seconds = {{ $timeLeft }};
        let currentQuestion = 0;
        let timeLeft = timeLeft_seconds;

        // Timer
        const countdownEl = document.getElementById('countdown');
        const timerInterval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                document.getElementById('auto-submit-flag').value = '1';
                document.getElementById('quiz-form').submit();
                return;
            }
            timeLeft--;
            const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const s = String(timeLeft % 60).padStart(2, '0');
            countdownEl.textContent = `${m}:${s}`;
            if (timeLeft < 300) countdownEl.classList.add('animate-pulse');
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

        updateView();
    </script>
</body>
</html>
