<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">Participation Grading</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- GROUP SWITCHER --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 flex flex-wrap gap-2 items-center">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide mr-2">Group</span>
                @forelse($groups as $group)
                    <a href="{{ route('participation.grade', ['group_id' => $group->group_id]) }}"
                       class="px-3 py-1.5 rounded-full text-sm font-medium
                              {{ $selectedGroup && $selectedGroup->group_id === $group->group_id
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $group->name }}
                    </a>
                @empty
                    <p class="text-sm text-gray-400">You aren't a member of any groups yet.</p>
                @endforelse
            </div>

            @if($selectedGroup)
                {{-- GRADING TABLE --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <form method="POST" action="{{ route('participation.grade.save') }}">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $selectedGroup->group_id }}" />

                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-700">{{ $selectedGroup->name }} — Active Members</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Scores are out of 100 and are added as a new entry for the criteria below.</p>
                            </div>
                            <div class="w-64">
                                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Criteria</label>
                                <input type="text" name="criteria" required maxlength="120"
                                    value="{{ old('criteria', 'Forum Participation') }}"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                           focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 text-left">Student</th>
                                    <th class="px-6 py-3 text-left">Email</th>
                                    <th class="px-6 py-3 text-left w-40">Score (0–100)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($students as $student)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <x-avatar :name="$student->username" size="w-7 h-7" />
                                                <span class="font-medium text-gray-800">{{ $student->username }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $student->email }}</td>
                                        <td class="px-6 py-4">
                                            <input type="number" name="scores[{{ $student->user_id }}]"
                                                min="0" max="100" step="0.5" placeholder="—"
                                                class="w-24 border-gray-300 rounded-md shadow-sm text-sm
                                                       focus:ring-indigo-500 focus:border-indigo-500" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-6 text-center text-sm text-gray-400">
                                            No active student members in this group yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($students->isNotEmpty())
                            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                                <button type="submit"
                                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg
                                           text-sm font-semibold hover:bg-indigo-700">
                                    Save All Grades
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
