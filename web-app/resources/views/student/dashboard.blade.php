<x-app-layout>
    <div class="flex min-h-screen bg-gray-50">
        <!-- LEFT SIDEBAR -->
        <div class="w-64 bg-white border-r border-gray-200 p-6 flex flex-col gap-4">
            <h2 class="text-xl font-bold text-gray-800">Student Portal</h2>
            <nav class="flex flex-col gap-2">
                <a href="#" class="bg-gray-100 text-gray-900 px-4 py-2 rounded font-medium">Forums</a>
                <a href="#" class="text-gray-600 hover:bg-gray-50 px-4 py-2 rounded">My Quizzes</a>
            </nav>
        </div>

        <!-- MIDDLE PANE -->
        <div class="flex-1 p-8 overflow-y-auto">
            <!-- QUIZ POPUP ANNOUNCEMENT BANNER (Recess Requirement #10) -->
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-amber-800">⚠️ Live Quiz Alert</h3>
                        <p class="text-sm text-amber-700">A quiz configuration is set to open. It will auto-submit when time expires.</p>
                    </div>
                    <button class="bg-amber-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-amber-700">Enter Quiz</button>
                </div>
            </div>

            <!-- TOPIC ISOLATION AND PDF EXPORT (Recess Requirement #6) -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Topic: Linear Data Chains</h3>
                        <p class="text-xs text-gray-400">Viewing isolated chats for this topic only</p>
                    </div>
                    <button class="bg-gray-900 text-white px-4 py-2 rounded text-xs font-semibold uppercase tracking-wide">
                        Export Thread to PDF
                    </button>
                </div>
                <div class="space-y-4 border-t border-gray-100 pt-4">
                    <div class="bg-gray-50 p-3 rounded">
                        <span class="font-semibold text-sm block">Tony Stark:</span>
                        <p class="text-sm text-gray-600">Has anyone optimized the offline synchronization task?</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDEBAR (Recess Requirement #11 & #12) -->
        <div class="w-80 bg-white border-l border-gray-200 p-6 flex flex-col gap-6">
            <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
                <h4 class="font-bold text-indigo-900 text-sm mb-1">💡 Recommended for You (ML)</h4>
                <p class="text-xs text-indigo-700 mb-2">Based on past engagement profiles:</p>
                <a href="#" class="text-xs font-semibold text-indigo-600 underline block"># Advanced Data Normalization</a>
            </div>
            <div>
                <h4 class="font-bold text-gray-800 text-sm mb-2">Forward Thread</h4>
                <div class="flex gap-2">
                    <button class="bg-blue-600 text-white text-xs px-3 py-1.5 rounded font-medium">Share to Twitter</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
