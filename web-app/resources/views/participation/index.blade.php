<x-app-layout>
    <div class="min-h-screen bg-gray-50">

        {{-- LEFT SIDEBAR + CONTENT --}}
        <div class="flex">

            {{-- LEFT SIDEBAR --}}
            <aside class="w-64 min-h-screen bg-white border-r border-gray-200 p-6 shrink-0">
                <div class="mb-8">
                    <p class="font-bold text-gray-900 text-lg">Alex Rivers</p>
                    <p class="text-sm text-gray-500">Computer Science Major</p>
                    <span class="inline-block mt-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-semibold">
                        Level 4
                    </span>
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        ⊞ Dashboard
                    </a>
                    <a href="#"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50">
                        📚 Courses
                    </a>
                    <a href="{{ route('participation.index') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm bg-blue-50 text-blue-700 font-semibold">
                        📊 Assessment Overview
                    </a>
                    <a href="#"
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
                        Detailed breakdown of your academic consistency and recent performance.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">

                    {{-- Participation Metrics --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">Participation Metrics</h3>
                                <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mt-0.5">
                                    Real-Time Engagement Data
                                </p>
                            </div>
                            <span class="text-gray-300">⊡</span>
                        </div>

                        {{-- Bar Chart --}}
                        <p class="text-xs text-gray-500 mb-3">Consistency Score (Last 7 Days)</p>
                        <div class="flex items-end gap-2 h-24 mb-4">
                            @foreach([40, 65, 50, 80, 45, 90, 60] as $i => $h)
                            <div class="flex-1 rounded-t"
                                style="height: {{ $h }}%; background: {{ $h >= 80 ? '#1e293b' : '#94a3b8' }}">
                            </div>
                            @endforeach
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mb-4">
                            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                            <span>{{ $d }}</span>
                            @endforeach
                        </div>

                        {{-- Metrics --}}
                        <div class="space-y-3">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Forum Contribution Quality</span>
                                    <span class="font-bold text-gray-900">94/100</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-gray-900 h-1.5 rounded-full" style="width: 94%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Peer Review Accuracy</span>
                                    <span class="font-bold text-gray-900">88%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-gray-400 h-1.5 rounded-full" style="width: 88%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Assessment History --}}
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-semibold text-gray-900">Assessment History</h3>
                            <button class="text-sm text-gray-400 hover:text-gray-700 flex items-center gap-1">
                                Download All ↓
                            </button>
                        </div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide grid grid-cols-4 pb-2 border-b border-gray-100 mb-2">
                            <span class="col-span-2">Quiz/Module</span>
                            <span>Score</span>
                            <span>Vs Peer Avg</span>
                        </div>
                        <div class="space-y-4">
                            <div class="grid grid-cols-4 items-center py-2 border-b border-gray-50">
                                <div class="col-span-2">
                                    <p class="text-sm font-semibold text-gray-800">Advanced React Patterns</p>
                                    <p class="text-xs text-gray-400">Completed Oct 14, 2024</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">92%</span>
                                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full w-fit">
                                    +12%
                                </span>
                            </div>
                            <div class="grid grid-cols-4 items-center py-2 border-b border-gray-50">
                                <div class="col-span-2">
                                    <p class="text-sm font-semibold text-gray-800">Data Structures II</p>
                                    <p class="text-xs text-gray-400">Completed Oct 08, 2024</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">85%</span>
                                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full w-fit">
                                    +4%
                                </span>
                            </div>
                            <div class="grid grid-cols-4 items-center py-2">
                                <div class="col-span-2">
                                    <p class="text-sm font-semibold text-gray-800">System Architecture</p>
                                    <p class="text-xs text-gray-400">Completed Sep 29, 2024</p>
                                </div>
                                <span class="text-sm font-bold text-gray-900">78%</span>
                                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full w-fit">
                                    -2%
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-4">Showing 3 of 12 assessments</p>
                    </div>
                </div>

                {{-- Professor's Insight --}}
                <div class="bg-gray-900 text-white rounded-lg p-6">
                    <h3 class="font-semibold text-lg mb-2">Professor's Insight</h3>
                    <p class="text-gray-400 text-sm italic mb-6">
                        "Alex demonstrates exceptional mastery of front-end logic. For the final capstone,
                        focus on optimizing the render cycles in the 'Advanced Patterns' module to achieve
                        top-tier performance benchmarks."
                    </p>
                    <div class="flex gap-3">
                        <button class="bg-white text-gray-900 px-4 py-2 rounded-lg text-sm font-semibold
                                       hover:bg-gray-100 transition-colors uppercase tracking-wide">
                            Schedule Office Hours
                        </button>
                        <button class="border border-gray-600 text-white px-4 py-2 rounded-lg text-sm
                                       font-semibold hover:bg-gray-800 transition-colors uppercase tracking-wide">
                            View Curated Resources
                        </button>
                    </div>
                </div>

            </main>
        </div>
    </div>
</x-app-layout>