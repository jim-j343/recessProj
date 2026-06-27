<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">📊 Group Statistics & Analytics</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-indigo-600">24</p>
                    <p class="text-sm text-gray-500 mt-1">Total Members</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-green-600">142</p>
                    <p class="text-sm text-gray-500 mt-1">Total Posts</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-yellow-500">9</p>
                    <p class="text-sm text-gray-500 mt-1">Quizzes Held</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-purple-600">78%</p>
                    <p class="text-sm text-gray-500 mt-1">Avg Quiz Score</p>
                </div>
            </div>

            {{-- ACTIVITY CHART (visual bar chart using pure CSS/Tailwind) --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-700 mb-4">
                    📈 Weekly Forum Activity (Posts per Week)
                </h3>
                <div class="flex items-end gap-3 h-40">
                    @foreach([
                        ['Week 1', 12, 'bg-indigo-400'],
                        ['Week 2', 28, 'bg-indigo-500'],
                        ['Week 3', 18, 'bg-indigo-400'],
                        ['Week 4', 35, 'bg-indigo-600'],
                        ['Week 5', 22, 'bg-indigo-400'],
                        ['Week 6', 40, 'bg-indigo-700'],
                    ] as [$label, $value, $color])
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <span class="text-xs font-semibold text-gray-600">{{ $value }}</span>
                        <div class="{{ $color }} rounded-t w-full"
                             style="height: {{ ($value / 40) * 100 }}%">
                        </div>
                        <span class="text-xs text-gray-400">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- TOP CONTRIBUTORS --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">🏆 Top Contributors</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Rank</th>
                            <th class="px-6 py-3 text-left">Member</th>
                            <th class="px-6 py-3 text-left">Posts</th>
                            <th class="px-6 py-3 text-left">Replies</th>
                            <th class="px-6 py-3 text-left">Quiz Avg</th>
                            <th class="px-6 py-3 text-left">Participation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Later: @foreach($topContributors as $i => $member) --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="text-yellow-500 font-bold text-lg">🥇</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-indigo-500 text-white rounded-full
                                                flex items-center justify-center text-xs font-bold">
                                        J
                                    </div>
                                    <span class="font-medium text-gray-800">Jane Doe</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">12</td>
                            <td class="px-6 py-4 text-gray-600">34</td>
                            <td class="px-6 py-4 text-green-600 font-semibold">92%</td>
                            <td class="px-6 py-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full"
                                         style="width: 92%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="text-gray-400 font-bold text-lg">🥈</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-purple-500 text-white rounded-full
                                                flex items-center justify-center text-xs font-bold">
                                        J
                                    </div>
                                    <span class="font-medium text-gray-800">John Smith</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">8</td>
                            <td class="px-6 py-4 text-gray-600">21</td>
                            <td class="px-6 py-4 text-green-600 font-semibold">85%</td>
                            <td class="px-6 py-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full"
                                         style="width: 85%"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="text-orange-400 font-bold text-lg">🥉</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-pink-500 text-white rounded-full
                                                flex items-center justify-center text-xs font-bold">
                                        A
                                    </div>
                                    <span class="font-medium text-gray-800">Alice Nakato</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">5</td>
                            <td class="px-6 py-4 text-gray-600">14</td>
                            <td class="px-6 py-4 text-yellow-600 font-semibold">70%</td>
                            <td class="px-6 py-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full"
                                         style="width: 70%"></div>
                                </div>
                            </td>
                        </tr>
                        {{-- @endforeach --}}
                    </tbody>
                </table>
            </div>

            {{-- QUIZ PERFORMANCE SUMMARY --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold text-gray-700 mb-4">🧪 Quiz Performance Summary</h3>
                <div class="space-y-3">
                    @foreach([
                        ['Week 1 Quiz', 92, 'bg-green-500'],
                        ['Week 2 Quiz', 78, 'bg-green-400'],
                        ['Week 3 Quiz', 65, 'bg-yellow-400'],
                        ['Week 4 Quiz', 55, 'bg-red-400'],
                    ] as [$quiz, $score, $color])
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 w-28 shrink-0">{{ $quiz }}</span>
                        <div class="flex-1 bg-gray-200 rounded-full h-3">
                            <div class="{{ $color }} h-3 rounded-full"
                                 style="width: {{ $score }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-700 w-10 text-right">
                            {{ $score }}%
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>