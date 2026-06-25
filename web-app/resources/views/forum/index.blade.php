<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Discussion Forum
            </h2>
            @if(auth()->user()->role === 'lecturer')
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
                <input type="text" placeholder="Search topics..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            {{-- Loop through topics from the database --}}
            @foreach($topics as $topic)
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 flex justify-between items-start">
                <div>
                    <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">
                        {{ $topic->category }}
                    </span>
                    <h3 class="mt-2 text-lg font-bold text-gray-800">
                        <a href="/topics/{{ $topic->id }}" class="hover:text-indigo-600">
                            {{ $topic->title }}
                        </a>
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Posted by {{ $topic->user->name }} · {{ $topic->posts_count }} replies
                    </p>
                </div>
                <div class="text-right text-sm">
                    @if($topic->has_unanswered)
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