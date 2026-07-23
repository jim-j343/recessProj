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
                        <a href="{{ route('quiz.preview', $quiz->quiz_id) }}"
                           class="flex justify-between items-center py-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded transition-colors">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $quiz->title }}</p>
                                <p class="text-xs text-gray-400">{{ $quiz->start_time->format('d M Y H:i') }} · {{ $quiz->duration_minutes }} mins</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $quiz->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $quiz->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </a>
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
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-indigo-600" fill="none"
     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round"
          d="M16.862 4.487l1.651-1.652a2.121 2.121 0 113 3L10.582 16.767a4.5 4.5 0 01-1.897 1.13L6 18l.103-2.685a4.5 4.5 0 011.13-1.897L16.862 4.487z"/>
</svg>

<span>Create a new quiz</span>
                        </a>
                        <a href="{{ route('topics.create') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-600"
     fill="none" viewBox="0 0 24 24"
     stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round"
          d="M8 10h8M8 14h5m-8 6l-4-4V5a2 2 0 012-2h14a2 2 0 012 2v11a2 2 0 01-2 2H7l-3 3z"/>
</svg>

<span>Post a new topic</span>
                        </a>
                        <a href="{{ route('forum.index') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                           <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-purple-600"
     fill="none" viewBox="0 0 24 24"
     stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round"
          d="M2.458 12C3.732 7.943 7.523 5 12 5
             c4.478 0 8.268 2.943 9.542 7
             -1.274 4.057-5.064 7-9.542 7
             -4.477 0-8.268-2.943-9.542-7z"/>
    <circle cx="12" cy="12" r="3"/>
</svg>

<span>View forum</span>
                        </a>
                        <a href="{{ route('participation.grade') }}"
                           class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 text-sm font-medium text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-500"
     fill="none" viewBox="0 0 24 24"
     stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round"
          d="M8 21h8M12 17v4M7 4h10v4a5 5 0 01-10 0V4zm10 1h3a2 2 0 010 4h-3M7 5H4a2 2 0 000 4h3"/>
</svg>

<span>Award participation marks</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection