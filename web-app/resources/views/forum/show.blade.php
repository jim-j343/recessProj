<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">{{ $topic->title }}</h2>
            <a href="/topics/{{ $topic->id }}/export-pdf"
               class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200">
                Export to PDF
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Each post/reply in the thread --}}
            @foreach($posts as $post)
            <div class="bg-white shadow-sm rounded-lg p-6 {{ $post->is_answer ? 'border-l-4 border-green-500' : '' }}">
                <div class="flex items-center mb-3">
                    <div class="w-8 h-8 bg-indigo-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-gray-800">{{ $post->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                    </div>
                    @if($post->is_answer)
                        <span class="ml-auto text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold">
                            ✓ Accepted Answer
                        </span>
                    @endif
                </div>
                <p class="text-gray-700">{{ $post->body }}</p>
            </div>
            @endforeach

            {{-- Reply form --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h4 class="font-semibold text-gray-800 mb-3">Post a Reply</h4>
                <form method="POST" action="/topics/{{ $topic->id }}/posts">
                    @csrf
                    <textarea name="content" rows="4" required
                              placeholder="Write your reply here..."
                              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </textarea>
                    <button type="submit"
                            class="mt-3 bg-indigo-600 text-white px-6 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                        Submit Reply
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>