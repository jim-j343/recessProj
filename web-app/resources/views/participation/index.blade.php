<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📊 My Participation & Marks
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- SUMMARY CARDS --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-indigo-600">8</p>
                    <p class="text-sm text-gray-500 mt-1">Topics Participated In</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-green-600">24</p>
                    <p class="text-sm text-gray-500 mt-1">Replies Posted</p>
                </div>
                <div class="bg-white rounded-lg shadow p-5 text-center">
                    <p class="text-3xl font-bold text-yellow-500">3</p>
                    <p class="text-sm text-gray-500 mt-1">Quizzes Taken</p>
                </div>
            </div>

            {{-- QUIZ MARKS TABLE --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">🧪 Quiz Results</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Quiz</th>
                            <th class="px-6 py-3 text-left">Date Taken</th>
                            <th class="px-6 py-3 text-left">Score</th>
                            <th class="px-6 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Later: @foreach($quizResults as $result) --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">Week 1 Quiz</td>
                            <td class="px-6 py-4 text-gray-500">10 June 2026</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-green-600">18 / 20</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-green-100 text-green-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    Passed
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">Week 2 Quiz</td>
                            <td class="px-6 py-4 text-gray-500">17 June 2026</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-yellow-600">12 / 20</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    Passed
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">Week 3 Quiz</td>
                            <td class="px-6 py-4 text-gray-500">24 June 2026</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-red-600">7 / 20</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="bg-red-100 text-red-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    Failed
                                </span>
                            </td>
                        </tr>
                        {{-- @endforeach --}}
                    </tbody>
                </table>
            </div>

            {{-- FORUM PARTICIPATION TABLE --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">💬 Forum Activity</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Topic</th>
                            <th class="px-6 py-3 text-left">Replies</th>
                            <th class="px-6 py-3 text-left">Last Active</th>
                            <th class="px-6 py-3 text-left">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Later: @foreach($forumActivity as $activity) --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                <a href="#" class="text-indigo-600 hover:underline">
                                    What is Machine Learning?
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">4 replies</td>
                            <td class="px-6 py-4 text-gray-500">2 days ago</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-green-600">A</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800">
                                <a href="#" class="text-indigo-600 hover:underline">
                                    Introduction to Database Normalization
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">2 replies</td>
                            <td class="px-6 py-4 text-gray-500">5 days ago</td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-yellow-600">B</span>
                            </td>
                        </tr>
                        {{-- @endforeach --}}
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>