<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-8 py-6">

            {{-- HEADER --}}
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Academic Insights</h1>
                    <p class="text-sm text-gray-400 mt-1">Real-time performance metrics for the current semester.</p>
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

                {{-- Group Average Performance — real mean quiz score per
                     group, replacing the old fake "99.8% System Sync" style
                     placeholder cards --}}
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Group Average Performance</p>
                        </div>
                        @forelse($groupPerformance as $row)
                        <div class="mb-3 last:mb-0">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span class="font-medium">{{ $row['name'] }}</span>
                                <span class="font-bold text-gray-900">{{ $row['avgPct'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-gray-900 h-1.5 rounded-full" style="width: {{ $row['avgPct'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $row['count'] }} completed {{ Str::plural('quiz', $row['count']) }}</p>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400">No completed quizzes yet.</p>
                        @endforelse
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <div class="flex justify-between items-center mb-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Group Activity (Last 7 Days)</p>
                        </div>
                        @php $activityPeak = max(1, $groupActivity->max('count')); @endphp
                        @forelse($groupActivity as $row)
                        <div class="mb-3 last:mb-0">
                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                <span class="font-medium">{{ $row['name'] }}</span>
                                <span class="font-bold text-gray-900">{{ $row['count'] }} {{ Str::plural('post', $row['count']) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ ($row['count'] / $activityPeak) * 100 }}%"></div>
                            </div>
                        </div>
                        @empty
                        <p class="text-xs text-gray-400">No groups yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Lecturer Performance — grades and grading activity
                     grouped by who actually set each quiz, not by group
                     admin (any student can admin a group now) --}}
                <div class="col-span-3 bg-white border border-gray-200 rounded-lg p-6">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-900">Lecturer Performance</h3>
                        <p class="text-xs text-gray-400">Quiz average and grading activity per lecturer</p>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="py-2 pr-4">Lecturer</th>
                                <th class="py-2 pr-4">Course(s)</th>
                                <th class="py-2 pr-4">Quizzes Set</th>
                                <th class="py-2 pr-4">Avg Score</th>
                                <th class="py-2">Students Graded</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lecturerPerformance as $row)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="py-3 pr-4 font-medium text-gray-800">{{ $row['name'] }}</td>
                                <td class="py-3 pr-4 text-gray-600">
                                    {{ $row['courses']->isNotEmpty() ? $row['courses']->join(', ') : '—' }}
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $row['quizCount'] }}</td>
                                <td class="py-3 pr-4">
                                    @if($row['avgPct'] !== null)
                                        <span class="font-semibold text-gray-900">{{ $row['avgPct'] }}%</span>
                                        <span class="text-xs text-gray-400">({{ $row['submissionCount'] }} {{ Str::plural('submission', $row['submissionCount']) }})</span>
                                    @else
                                        <span class="text-xs text-gray-400">No completed quizzes yet</span>
                                    @endif
                                </td>
                                <td class="py-3 text-gray-600">{{ $row['studentsGraded'] }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">No lecturers yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                   </table>
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
