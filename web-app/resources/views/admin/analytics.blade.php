<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="flex">

            {{-- LEFT SIDEBAR --}}
            <aside class="w-64 min-h-screen bg-white border-r border-gray-200 p-6 shrink-0">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                        🎓
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-sm">Academic Portal</p>
                        <p class="text-xs text-gray-400">Spring Semester 2024</p>
                    </div>
                </div>
                <nav class="space-y-1">
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm bg-blue-50 text-blue-700 font-semibold">
                        ⊞ Dashboard
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        📚 My Courses
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        💬 Discussions
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        📝 Quiz Center
                    </a>
                </nav>
                <div class="absolute bottom-8 left-6 flex items-center gap-3">
                    <div class="w-9 h-9 bg-gray-300 rounded-full"></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Dr. Sarah Jenkins</p>
                        <p class="text-xs text-gray-400">Lead Administrator</p>
                    </div>
                </div>
            </aside>

            {{-- MAIN CONTENT --}}
            <main class="flex-1 p-8">

                {{-- Header --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Academic Insights</h1>
                        <p class="text-sm text-gray-400 mt-1">Real-time performance metrics for the current semester.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="border border-gray-200 rounded-lg px-4 py-2 text-sm bg-white focus:outline-none">
                            <option>Advanced Calculus II</option>
                            <option>Computer Science 101</option>
                        </select>
                        <button class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                            📅 Last 7 Days
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-6">

                    {{-- Post Volume Chart --}}
                    <div class="col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-semibold text-gray-900">Post Volume Traffic</h3>
                                <p class="text-xs text-gray-400">Engagement velocity across all modules</p>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs text-green-600 font-semibold">
                                <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                                Live Traffic
                            </span>
                        </div>
                        <div class="flex items-end gap-3 h-40 mb-4">
                            @foreach([
                                ['Mon', 30, 'bg-blue-200'],
                                ['Tue', 55, 'bg-blue-300'],
                                ['Wed', 45, 'bg-blue-200'],
                                ['Thu', 75, 'bg-blue-400'],
                                ['Fri', 65, 'bg-blue-300'],
                                ['Sat', 90, 'bg-blue-600'],
                                ['Sun', 70, 'bg-blue-500'],
                            ] as [$label, $h, $color])
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <div class="{{ $color }} rounded-t w-full" style="height: {{ $h }}%"></div>
                                <span class="text-xs text-gray-400">{{ $label }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- System Status --}}
                    <div class="space-y-4">
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-lg">🔄</span>
                                <span class="text-xs text-green-600 font-bold uppercase">Operational</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">System Sync</p>
                            <p class="text-xl font-bold text-gray-900">99.8%</p>
                            <div class="w-full bg-blue-200 rounded-full h-1 mt-2">
                                <div class="bg-blue-600 h-1 rounded-full" style="width: 99.8%"></div>
                            </div>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-lg">📶</span>
                                <span class="text-xs text-blue-600 font-bold uppercase">Stable</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Offline Reliability</p>
                            <p class="text-xl font-bold text-gray-900">Active</p>
                            <p class="text-xs text-gray-400 mt-1">Local caching layer active for 4.2k users</p>
                        </div>
                    </div>

                    {{-- Trending Topics --}}
                    <div class="col-span-2 bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Trending Topics</h3>
                                <p class="text-xs text-gray-400">AI-curated semantic clusters</p>
                            </div>
                            <span class="text-gray-300 text-xl">✦</span>
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
                            <a href="#" class="text-xs text-blue-600 font-semibold hover:underline">View All</a>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-50">
                                <div class="w-8 h-8 bg-gray-200 rounded-full shrink-0"></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Marcus V. posted in <span class="font-bold">Calculus II</span></p>
                                    <p class="text-xs text-gray-400 mt-0.5">"Could anyone clarify the integration b...</p>
                                    <p class="text-xs text-gray-400">2 minutes ago</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 pb-4 border-b border-gray-50">
                                <div class="w-8 h-8 bg-gray-200 rounded-full shrink-0"></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Lila K. uploaded <span class="font-bold">Assignment_3.pdf</span></p>
                                    <p class="text-xs text-gray-400 mt-0.5">Successfully submitted to Intro to Phil...</p>
                                    <p class="text-xs text-gray-400">15 minutes ago</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-gray-200 rounded-full shrink-0"></div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">Chen J. joined <span class="font-bold">Macroeconomics</span></p>
                                    <p class="text-xs text-gray-400 mt-0.5">New student enrollment completed su...</p>
                                    <p class="text-xs text-gray-400">1 hour ago</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
</x-app-layout>