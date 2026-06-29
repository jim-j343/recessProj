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
                    <p class="text-3xl font-bold text-gray-900 mt-1">4,218</p>
                    <p class="text-xs text-green-600 font-semibold mt-1">↑ +34 this week</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Active This Week</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">1,847</p>
                    <p class="text-xs text-gray-400 mt-1">43.8% of total members</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Warnings Issued</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">12</p>
                    <p class="text-xs text-yellow-600 font-semibold mt-1">↑ +3 this week</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Blacklisted</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">5</p>
                    <p class="text-xs text-red-500 font-semibold mt-1">2 pending review</p>
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

                    {{-- Bar chart - fixed with inline height px instead of % --}}
                    <div class="flex items-end gap-3 mb-3" style="height: 160px;">
                        @foreach([
                            ['Mon', 48],
                            ['Tue', 88],
                            ['Wed', 72],
                            ['Thu', 120],
                            ['Fri', 104],
                            ['Sat', 144],
                            ['Sun', 112],
                        ] as [$label, $h])
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                            <span class="text-xs text-gray-500 font-medium">{{ $h }}</span>
                            <div class="w-full rounded-t bg-gray-900 hover:bg-gray-700 transition-colors"
                                 style="height: {{ $h }}px; max-height: 140px;"></div>
                        </div>
                        @endforeach
                    </div>
                    <div class="flex gap-3">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                        <div class="flex-1 text-center text-xs text-gray-400">{{ $day }}</div>
                        @endforeach
                    </div>
                </div>

                {{-- System Status --}}
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

                {{-- Trending Topics --}}
                <div class="col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Trending Topics</h3>
                            <p class="text-xs text-gray-400">AI-curated semantic clusters</p>
                        </div>
                        <span class="text-gray-300">✦</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach([
                            ['Quantum Mechanics', true],
                            ['Latex Formatting', false],
                            ['Final Review', false],
                            ['Study Groups', false],
                            ['Peer Feedback', true],
                            ['Neural Networks', false],
                            ['Office Hours', false],
                        ] as [$topic, $active])
                        <span class="px-4 py-2 rounded-full text-sm font-semibold cursor-pointer transition-colors
                            {{ $active
                                ? 'bg-gray-900 text-white'
                                : 'border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                            {{ $topic }}
                        </span>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">Recent Activity</h3>
                        <a href="#" class="text-xs text-gray-500 hover:text-gray-900 underline">View all</a>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full shrink-0 flex items-center justify-center text-indigo-700 font-bold text-xs">M</div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Marcus V. posted in <span class="font-bold">Calculus II</span></p>
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">"Could anyone clarify the integration b..."</p>
                                <p class="text-xs text-gray-400">2 minutes ago</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 pb-4 border-b border-gray-100">
                            <div class="w-8 h-8 bg-green-100 rounded-full shrink-0 flex items-center justify-center text-green-700 font-bold text-xs">L</div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Lila K. uploaded <span class="font-bold">Assignment_3.pdf</span></p>
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">Submitted to Intro to Philosophy...</p>
                                <p class="text-xs text-gray-400">15 minutes ago</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full shrink-0 flex items-center justify-center text-purple-700 font-bold text-xs">C</div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Chen J. joined <span class="font-bold">Macroeconomics</span></p>
                                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">New student enrollment completed...</p>
                                <p class="text-xs text-gray-400">1 hour ago</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>