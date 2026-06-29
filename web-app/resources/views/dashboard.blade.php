<x-app-layout>

    {{-- TOP NAV is handled by x-app-layout --}}

    <div class="min-h-screen bg-gray-50">

        {{-- AI RECOMMENDED TOPICS ROW --}}
        <div class="border-b border-gray-200 bg-white px-8 py-5">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        ✦ AI Recommended Topics
                    </h2>
                    <a href="#" class="text-xs text-gray-500 hover:text-gray-800 uppercase tracking-wide font-semibold">
                        View All
                    </a>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    {{-- Card 1 --}}
                    <a href="#" class="group border border-gray-200 rounded-lg p-4 bg-white hover:border-gray-400 transition-all">
                        <span class="text-xs bg-gray-900 text-white px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">
                            New For You
                        </span>
                        <h3 class="font-semibold text-gray-900 mt-3 text-sm leading-snug">
                            Introduction to Machine Learning
                        </h3>
                        <div class="flex justify-between items-center mt-4">
                            <p class="text-xs text-gray-400">89 active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    {{-- Card 2 --}}
                    <a href="#" class="group border border-gray-200 rounded-lg p-4 bg-white hover:border-gray-400 transition-all">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">
                            Recommended
                        </span>
                        <h3 class="font-semibold text-gray-900 mt-3 text-sm leading-snug">
                            Database Normalization Deep Dive
                        </h3>
                        <div class="flex justify-between items-center mt-4">
                            <p class="text-xs text-gray-400">124 active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    {{-- Card 3 --}}
                    <a href="#" class="group border border-gray-200 rounded-lg p-4 bg-white hover:border-gray-400 transition-all">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">
                            Recommended
                        </span>
                        <h3 class="font-semibold text-gray-900 mt-3 text-sm leading-snug">
                            Web Development with Laravel
                        </h3>
                        <div class="flex justify-between items-center mt-4">
                            <p class="text-xs text-gray-400">250+ active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    {{-- Card 4 (highlighted) --}}
                    <a href="#" class="group border border-gray-900 rounded-lg p-4 bg-gray-900 hover:bg-gray-800 transition-all">
                        <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">
                            Popular
                        </span>
                        <h3 class="font-semibold text-white mt-3 text-sm leading-snug">
                            Software Engineering Principles
                        </h3>
                        <div class="flex justify-between items-center mt-4">
                            <p class="text-xs text-gray-400">42 active members</p>
                            <span class="text-gray-400 group-hover:text-white transition-colors">→</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="max-w-7xl mx-auto px-8 py-6 flex gap-6">

            {{-- CENTER: Recent Discussions --}}
            <main class="flex-1 space-y-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">Recent Discussions</h3>
                        <div class="flex bg-gray-100 rounded-lg p-0.5">
                            <button class="px-3 py-1 text-xs font-semibold bg-white rounded-md shadow-sm text-gray-900">
                                Latest
                            </button>
                            <button class="px-3 py-1 text-xs font-semibold text-gray-500 hover:text-gray-700">
                                Trending
                            </button>
                        </div>
                    </div>
                    @if(auth()->user()->role === 'lecturer')
                    <a href="{{ route('topics.create') }}"
                        class="flex items-center gap-1 bg-gray-900 text-white text-xs px-4 py-2 rounded-lg hover:bg-gray-700">
                        + New Thread
                    </a>
                    @endif
                </div>

                {{-- Topic Cards --}}
                {{-- Later: @foreach($topics as $topic) --}}
                <a href="{{ route('topics.show', 1) }}"
                    class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-gray-400 transition-all">
                    <div class="flex gap-4">
                        <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">
                            J
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 text-sm">
                                What is Machine Learning?
                            </h4>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                An introduction to the core concepts of machine learning and how it applies to modern software systems.
                            </p>
                            <div class="flex items-center gap-4 mt-3">
                                <span class="text-xs text-gray-400">#DataScience</span>
                                <span class="text-xs text-gray-400">#AI</span>
                                <span class="text-xs text-gray-400 ml-auto">Posted 2h ago by Jane Doe</span>
                                <span class="text-xs text-gray-500">💬 24</span>
                                <span class="text-xs text-gray-500">👁 1.2k</span>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('topics.show', 2) }}"
                    class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-gray-400 transition-all">
                    <div class="flex gap-4">
                        <div class="w-9 h-9 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-bold text-sm shrink-0">
                            J
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 text-sm">
                                Introduction to Database Normalization
                            </h4>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                Understanding the principles of database normalization and how to apply them in real-world database design.
                            </p>
                            <div class="flex items-center gap-4 mt-3">
                                <span class="text-xs text-gray-400">#Databases</span>
                                <span class="text-xs text-gray-400">#SQL</span>
                                <span class="text-xs text-gray-400 ml-auto">Posted 1d ago by John Smith</span>
                                <span class="text-xs text-gray-500">💬 12</span>
                                <span class="text-xs text-gray-500">👁 840</span>
                            </div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('topics.show', 3) }}"
                    class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-gray-400 transition-all">
                    <div class="flex gap-4">
                        <div class="w-9 h-9 bg-purple-100 rounded-full flex items-center justify-center text-purple-700 font-bold text-sm shrink-0">
                            A
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 text-sm">
                                Is micro-frontend architecture still viable?
                            </h4>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                We've been using Module Federation for a year, but the overhead is becoming significant. Is monolith-first becoming the trend again?
                            </p>
                            <div class="flex items-center gap-4 mt-3">
                                <span class="text-xs text-gray-400">#Architecture</span>
                                <span class="text-xs text-gray-400 ml-auto">Posted 1d ago by Alice</span>
                                <span class="text-xs text-gray-500">💬 48</span>
                                <span class="text-xs text-gray-500">👁 2.5k</span>
                            </div>
                        </div>
                    </div>
                </a>
                {{-- @endforeach --}}
            </main>

            {{-- RIGHT PANEL: Your Statistics + Unanswered --}}
            <aside class="w-72 shrink-0 space-y-4">

                {{-- Your Statistics --}}
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900 text-sm">Your Statistics</h3>
                        <span class="text-gray-300 text-lg">↗</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Marks Earned</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">124</p>
                            <p class="text-xs text-green-600 font-semibold mt-1">↑ +12 this week</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Contributions</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">8</p>
                            <p class="text-xs text-gray-400 mt-1">2 pending review</p>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progress to Expert Tier</span>
                            <span class="font-semibold text-gray-700">82%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="bg-gray-900 h-1.5 rounded-full" style="width: 82%"></div>
                        </div>
                    </div>
                </div>

                {{-- Unanswered Questions --}}
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-gray-900 text-sm">Unanswered</h3>
                        <span class="text-gray-400 text-xs">?</span>
                    </div>
                    <div class="space-y-3">
                        <div class="border-b border-gray-100 pb-3">
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                How to handle race conditions in distributed systems?
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-semibold">
                                    URGENT
                                </span>
                                <span class="text-xs text-gray-400">8m ago</span>
                            </div>
                        </div>
                        <div class="border-b border-gray-100 pb-3">
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                WebAssembly vs JS for high-perf computation?
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Trending • 2h ago</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                Implementing Z-index layering in complex SVG charts?
                            </p>
                            <p class="text-xs text-gray-400 mt-1">3 tags • 5h ago</p>
                        </div>
                    </div>
                    <a href="#"
                        class="block text-center text-xs text-gray-500 hover:text-gray-800 border border-gray-200
                               rounded-lg py-2 mt-4 hover:border-gray-400 transition-all">
                        Browse All Unanswered ↗
                    </a>
                </div>

                {{-- Upcoming Quizzes --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5">
                    <h3 class="font-semibold text-yellow-800 text-sm mb-3">📢 Upcoming Quizzes</h3>
                    <div class="bg-white rounded-lg p-3 border border-yellow-100">
                        <p class="text-sm font-semibold text-gray-800">Week 3 Quiz</p>
                        <p class="text-xs text-gray-400 mt-1">Starts: 30 June · 10:00 AM</p>
                        <p class="text-xs text-gray-400">Duration: 30 mins</p>
                    </div>
                </div>

            </aside>
        </div>
    </div>
</x-app-layout>