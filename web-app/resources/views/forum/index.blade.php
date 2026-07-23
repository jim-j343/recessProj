<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Discussion Forum
            </h2>
            @if(auth()->user()->system_role === 'lecturer')
                <a href="/topics/create"
                   class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                    + New Topic
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Search bar --}}
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form method="GET" action="{{ route('forum.index') }}" class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search topics..."
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                        class="shrink-0 bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                        Search
                    </button>
                    @if($search)
                        <a href="{{ route('forum.index') }}"
                           class="shrink-0 flex items-center px-3 text-sm text-gray-500 hover:text-gray-700">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Empty state --}}
            @if($topics->isEmpty())
                <div class="bg-white p-8 rounded-lg shadow-sm text-center text-gray-500">
                    No topics yet. Be the first to start a discussion!
                </div>
            @endif

            {{-- Loop through topics from the database --}}
            @foreach($topics as $topic)
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 flex justify-between items-start">
                <div>
                    <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">
                        {{ $topic->category ?? 'General' }}
                    </span>
                    <h3 class="mt-2 text-lg font-bold text-gray-800">
                        <a href="/topics/{{ $topic->topic_id }}" class="hover:text-indigo-600">
                            {{ $topic->title }}
                        </a>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Posted by {{ $topic->creator->username ?? 'Unknown' }}
                        · {{ $topic->posts_count ?? 0 }} replies
                    </p>
                </div>
                <div class="text-right text-sm">
                    @if($topic->posts_count === 0)
                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold">
                            Unanswered
                        </span>
                    @else
                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">
                            Answered
                        </span>
                    @endif
                </div>
            </div>
            @endforeach

        </div>
    </div>
</x-app-layout>
