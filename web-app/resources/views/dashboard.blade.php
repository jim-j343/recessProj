<x-app-layout>

    <div class="min-h-screen bg-gray-50" x-data="{
        search: '',
        activeTag: 'all',
        topics: [
            { id: 1, title: 'What is Machine Learning?', excerpt: 'An introduction to the core concepts of machine learning and how it applies to modern software systems.', tags: ['DataScience', 'AI'], author: 'Jane Doe', time: '2h ago', replies: 24, views: '1.2k', avatar: 'J', color: 'indigo' },
            { id: 2, title: 'Introduction to Database Normalization', excerpt: 'Understanding the principles of database normalization and how to apply them in real-world database design.', tags: ['Databases', 'SQL'], author: 'John Smith', time: '1d ago', replies: 12, views: '840', avatar: 'J', color: 'green' },
            { id: 3, title: 'Is micro-frontend architecture still viable?', excerpt: 'We\'ve been using Module Federation for a year, but the overhead is becoming significant. Is monolith-first becoming the trend again?', tags: ['Architecture'], author: 'Alice', time: '1d ago', replies: 48, views: '2.5k', avatar: 'A', color: 'purple' }
        ],
        get filtered() {
            return this.topics.filter(t => {
                const matchSearch = this.search === '' ||
                    t.title.toLowerCase().includes(this.search.toLowerCase()) ||
                    t.excerpt.toLowerCase().includes(this.search.toLowerCase());
                const matchTag = this.activeTag === 'all' ||
                    t.tags.some(tag => tag.toLowerCase() === this.activeTag.toLowerCase());
                return matchSearch && matchTag;
            });
        }
    }">

        {{-- AI RECOMMENDED TOPICS ROW --}}
        <div class="border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8 py-4">
            <div class="max-w-7xl mx-auto">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2 uppercase tracking-wide">
                        ✦ AI Recommended Topics
                    </h2>
                    <a href="{{ route('forum.index') }}" class="text-xs text-gray-400 hover:text-gray-800 uppercase tracking-wide font-semibold">
                        View All
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <a href="{{ route('forum.index') }}" class="group border border-gray-200 rounded-lg p-3 bg-white hover:border-gray-400 transition-all">                        <span class="text-xs bg-gray-900 text-white px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">New For You</span>
                        <h3 class="font-semibold text-gray-900 mt-2 text-sm leading-snug">Introduction to Machine Learning</h3>
                        <div class="flex justify-between items-center mt-3">
                            <p class="text-xs text-gray-400">89 active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    <a href="{{ route('forum.index') }}" class="group border border-gray-200 rounded-lg p-3 bg-white hover:border-gray-400 transition-all">                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">Recommended</span>
                        <h3 class="font-semibold text-gray-900 mt-2 text-sm leading-snug">Database Normalization Deep Dive</h3>
                        <div class="flex justify-between items-center mt-3">
                            <p class="text-xs text-gray-400">124 active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    <a href="{{ route('forum.index') }}" class="group border border-gray-200 rounded-lg p-3 bg-white hover:border-gray-400 transition-all">
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">Recommended</span>
                        <h3 class="font-semibold text-gray-900 mt-2 text-sm leading-snug">Web Development with Laravel</h3>
                        <div class="flex justify-between items-center mt-3">
                            <p class="text-xs text-gray-400">250+ active members</p>
                            <span class="text-gray-400 group-hover:text-gray-900 transition-colors">→</span>
                        </div>
                    </a>
                    <a href="{{ route('forum.index') }}" class="group border border-gray-900 rounded-lg p-3 bg-gray-900 hover:bg-gray-800 transition-all">
                        <span class="text-xs bg-white/20 text-white px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">Popular</span>
                        <h3 class="font-semibold text-white mt-2 text-sm leading-snug">Software Engineering Principles</h3>
                        <div class="flex justify-between items-center mt-3">
                            <p class="text-xs text-gray-400">42 active members</p>
                            <span class="text-gray-400 group-hover:text-white transition-colors">→</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col lg:flex-row gap-6">

            {{-- LEFT: Groups Sidebar --}}
            <aside class="w-full lg:w-56 shrink-0 order-1 lg:order-1">
                <div class="bg-white border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wide">
                        My Groups
                    </h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="#" class="block text-sm text-blue-600 hover:bg-blue-50 px-2 py-1.5 rounded">
                                📁 Group Alpha
                            </a>
                        </li>
                        <li>
                            <a href="#" class="block text-sm text-gray-700 hover:bg-gray-50 px-2 py-1.5 rounded">
                                📁 Group Beta
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            {{-- CENTER: Recent Discussions --}}
            <main class="flex-1 min-w-0 space-y-4 order-3 lg:order-2">

                {{-- Search Bar (SDD Requirement) --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Search discussions by keyword..."
                        class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm
                               bg-white focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                               placeholder-gray-400 transition-all"
                    />
                    <span x-show="search !== ''" x-cloak
                        @click="search = ''"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-400 cursor-pointer hover:text-gray-700">
                        ✕
                    </span>
                </div>

                {{-- Tag Filter Chips --}}
                <div class="flex flex-wrap gap-2">
                    <template x-for="tag in ['all', 'DataScience', 'AI', 'Databases', 'SQL', 'Architecture']" :key="tag">
                        <button
                            @click="activeTag = tag"
                            :class="activeTag === tag
                                ? 'bg-gray-900 text-white border-gray-900'
                                : 'bg-white text-gray-500 border-gray-200 hover:border-gray-400'"
                            class="px-3 py-1 rounded-full text-xs font-semibold border uppercase tracking-wide transition-all"
                            x-text="tag === 'all' ? 'All Topics' : '#' + tag">
                        </button>
                    </template>
                </div>

                {{-- Header row --}}
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900">Recent Discussions</h3>
                        <div class="flex bg-gray-100 rounded-lg p-0.5">
                            <button class="px-3 py-1 text-xs font-semibold bg-white rounded-md shadow-sm text-gray-900">Latest</button>
                            <button class="px-3 py-1 text-xs font-semibold text-gray-500 hover:text-gray-700">Trending</button>
                        </div>
                    </div>
                    @if(auth()->user()->role === 'lecturer')
                    <a href="{{ route('topics.create') }}"
                        class="flex items-center gap-1 bg-gray-900 text-white text-xs px-4 py-2 rounded-lg hover:bg-gray-700">
                        + New Thread
                    </a>
                    @endif
                </div>

                {{-- Topic Cards (Alpine filtered) --}}
                <div class="space-y-3">
                    <template x-for="topic in filtered" :key="topic.id">
                        <a :href="`/topics/${topic.id}`"
                            class="block bg-white border border-gray-200 rounded-lg p-5 hover:border-gray-400 hover:shadow-sm transition-all">
                            <div class="flex gap-4">
                                <div :class="`w-9 h-9 bg-${topic.color}-100 rounded-full flex items-center justify-center text-${topic.color}-700 font-bold text-sm shrink-0`"
                                     x-text="topic.avatar">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 text-sm" x-text="topic.title"></h4>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2" x-text="topic.excerpt"></p>
                                    <div class="flex items-center gap-3 mt-3 flex-wrap">
                                        <template x-for="tag in topic.tags" :key="tag">
                                            <span class="text-xs text-indigo-600 font-medium" x-text="'#' + tag"></span>
                                        </template>
                                        <span class="text-xs text-gray-400 ml-auto" x-text="'Posted ' + topic.time + ' by ' + topic.author"></span>
                                        <span class="text-xs text-gray-500">💬 <span x-text="topic.replies"></span></span>
                                        <span class="text-xs text-gray-500">👁 <span x-text="topic.views"></span></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </template>

                    {{-- Empty state --}}
                    <div x-show="filtered.length === 0" class="text-center py-12 text-gray-400">
                        <p class="text-2xl mb-2">🔍</p>
                        <p class="text-sm font-medium">No discussions match "<span x-text="search"></span>"</p>
                        <p class="text-xs mt-1">Try a different keyword or clear the filter.</p>
                    </div>
                </div>

            </main>

            {{-- RIGHT PANEL: Statistics + Unanswered --}}
            <aside class="w-full lg:w-72 shrink-0 space-y-4 order-2 lg:order-3">

                {{-- Your Statistics — prominent --}}
                <div class="bg-gray-900 text-white rounded-xl p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-white text-sm uppercase tracking-wide">Your Statistics</h3>
                        <a href="#" class="text-gray-400 hover:text-white text-xs transition-colors">↗ Full report</a>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-5">
                        <div class="bg-white/10 rounded-lg p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Marks Earned</p>
                            <p class="text-3xl font-bold text-white mt-1">124</p>
                            <p class="text-xs text-green-400 font-semibold mt-1">↑ +12 this week</p>
                        </div>
                        <div class="bg-white/10 rounded-lg p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Contributions</p>
                            <p class="text-3xl font-bold text-white mt-1">8</p>
                            <p class="text-xs text-gray-400 mt-1">2 pending review</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="text-gray-400">Progress to Expert Tier</span>
                            <span class="font-bold text-white">82%</span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-2">
                            <div class="bg-white h-2 rounded-full transition-all" style="width: 82%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">18% remaining — keep contributing!</p>
                    </div>
                </div>

                {{-- Unanswered Questions --}}
                <div class="bg-white border border-gray-200 rounded-lg p-5">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-semibold text-gray-900 text-sm">Unanswered</h3>
                        <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-semibold">3 open</span>
                    </div>
                    <div class="space-y-3">
                        <div class="border-b border-gray-100 pb-3">
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                How to handle race conditions in distributed systems?
                            </p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-semibold">URGENT</span>
                                <span class="text-xs text-gray-400">8m ago</span>
                            </div>
                        </div>
                        <div class="border-b border-gray-100 pb-3">
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                WebAssembly vs JS for high-perf computation?
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Trending · 2h ago</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-800 leading-snug">
                                Implementing Z-index layering in complex SVG charts?
                            </p>
                            <p class="text-xs text-gray-400 mt-1">3 tags · 5h ago</p>
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