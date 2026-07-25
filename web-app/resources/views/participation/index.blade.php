<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <main class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">

            {{-- Header — navigation lives in the top navbar, so this page
                 doesn't repeat it in a sidebar --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                <div class="min-w-0">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Assessment Overview</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Your real forum activity and quiz performance across the courses you've joined.
                    </p>
                </div>
                <p class="text-sm text-gray-500 shrink-0">
                    {{ $user->username }} · {{ ucfirst($user->system_role) }}
                </p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6 mb-6">

                {{-- Participation Metrics --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Participation Metrics</h3>
                            <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mt-0.5">
                                Your Real Engagement Data
                            </p>
                        </div>
                    </div>

                    {{-- Bar Chart — real reply/post activity, last 7 days.
                         Per-course participation lives on the dashboard, so
                         it isn't duplicated here. --}}
                    <p class="text-xs text-gray-500 mb-3">Your Activity (Last 7 Days)</p>
                    @php $peak = max(1, $activityByDay->max('count')); @endphp
                    <div class="flex items-end gap-1.5 sm:gap-2 h-24 mb-2">
                        @foreach($activityByDay as $day)
                        <div class="flex-1 rounded-t"
                            style="height: {{ max(6, ($day['count'] / $peak) * 100) }}%; background: {{ $day['count'] > 0 ? '#1e293b' : '#e2e8f0' }}">
                        </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between text-[10px] sm:text-xs text-gray-400">
                        @foreach($activityByDay as $day)
                        <span class="flex-1 text-center">{{ $day['label'] }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Assessment History — real completed quizzes --}}
                <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Assessment History</h3>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 uppercase tracking-wide pb-2 border-b border-gray-100 mb-2">
                        <span>Quiz</span>
                        <span>Score · Vs Peer Avg</span>
                    </div>
                    <div class="space-y-4">
                        @forelse($assessmentHistory as $item)
                        <div class="flex justify-between items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 break-words">{{ $item->title }}</p>
                                <p class="text-xs text-gray-400">Completed {{ $item->submittedAt->format('d M Y') }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-sm font-bold text-gray-900">{{ $item->scorePct }}%</span>
                                @if($item->vsPeerPct !== null)
                                    <span class="text-xs font-semibold {{ $item->vsPeerPct >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }} px-2 py-0.5 rounded-full">
                                        {{ $item->vsPeerPct >= 0 ? '+' : '' }}{{ $item->vsPeerPct }}%
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-400 text-center py-8">No completed quizzes yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Latest real remark a lecturer left when grading, if any --}}
            @if($latestRemark && !str_starts_with($latestRemark->criteria, 'Forum participation + quiz average'))
            <div class="bg-gray-900 text-white rounded-lg p-4 sm:p-6">
                <h3 class="font-semibold text-lg mb-2">Lecturer's Remark</h3>
                <p class="text-gray-400 text-sm italic mb-3 break-words">"{{ $latestRemark->criteria }}"</p>
                <p class="text-xs text-gray-500">
                    Score awarded: {{ $latestRemark->score }} · {{ $latestRemark->created_at->diffForHumans() }}
                </p>
            </div>
            @else
            <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 text-center text-gray-400 text-sm">
                No lecturer remarks yet — these appear here once a lecturer grades your participation.
            </div>
            @endif

        </main>
    </div>
</x-app-layout>
