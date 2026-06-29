<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-900">Quiz Configuration</h2>
                <p class="text-sm text-gray-400 mt-0.5">
                    Set up your assessment parameters and preview student experience.
                </p>
            </div>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-900">Dashboard</a>
                <a href="#" class="text-gray-900 font-semibold border-b-2 border-gray-900 pb-0.5">Quiz Center</a>
                <a href="#" class="text-gray-500 hover:text-gray-900">Students</a>
            </nav>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-3xl mx-auto px-4 space-y-6">

            <form method="POST" action="/quiz/store">
                @csrf

                {{-- SECTION 1: General Details --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                        ⚙ General Details
                    </h3>

                    {{-- Quiz Title --}}
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                            Quiz Title
                        </label>
                        <input type="text" name="title"
                            value="Advanced Macroeconomics Midterm"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                   focus:outline-none focus:border-gray-400 transition-colors bg-white" />
                    </div>

                    {{-- Start Date + Duration --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                Start Date & Time
                            </label>
                            <input type="datetime-local" name="start_time"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                       focus:outline-none focus:border-gray-400 transition-colors bg-white" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                Duration (Minutes)
                            </label>
                            <input type="number" name="duration" value="60"
                                class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                       focus:outline-none focus:border-gray-400 transition-colors bg-white" />
                        </div>
                    </div>

                    {{-- Student Category --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                            Student Category
                        </label>
                        <select name="target"
                            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-sm
                                   focus:outline-none focus:border-gray-400 transition-colors bg-white">
                            <option>Undergraduate - Level 3</option>
                            <option>Undergraduate - Level 1</option>
                            <option>Undergraduate - Level 2</option>
                            <option>All Students</option>
                        </select>
                    </div>
                </div>

                {{-- SECTION 2: Student View Preview --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                            👁 Student View Preview
                        </h3>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-semibold">
                            LIVE PREVIEW
                        </span>
                    </div>

                    {{-- Preview Card --}}
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        {{-- Red Timer Bar --}}
                        <div class="bg-red-600 px-4 py-3 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-white text-sm">⏱</span>
                                <span class="text-white text-xs font-bold uppercase tracking-wide">
                                    Time Remaining
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-white text-xl font-bold font-mono">14:32</span>
                                <span class="text-white text-xs">MIN</span>
                            </div>
                        </div>
                        {{-- Preview Body --}}
                        <div class="p-4 bg-gray-50 space-y-2">
                            <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="h-10 bg-white border border-gray-200 rounded"></div>
                                <div class="h-10 bg-white border border-gray-200 rounded"></div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 italic mt-3">
                        The red alert banner triggers when 15 minutes remain to create psychological urgency for completion.
                    </p>
                </div>

                {{-- DEPLOY + SAVE BUTTONS --}}
                <button type="submit"
                    class="w-full bg-gray-900 text-white py-4 rounded-lg font-semibold
                           flex items-center justify-center gap-2 hover:bg-gray-700 transition-colors">
                    🚀 Deploy Assessment
                </button>

                <button type="button"
                    class="w-full bg-white border border-gray-200 text-gray-700 py-4 rounded-lg
                           font-semibold flex items-center justify-center gap-2
                           hover:bg-gray-50 transition-colors">
                    💾 Save as Draft
                </button>

            </form>
        </div>
    </div>
</x-app-layout>