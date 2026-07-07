<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lecturer Dashboard</h2>
            <a href="{{ route('quiz.create') }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                + New Quiz
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Quick stats --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ $quizCount ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">Quizzes created</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $groupCount ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">Groups joined</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-2xl font-bold text-gray-700">{{ $topicCount ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">Topics created</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                {{-- My quizzes --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold text-gray-900">My Quizzes</h3>
                        <a href="{{ route('quiz.create') }}" class="text-sm text-indigo-600 hover:underline">+ New</a>
                    </div>
                    @forelse($quizzes ?? [] as $quiz)
                        <div class="flex justify-between items-center py-3 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $quiz->title }}</p>
                                <p class="text-xs text-gray-400">{{ $quiz->start_time->format('d M Y H:i') }} · {{ $quiz->duration_minutes }} mins</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $quiz->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $quiz->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No quizzes yet. Create your first one.</p>
                    @endforelse
                </div>

                {{-- Quick actions --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('quiz.create') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            📝 Create a new quiz
                        </a>
                        <a href="{{ route('topics.create') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            💬 Post a new topic
                        </a>
                        <a href="{{ route('forum.index') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            👁 View forum
                        </a>
                        <a href="{{ route('participation.grade') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            🏆 Award participation marks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
