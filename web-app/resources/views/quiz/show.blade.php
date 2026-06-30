<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Quiz | Smart Discussion Forum</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .blur-backdrop {
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
        }
        .timer-glow {
            text-shadow: 0 0 10px rgba(186, 26, 26, 0.3);
        }
        .quiz-shadow {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden">

    {{-- BLURRED BACKGROUND: Bento Grid --}}
    <div class="fixed inset-0 p-10 grid grid-cols-12 gap-6 opacity-40 blur-xl pointer-events-none select-none">
        <div class="col-span-full h-16 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-8 h-64 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-4 h-64 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-3 h-40 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-3 h-40 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-3 h-40 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-3 h-40 bg-white border border-gray-200 rounded-lg"></div>
        <div class="col-span-full h-48 bg-white border border-gray-200 rounded-lg"></div>
    </div>

    {{-- OVERLAY --}}
    <div class="fixed inset-0 bg-white/60 blur-backdrop z-40 flex flex-col items-center justify-center p-6">

        {{-- TIMER --}}
        <div class="fixed top-8 right-8 z-50 text-right">
            <div class="flex items-center gap-2 bg-white/80 px-4 py-2 rounded-full border border-red-200 quiz-shadow">
                <span class="text-red-600">⏱</span>
                <span id="countdown"
                    class="text-3xl font-bold text-red-600 tracking-tight timer-glow font-mono">
                    24:59
                </span>
            </div>
            <p class="text-red-600 text-xs font-bold uppercase tracking-widest mt-1">
                Time Remaining
            </p>
        </div>

        {{-- QUIZ MODAL --}}
        <section class="w-full max-w-xl bg-white quiz-shadow rounded-xl border border-gray-200 overflow-hidden flex flex-col">

            {{-- Progress Bar --}}
            <div class="w-full h-1 bg-gray-100">
                <div class="h-full bg-gray-900 transition-all duration-1000" id="progress-bar" style="width: 40%"></div>
            </div>

            {{-- Modal Header --}}
            <header class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                <div>
                    <h2 class="font-semibold text-gray-900">Advanced Web Systems</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Question <span id="current-q">12</span> of 30 • Multiple Choice
                    </p>
                </div>
                <div class="flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full border border-gray-200">
                    <span class="text-xs">🔒</span>
                    <span class="text-xs font-bold uppercase tracking-wide text-gray-700">Locked</span>
                </div>
            </header>

            {{-- Question Body --}}
            <div class="px-8 py-8 flex-grow">
                <h3 class="text-xl font-semibold text-gray-900 mb-6 leading-snug">
                    What is the primary benefit of using a Virtual DOM in modern web frameworks?
                </h3>

                {{-- Options --}}
                <div class="space-y-3" id="options-container">
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition-all">
                        <input type="radio" name="answer" value="A" class="w-5 h-5 accent-gray-900" />
                        <span class="ml-4 text-sm text-gray-700">Faster initial render</span>
                    </label>
                    <label class="flex items-center p-4 border-2 border-gray-900 rounded-lg cursor-pointer bg-gray-50">
                        <input type="radio" name="answer" value="B" checked class="w-5 h-5 accent-gray-900" />
                        <span class="ml-4 text-sm text-gray-900 font-semibold">
                            Efficient updates by minimizing direct DOM manipulation
                        </span>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition-all">
                        <input type="radio" name="answer" value="C" class="w-5 h-5 accent-gray-900" />
                        <span class="ml-4 text-sm text-gray-700">Automatic SEO optimization</span>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:border-gray-900 transition-all">
                        <input type="radio" name="answer" value="D" class="w-5 h-5 accent-gray-900" />
                        <span class="ml-4 text-sm text-gray-700">Native browser support</span>
                    </label>
                </div>
            </div>

            {{-- Modal Footer --}}
            <footer class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button class="flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-100 transition-colors">
                    🚩 Review Later
                </button>
                <div class="flex gap-3">
                    <button onclick="prevQuestion()"
                        class="px-4 py-2 text-sm text-gray-400 font-semibold rounded-lg opacity-50 cursor-not-allowed">
                        Previous
                    </button>
                    <button onclick="nextQuestion()" id="next-btn"
                        class="flex items-center gap-2 px-5 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-all active:scale-95">
                        Next Question →
                    </button>
                    <button onclick="submitQuiz()" id="submit-btn"
                        class="hidden items-center gap-2 px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-all active:scale-95">
                        ✅ Submit Quiz
                    </button>
                </div>
            </footer>

        </section>

        {{-- PROCTORING BAR --}}
        <div class="mt-6 flex flex-col items-center gap-2">
            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <span>👁</span>
                <span>Proctoring Active: Screen Recording & Tab Locking Enabled</span>
            </div>
            <div class="w-48 h-1 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gray-400 rounded-full" id="heartbeat" style="width: 30%"></div>
            </div>
        </div>

    </div>

    {{-- Navigation lock toast --}}
    <div id="lock-toast"
        class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white px-6 py-2
               rounded-full text-xs font-semibold flex items-center gap-2 quiz-shadow
               opacity-0 transition-opacity duration-300">
        🔒 Navigation is disabled during the assessment
    </div>

    <script>
        // Timer
        let timeLeft = 24 * 60 + 59;
        const countdownEl = document.getElementById('countdown');
        const interval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(interval);
                submitQuiz();
                return;
            }
            timeLeft--;
            const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const s = String(timeLeft % 60).padStart(2, '0');
            countdownEl.textContent = `${m}:${s}`;
            if (timeLeft < 300) countdownEl.classList.add('animate-pulse');
        }, 1000);

        // Question navigation
        let currentQuestion = 12;
        const totalQuestions = 30;

        function updateProgress() {
            document.getElementById('current-q').textContent = currentQuestion;
            document.getElementById('progress-bar').style.width =
                ((currentQuestion / totalQuestions) * 100) + '%';

            // Toggle Next/Submit buttons on last question
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            if (currentQuestion === totalQuestions) {
                nextBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
                submitBtn.classList.add('flex');
            } else {
                nextBtn.classList.remove('hidden');
                nextBtn.classList.add('flex');
                submitBtn.classList.add('hidden');
                submitBtn.classList.remove('flex');
            }
        }

        function submitQuiz() {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = '✅ Submitted';
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            // Later: send answers to backend via fetch/axios
            alert('Quiz submitted! Your answers have been recorded.');
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions) { currentQuestion++; updateProgress(); }
        }

        function prevQuestion() {
            if (currentQuestion > 1) { currentQuestion--; updateProgress(); }
        }

        // Prevent tab navigation
        const toast = document.getElementById('lock-toast');
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' || e.key === 'Escape') {
                e.preventDefault();
                toast.classList.remove('opacity-0');
                setTimeout(() => toast.classList.add('opacity-0'), 3000);
            }
        });
        document.addEventListener('contextmenu', e => e.preventDefault());

        // Heartbeat animation
        let dir = 1, w = 30;
        const hb = document.getElementById('heartbeat');
        setInterval(() => {
            w += 5 * dir;
            if (w > 90 || w < 10) dir *= -1;
            hb.style.width = w + '%';
        }, 150);
    </script>

</body>
</html>