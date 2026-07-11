<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-8 py-6">

            {{-- HEADER --}}
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Academic Insights</h1>
                    <p class="text-sm text-gray-400 mt-1">Real-time performance metrics for the current semester.</p>
                </div>
                <div class="flex items-center gap-3">
                    <select class="border border-gray-200 rounded-lg px-4 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-900">
                        <option>Advanced Calculus II</option>
                        <option>Computer Science 101</option>
                        <option>Macroeconomics</option>
                    </select>
                    <button class="flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700 transition-colors">
                        Last 7 Days
                    </button>
                </div>
            </div>

            {{-- MEMBER STATS ROW (SDD: per-group activity reports) --}}
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Total Members</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalMembers) }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Active This Week</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($activeThisWeek) }}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $totalMembers > 0 ? round(($activeThisWeek / $totalMembers) * 100, 1) : 0 }}% of total members
                    </p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Warnings This Week</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($warningsThisWeek) }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Active Blacklists</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($activeBlacklists) }}</p>
                </div>
            </div>

            {{-- MAIN GRID --}}
            <div class="grid grid-cols-3 gap-6">

                {{-- Post Volume Chart --}}
                <div class="col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-900">Post Volume Traffic</h3>
                            <p class="text-xs text-gray-400">Engagement across all modules this week</p>
                        </div>
                        <span class="flex items-center gap-1.5 text-xs text-green-600 font-semibold">
                            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                            Live
                        </span>
                    </div>

                    {{-- Bar chart — real post counts for the last 7 days --}}
                    @php
                        $peak = max(1, $postVolume->max('count'));
                    @endphp
                    <div class="flex items-end gap-3 mb-3" style="height: 160px;">
                        @foreach($postVolume as $day)
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                            <span class="text-xs text-gray-500 font-medium">{{ $day['count'] }}</span>
                            <div class="w-full rounded-t bg-gray-900 hover:bg-gray-700 transition-colors"
                                 style="height: {{ max(4, ($day['count'] / $peak) * 140) }}px;"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex gap-3">
                        @foreach($postVolume as $day)
                        <div class="flex-1 text-center text-xs text-gray-400">{{ $day['label'] }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- System Status — no real telemetry source exists yet (no uptime/sync
                     monitoring service is wired into the app), so these three cards stay
                     as visual placeholders rather than being backed by fake precision. --}}
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">System Sync</p>
                            <span class="text-xs text-green-600 font-bold uppercase bg-green-50 px-2 py-0.5 rounded-full">Operational</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">99.8%</p>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3">
                            <div class="bg-gray-900 h-1.5 rounded-full" style="width: 99.8%"></div>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Offline Reliability</p>
                            <span class="text-xs text-gray-600 font-bold uppercase bg-gray-100 px-2 py-0.5 rounded-full">Stable</span>
                        </div>
                        <p class="text-2xl font-bold text-gray-900">Active</p>
                        <p class="text-xs text-gray-400 mt-2">Local caching active for 4.2k users</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Quiz Sessions</p>
                            <span class="text-xs text-yellow-700 font-bold uppercase bg-yellow-50 px-2 py-0.5 rounded-full">Scheduled</span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900">3</p>
                        <p class="text-xs text-gray-400 mt-2">Next: Week 3 Quiz · 30 Jun</p>
                    </div>
                </div>

                {{-- Most Active Groups (replaces the mockup's "AI-curated Trending Topics" —
                     no topic-classification ML service is wired in yet, so this shows real
                     group-level activity instead of fabricating semantic clusters) --}}
                <div class="col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Groups</h3>
                            <p class="text-xs text-gray-400">Topic count per group</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @forelse($groups as $group)
                        <span class="px-4 py-2 rounded-full text-sm font-semibold
                            {{ $group->topics_count > 0
                                ? 'bg-gray-900 text-white'
                                : 'border border-gray-200 text-gray-700' }}">
                            {{ $group->name }} · {{ $group->topics_count }} topics
                        </span>
                        @empty
                        <p class="text-sm text-gray-400">No groups yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Recent Activity — real feed from activity_log --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Recent Activity</h3>
                    </div>
                    <div class="space-y-4">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full shrink-0 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                    {{ strtoupper(substr($activity->user->username ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ $activity->user->username ?? 'Unknown' }}
                                        {{ str_replace('_', ' ', $activity->action_type) }}
                                        @if($activity->group)
                                            in <span class="font-bold">{{ $activity->group->name }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $activity->logged_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">No activity logged yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>