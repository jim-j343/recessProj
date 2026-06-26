<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📚 Discussion Forum
            </h2>
            {{-- Lecturers see a "New Topic" button --}}
            @if(auth()->user()->role === 'lecturer')
                <a href="{{ route('topics.create') }}"
                    class="bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700">
                    + New Topic
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex gap-6">

            {{-- LEFT SIDEBAR: Groups List --}}
            <aside class="w-1/5 shrink-0">
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wide">
                        My Groups
                    </h3>
                    <ul class="space-y-2">
                        {{-- Later: @foreach($groups as $group) --}}
                        <li>
                            <a href="#"
                                class="block text-sm text-blue-600 hover:bg-blue-50 px-2 py-1 rounded">
                                📁 Group Alpha
                            </a>
                        </li>
                        <li>
                            <a href="#"
                                class="block text-sm text-gray-700 hover:bg-gray-50 px-2 py-1 rounded">
                                📁 Group Beta
                            </a>
                        </li>
                        {{-- @endforeach --}}
                    </ul>
                </div>
            </aside>

            {{-- CENTER: Topic Feed --}}
            <main class="flex-1 space-y-4">

                {{-- Search bar --}}
                <div class="flex gap-2">
                    <input type="text"
                        placeholder="Search topics..."
                        class="flex-1 border border-gray-300 rounded-lg px-4 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                        Search
                    </button>
                </div>

                {{-- Topic Cards --}}
                {{-- Later: @foreach($topics as $topic) --}}
                <a href="{{ route('topics.show', 1) }}"
                    class="block bg-white rounded-lg shadow p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-gray-800">
                                What is Machine Learning?
                            </h4>
                            <p class="text-xs text-gray-400 mt-1">
                                Posted by <span class="text-blue-600">Jane Doe</span>
                                · 2 hours ago · 5 replies
                            </p>
                        </div>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full shrink-0">
                            AI
                        </span>
                    </div>
                </a>

                <a href="{{ route('topics.show', 2) }}"
                    class="block bg-white rounded-lg shadow p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-gray-800">
                                Introduction to Database Normalization
                            </h4>
                            <p class="text-xs text-gray-400 mt-1">
                                Posted by <span class="text-blue-600">John Smith</span>
                                · 1 day ago · 12 replies
                            </p>
                        </div>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full shrink-0">
                            Databases
                        </span>
                    </div>
                </a>
                {{-- @endforeach --}}

            </main>

            {{-- RIGHT PANEL: Recommendations + Quiz Announcements --}}
            <aside class="w-1/4 shrink-0 space-y-4">

                {{-- Recommended Topics --}}
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="font-bold text-gray-700 mb-3 text-sm uppercase tracking-wide">
                        Recommended
                    </h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="#" class="text-sm text-blue-600 hover:underline">
                                → Intro to AI
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm text-blue-600 hover:underline">
                                → Laravel Basics
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Quiz Announcements --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-bold text-yellow-700 mb-2 text-sm uppercase tracking-wide">
                        📢 Upcoming Quizzes
                    </h3>
                    {{-- Later: @forelse($quizzes as $quiz) --}}
                    <div class="text-sm text-gray-700 bg-white rounded p-2 shadow-sm">
                        <p class="font-medium">Week 3 Quiz</p>
                        <p class="text-xs text-gray-400 mt-1">Starts: 30 June · 10:00 AM</p>
                        <p class="text-xs text-gray-400">Duration: 30 mins</p>
                    </div>
                    {{-- @empty --}}
                    {{-- <p class="text-sm text-gray-500">No upcoming quizzes.</p> --}}
                    {{-- @endforelse --}}
                </div>

            </aside>

        </div>
    </div>
</x-app-layout>