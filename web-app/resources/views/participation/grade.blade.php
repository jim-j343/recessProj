<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Participation Grading</h2>
            <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('participation.grade') }}"
                  class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6 flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-56">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Filter by Topic</label>
                    <select name="topic" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">All Topics</option>
                        @foreach($topics as $topic)
                            <option value="{{ $topic->topic_id }}" {{ $topicFilter == $topic->topic_id ? 'selected' : '' }}>
                                {{ $topic->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-56">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Filter by Student</label>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Search student name..."
                        class="w-full border-gray-300 rounded-lg text-sm" />
                </div>
                <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-indigo-700">
                    Filter
                </button>
            </form>

            {{-- Grading table --}}
            <form method="POST" action="{{ route('participation.store') }}">
                @csrf
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex justify-between items-center px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Student Forum Participation</h3>
                        <button type="submit"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                            💾 Save All Grades
                        </button>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="px-6 py-3">Student</th>
                                <th class="px-4 py-3">Latest Topic</th>
                                <th class="px-4 py-3">Posts</th>
                                <th class="px-4 py-3">Replies</th>
                                <th class="px-4 py-3">Quality</th>
                                <th class="px-4 py-3">Grade</th>
                                <th class="px-6 py-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs">
                                            {{ strtoupper(substr($row->student->username, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800">{{ $row->student->username }}</p>
                                            @if($row->existing)
                                                <p class="text-xs text-gray-400">Last score: {{ $row->existing->score }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-600">{{ $row->latestTopic ?? '—' }}</td>
                                <td class="px-4 py-4 text-gray-600">{{ $row->postCount }}</td>
                                <td class="px-4 py-4 text-gray-600">{{ $row->replyCount }}</td>
                                <td class="px-4 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full font-medium
                                        {{ $row->quality === 'High' ? 'bg-green-100 text-green-700' :
                                           ($row->quality === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $row->quality }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <select name="grades[{{ $row->student->user_id }}][grade]"
                                        class="border-gray-300 rounded-lg text-sm w-20">
                                        <option value="">—</option>
                                        @foreach(['A','B','C','D','F'] as $g)
                                            <option value="{{ $g }}">{{ $g }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="grades[{{ $row->student->user_id }}][remark]"
                                        placeholder="Optional remark..."
                                        class="w-full border-gray-300 rounded-lg text-sm" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                                    No students found for this filter.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

