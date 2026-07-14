<x-app-layout>
    <div class="min-h-screen bg-gray-50">

        {{-- LEFT SIDEBAR + CONTENT --}}
        <div class="flex">

            {{-- LEFT SIDEBAR --}}
            <aside class="w-64 min-h-screen bg-white border-r border-gray-200 p-6 shrink-0">
                <div class="mb-8">
                    <p class="font-bold text-gray-900 text-lg">{{ $user->username }}</p>
                    <p class="text-sm text-gray-500">{{ ucfirst($user->system_role) }}</p>
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        ⊞ Dashboard
                    </a>
                    <a href="{{ route('groups.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        📚 Courses
                    </a>
                    <a href="{{ route('participation.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm bg-blue-50 text-blue-700 font-semibold">
                        📊 Assessment Overview
                    </a>
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        📝 Quiz Center
                    </a>
                </nav>
            </aside>

            {{-- MAIN CONTENT --}}
            <main class="flex-1 p-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Assessment Overview</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Your real forum activity and quiz performance across the courses you've joined.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">

                    {{-- Participation Metrics --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Participation Metrics</h3>
                                <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mt-0.5">
                                    Your Real Engagement Data
                                </p>
                            </div>
                        </div>

                        {{-- Bar Chart — real reply/post activity, last 7 days --}}
                        <p class="text-xs text-gray-500 mb-3">Your Activity (Last 7 Days)</p>
                        @php $peak = max(1, $activityByDay->max('count')); @endphp
                        <div class="flex items-end gap-2 h-24 mb-4">
                            @foreach($activityByDay as $day)
                            <div class="flex-1 rounded-t"
                                style="height: {{ max(6, ($day['count'] / $peak) * 100) }}%; background: {{ $day['count'] > 0 ? '#1e293b' : '#e2e8f0' }}">
                            </div>
                            @endforeach
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mb-4">
                            @foreach($activityByDay as $day)
                            <span>{{ $day['label'] }}</span>
                            @endforeach
                        </div>

                        {{-- Real participation %, same formula the lecturer's grading table uses --}}
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Forum Participation</span>
                                    <span class="font-bold text-gray-900">{{ $participationPct }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-gray-900 h-1.5 rounded-full" style="width: {{ $participationPct }}%"></div>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $replyCount }} {{ Str::plural('reply', $replyCount) }} out of {{ $postCount }} total posts
                                    ({{ min($replyCount, 10) }}/10 replies counted — 10 or more reaches 100%)
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Assessment History — real completed quizzes --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-900">Assessment History</h3>
                        </div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide grid grid-cols-4 pb-2 border-b border-gray-100 mb-2">
                            <span class="col-span-2">Quiz</span>
                            <span>Score</span>
                            <span>Vs Peer Avg</span>
                        </div>
                        <div class="space-y-4">
                            @forelse($assessmentHistory as $item)
                            <div class="grid grid-cols-4 items-center py-2 border-b border-gray-50 last:border-0">
                                <div class="col-span-2">
                                    <p class="text-sm font-semibold text-gray-800">{{ $item->title }}</p>
                                    <p class="text-xs text-gray-400">Completed {{ $item->submittedAt->format('d M Y') }}</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">{{ $item->scorePct }}%</span>
                                @if($item->vsPeerPct !== null)
                                    <span class="text-xs font-semibold {{ $item->vsPeerPct >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50' }} px-2 py-0.5 rounded-full w-fit">
                                        {{ $item->vsPeerPct >= 0 ? '+' : '' }}{{ $item->vsPeerPct }}%
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                            @empty
                            <p class="text-sm text-gray-400 text-center py-8">No completed quizzes yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Latest real remark a lecturer left when grading, if any --}}
                @if($latestRemark)
                <div class="bg-gray-900 text-white rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-2">Lecturer's Remark</h3>
                    <p class="text-gray-400 text-sm italic mb-3">"{{ $latestRemark->criteria }}"</p>
                    <p class="text-xs text-gray-500">
                        Score awarded: {{ $latestRemark->score }} · {{ $latestRemark->created_at->diffForHumans() }}
                    </p>
                </div>
                @else
                <div class="bg-white border border-gray-200 rounded-lg p-6 text-center text-gray-400 text-sm">
                    No lecturer remarks yet — these appear here once a lecturer grades your participation.
                </div>
                @endif

            </main>
        </div>
    </div>
</x-app-layout>
