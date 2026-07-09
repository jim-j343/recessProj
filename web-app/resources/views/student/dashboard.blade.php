<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Welcome back, {{ auth()->user()->username }}
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Here's where your progress, grades, and quizzes stand today.</p>
            </div>
            <a href="{{ route('forum.index') }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                Go to Forum
            </a>
        </div>
    </x-slot>

    <div class="flex min-h-screen bg-gray-50">
        <!-- LEFT SIDEBAR -->
        <div class="w-64 bg-white border-r border-gray-200 p-6 flex flex-col gap-4 shrink-0">
            <h2 class="text-xl font-bold text-gray-800">Student Portal</h2>
            <nav class="flex flex-col gap-2">
                <a href="{{ route('forum.index') }}" class="bg-gray-100 text-gray-900 px-4 py-2 rounded font-medium">Forums</a>
                <a href="#" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">My Quizzes</a>
                <a href="{{ route('participation.index') }}" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">My Progress</a>
            </nav>

            <!-- QUICK STATS (uses existing group/topic/post counters) -->
            <div class="mt-4 pt-4 border-t border-gray-100 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Groups joined</span>
                    <span class="font-semibold text-gray-800">{{ $groupCount ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Topics in reach</span>
                    <span class="font-semibold text-gray-800">{{ $topicCount ?? 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Posts made</span>
                    <span class="font-semibold text-gray-800">{{ $postCount ?? 0 }}</span>
                </div>
            </div>
        </div>

        <!-- MIDDLE PANE -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">

            <!-- QUIZ NOTICE (Recess Requirement #10) -->
            @if($activeQuiz ?? null)
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded shadow-sm">
                    <div class="flex justify-between items-center gap-4">
                        <div>
                            <h3 class="font-bold text-amber-800">⚠️ Live Quiz In Progress</h3>
                            <p class="text-sm text-amber-700">
                                "{{ $activeQuiz->title }}" is open now ({{ $activeQuiz->duration_minutes }} min). It will auto-submit when time expires.
                            </p>
                        </div>
                        <a href="{{ route('quiz.show', $activeQuiz->quiz_id) }}"
                           class="bg-amber-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-amber-700 whitespace-nowrap">
                            Enter Quiz
                        </a>
                    </div>
                </div>
            @elseif($upcomingQuiz ?? null)
                <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded shadow-sm">
                    <div class="flex justify-between items-center gap-4">
                        <div>
                            <h3 class="font-bold text-indigo-800">🔔 Upcoming Quiz</h3>
                            <p class="text-sm text-indigo-700">
                                "{{ $upcomingQuiz->title }}" opens {{ $upcomingQuiz->start_time->format('D, d M Y · H:i') }} ({{ $upcomingQuiz->duration_minutes }} min).
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-3 py-1.5 rounded-full whitespace-nowrap">
                            Starts {{ $upcomingQuiz->start_time->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border-l-4 border-gray-300 p-4 rounded shadow-sm">
                    <h3 class="font-bold text-gray-700">✅ No Quizzes Pending</h3>
                    <p class="text-sm text-gray-500">You're all caught up — nothing scheduled for your groups right now.</p>
                </div>
            @endif

            <!-- PROGRESS & GENERAL ASSESSMENT -->
            <div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Progress & General Assessment</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                    <!-- Overall assessment -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Overall Assessment</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            {{ $overallScore !== null ? $overallScore.'%' : '—' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Blend of quiz grades &amp; participation</p>
                    </div>

                    <!-- Quiz progress -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Quiz Progress</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $quizzesCompleted ?? 0 }}/{{ $quizzesTotal ?? 0 }}</p>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3">
                            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $quizProgress ?? 0 }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $quizProgress ?? 0 }}% of published quizzes completed</p>
                    </div>

                    <!-- Participation -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Participation Score</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">
                            {{ $participationAvg !== null ? $participationAvg : '—' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $participationTotal ?? 0 }} total pts across all criteria
                        </p>
                    </div>

                    <!-- Community standing -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                        <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Community Standing</p>
                        @if(($latestWarning ?? null) && !$latestWarning->is_heeded)
                            <span class="inline-block mt-2 text-sm font-bold text-amber-700 bg-amber-100 px-3 py-1 rounded-full">
                                Warning #{{ $latestWarning->warning_number }}
                            </span>
                            <p class="text-xs text-gray-400 mt-2">Comply before {{ $latestWarning->deadline?->format('d M Y') ?? 'the deadline' }}</p>
                        @else
                            <span class="inline-block mt-2 text-sm font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">
                                Good Standing
                            </span>
                            <p class="text-xs text-gray-400 mt-2">No active warnings on your account</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- GRADES -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Grades</h3>
                        <p class="text-xs text-gray-400">Your most recent quiz results</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                        Average: {{ $averageGrade !== null ? $averageGrade.'%' : '—' }}
                    </span>
                </div>

                @forelse($gradedSubmissions ?? [] as $submission)
                    <div class="flex justify-between items-center py-3 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $submission->quiz->title ?? 'Quiz' }}</p>
                            <p class="text-xs text-gray-400">
                                Submitted {{ $submission->submitted_at?->format('d M Y, H:i') }}
                                @if($submission->auto_submitted)
                                    <span class="text-amber-600 font-semibold">· auto-submitted</span>
                                @endif
                            </p>
                        </div>
                        <span class="text-sm font-bold text-gray-900">{{ $submission->score }}%</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No graded quizzes yet — results will appear here once a quiz is marked.</p>
                @endforelse
            </div>

            <!-- TOPIC ISOLATION AND PDF EXPORT (Recess Requirement #6) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Topic: Linear Data Chains</h3>
                        <p class="text-xs text-gray-400">Viewing isolated chats for this topic only</p>
                    </div>
                    <button class="bg-gray-900 text-white px-4 py-2 rounded text-xs font-semibold uppercase tracking-wide">
                        Export Thread to PDF
                    </button>
                </div>
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="font-semibold text-sm block">Tony Stark:</span>
                        <p class="text-sm text-gray-600">Has anyone optimized the offline synchronization task?</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDEBAR (Recess Requirement #11 & #12) -->
        <div class="w-80 bg-white border-l border-gray-200 p-6 flex flex-col gap-6 shrink-0">
            <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
                <h4 class="font-bold text-indigo-900 text-sm mb-1">💡 Recommended for You (ML)</h4>
                <p class="text-xs text-indigo-700 mb-2">Based on past engagement profiles:</p>
                <a href="#" class="text-xs font-semibold text-indigo-600 underline block"># Advanced Data Normalization</a>
            </div>

            <!-- RECENT ACTIVITY -->
            <div>
                <h4 class="font-bold text-gray-800 text-sm mb-3">Recent Activity</h4>
                <div class="space-y-3">
                    @forelse($recentActivity ?? [] as $activity)
                        <div class="text-xs">
                            <p class="text-gray-700 font-medium capitalize">{{ str_replace('_', ' ', $activity->action_type) }}</p>
                            <p class="text-gray-400">{{ $activity->logged_at?->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No activity logged yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <h4 class="font-bold text-gray-800 text-sm mb-2">Forward Thread</h4>
                <div class="flex gap-2">
                    <button class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded font-medium">Share to Twitter</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
