<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Participation Grading</h2>
            <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 self-start sm:self-auto">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            {{-- Filters — a mark belongs to one course, so the course is
                 chosen first and everything below is scoped to it --}}
            <form method="GET" action="{{ route('participation.grade') }}"
                  class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6 flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-56">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Course / Group</label>
                    <select name="group" onchange="this.form.submit()" class="w-full border-gray-300 rounded-lg text-sm">
                        @forelse($groups as $g)
                            <option value="{{ $g->group_id }}" {{ $group && $group->group_id == $g->group_id ? 'selected' : '' }}>
                                {{ $g->course_name ? $g->course_name.' — ' : '' }}{{ $g->name }}
                            </option>
                        @empty
                            <option value="">No groups yet</option>
                        @endforelse
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

            @if($group)
            {{-- Grading table --}}
            <form method="POST" action="{{ route('participation.store') }}">
                @csrf
                <input type="hidden" name="group_id" value="{{ $group->group_id }}" />

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 px-4 sm:px-6 py-4 border-b border-gray-100">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-900 truncate">
                                {{ $group->course_name ?: $group->name }}
                            </h3>
                            <p class="text-xs text-gray-400">
                                {{ $group->name }} · participation and quiz average are both from this course only
                            </p>
                        </div>
                        <button type="submit"
                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 self-start sm:self-auto shrink-0">
                            💾 Save All Grades
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                                <th class="px-6 py-3">Student</th>
                                <th class="px-4 py-3">Replies</th>
                                <th class="px-4 py-3">Participation</th>
                                <th class="px-4 py-3">Test Avg</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-6 py-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs shrink-0">
                                            {{ strtoupper(substr($row->student->username, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 truncate">{{ $row->student->username }}</p>
                                            @if($row->existing)
                                                <p class="text-xs text-gray-400">Last score here: {{ $row->existing->score }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-gray-600">{{ $row->replyCount }}</td>
                                <td class="px-4 py-4 text-gray-600 whitespace-nowrap">
                                    {{ $row->participationPct }}%
                                    <span class="text-xs text-gray-400">(reply-based, /10)</span>
                                </td>
                                <td class="px-4 py-4 text-gray-600 whitespace-nowrap">
                                    @if($row->quizAvgPct !== null)
                                        {{ $row->quizAvgPct }}%
                                        <span class="text-xs text-gray-400">({{ $row->quizCount }} {{ Str::plural('quiz', $row->quizCount) }})</span>
                                    @else
                                        <span class="text-xs text-gray-400">No quizzes yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" name="grades[{{ $row->student->user_id }}][score]"
                                        value="{{ $row->suggestedScore }}" min="0" max="100" step="0.1"
                                        class="border-gray-300 rounded-lg text-sm w-20" />
                                    <p class="text-xs text-gray-400 mt-0.5">Suggested — editable</p>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="grades[{{ $row->student->user_id }}][remark]"
                                        maxlength="120" placeholder="Optional remark..."
                                        class="w-full border-gray-300 rounded-lg text-sm" />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                                    No students found in this course.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </form>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400 text-sm">
                You're not a member of any group yet — join the group for the course you teach to grade its students.
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
