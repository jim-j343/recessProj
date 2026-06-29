<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">📝 Participation Grading</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- FILTERS --}}
            <div class="bg-white rounded-lg shadow p-4 flex gap-4 items-end">
                <div class="flex-1">
                    <x-input-label :value="__('Filter by Topic')" />
                    <select class="block mt-1 w-full border-gray-300 rounded-md shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Topics</option>
                        <option>What is Machine Learning?</option>
                        <option>Introduction to Database Normalization</option>
                    </select>
                </div>
                <div class="flex-1">
                    <x-input-label :value="__('Filter by Student')" />
                    <x-text-input type="text" class="block mt-1 w-full"
                        placeholder="Search student name..." />
                </div>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg
                               text-sm hover:bg-indigo-700">
                    Filter
                </button>
            </div>

            {{-- GRADING TABLE --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700">Student Forum Participation</h3>
                    <button class="text-sm text-indigo-600 hover:underline">
                        💾 Save All Grades
                    </button>
                </div>

                <form method="POST" action="/participation/grade/save">
                    @csrf
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3 text-left">Student</th>
                                <th class="px-6 py-3 text-left">Topic</th>
                                <th class="px-6 py-3 text-left">Replies</th>
                                <th class="px-6 py-3 text-left">Quality</th>
                                <th class="px-6 py-3 text-left">Grade</th>
                                <th class="px-6 py-3 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">

                            {{-- Later: @foreach($participations as $p) --}}

                            {{-- Row 1 --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-indigo-500 text-white rounded-full
                                                    flex items-center justify-center text-xs font-bold">
                                            J
                                        </div>
                                        <span class="font-medium text-gray-800">Jane Doe</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">
                                    What is Machine Learning?
                                </td>
                                <td class="px-6 py-4 text-gray-500">4 replies</td>
                                <td class="px-6 py-4">
                                    <span class="bg-green-100 text-green-700 px-2 py-1
                                                 rounded-full text-xs">
                                        High
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <select name="grades[1]"
                                        class="border-gray-300 rounded-md shadow-sm text-sm
                                               focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">--</option>
                                        <option value="A" selected>A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="F">F</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="remarks[1]"
                                        placeholder="Optional remark..."
                                        value="Very active contributor"
                                        class="border-gray-300 rounded-md shadow-sm text-xs
                                               focus:ring-indigo-500 focus:border-indigo-500 w-40" />
                                </td>
                            </tr>

                            {{-- Row 2 --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-purple-500 text-white rounded-full
                                                    flex items-center justify-center text-xs font-bold">
                                            J
                                        </div>
                                        <span class="font-medium text-gray-800">John Smith</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">
                                    What is Machine Learning?
                                </td>
                                <td class="px-6 py-4 text-gray-500">2 replies</td>
                                <td class="px-6 py-4">
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1
                                                 rounded-full text-xs">
                                        Medium
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <select name="grades[2]"
                                        class="border-gray-300 rounded-md shadow-sm text-sm
                                               focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">--</option>
                                        <option value="A">A</option>
                                        <option value="B" selected>B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="F">F</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="remarks[2]"
                                        placeholder="Optional remark..."
                                        class="border-gray-300 rounded-md shadow-sm text-xs
                                               focus:ring-indigo-500 focus:border-indigo-500 w-40" />
                                </td>
                            </tr>

                            {{-- Row 3 --}}
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-pink-500 text-white rounded-full
                                                    flex items-center justify-center text-xs font-bold">
                                            A
                                        </div>
                                        <span class="font-medium text-gray-800">Alice Nakato</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 text-xs">
                                    Database Normalization
                                </td>
                                <td class="px-6 py-4 text-gray-500">1 reply</td>
                                <td class="px-6 py-4">
                                    <span class="bg-red-100 text-red-700 px-2 py-1
                                                 rounded-full text-xs">
                                        Low
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <select name="grades[3]"
                                        class="border-gray-300 rounded-md shadow-sm text-sm
                                               focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">--</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C" selected>C</option>
                                        <option value="D">D</option>
                                        <option value="F">F</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <input type="text" name="remarks[3]"
                                        placeholder="Optional remark..."
                                        value="Needs to participate more"
                                        class="border-gray-300 rounded-md shadow-sm text-xs
                                               focus:ring-indigo-500 focus:border-indigo-500 w-40" />
                                </td>
                            </tr>

                            {{-- @endforeach --}}

                        </tbody>
                    </table>

                    {{-- Save Button --}}
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button type="submit"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg
                                   text-sm font-semibold hover:bg-indigo-700">
                            💾 Save All Grades
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>