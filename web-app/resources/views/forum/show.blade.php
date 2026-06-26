<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            {{-- Back button + Title --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}"
                    class="text-gray-500 hover:text-gray-700 text-sm">
                    ← Back
                </a>
                <h2 class="font-semibold text-xl text-gray-800">
                    {{ $topic->title }}
                </h2>
            </div>

            {{-- Export to PDF --}}
            <a href="/topics/{{ $topic->id }}/export-pdf"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200">
                📄 Export to PDF
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Posts and replies --}}
            @foreach($posts as $post)
            <div class="bg-white shadow-sm rounded-lg p-6
                {{ $post->is_answer ? 'border-l-4 border-green-500' : '' }}
                {{ $post->parent_id ? 'ml-10 border-l-2 border-gray-200' : '' }}">

                {{-- Post header: Avatar + Name + Time --}}
                <div class="flex items-center mb-3">
                    <div class="w-9 h-9 bg-indigo-500 text-white rounded-full flex items-center
                                justify-center text-sm font-bold shrink-0">
                        {{ strtoupper(substr($post->user->name, 0, 1)) }}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-semibold text-gray-800">{{ $post->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $post->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Accepted Answer badge --}}
                    @if($post->is_answer)
                        <span class="ml-auto text-xs bg-green-100 text-green-700 px-2 py-1
                                     rounded-full font-semibold">
                            ✓ Accepted Answer
                        </span>
                    @endif

                    {{-- Lecturer: Mark as Answer button --}}
                    @if(auth()->user()->role === 'lecturer' && !$post->is_answer)
                        <form method="POST" action="/posts/{{ $post->id }}/mark-answer"
                              class="ml-auto">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="text-xs text-green-600 border border-green-400 px-2 py-1
                                       rounded hover:bg-green-50">
                                ✓ Mark as Answer
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Post content --}}
                <p class="text-gray-700 text-sm leading-relaxed">{{ $post->content }}</p>

                {{-- Like + Reply buttons --}}
                <div class="flex items-center gap-4 mt-4">
                    <form method="POST" action="/posts/{{ $post->id }}/like">
                        @csrf
                        <button type="submit"
                            class="text-xs text-gray-500 hover:text-indigo-600 flex items-center gap-1">
                            👍 Like
                            <span class="text-gray-400">({{ $post->likes_count ?? 0 }})</span>
                        </button>
                    </form>

                    <button onclick="document.getElementById('reply-{{ $post->id }}').classList.toggle('hidden')"
                        class="text-xs text-gray-500 hover:text-indigo-600">
                        💬 Reply
                    </button>
                </div>

                {{-- Inline reply form (hidden by default) --}}
                <div id="reply-{{ $post->id }}" class="hidden mt-4">
                    <form method="POST" action="/topics/{{ $topic->id }}/posts">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $post->id }}" />
                        <textarea name="content" rows="3" required
                            placeholder="Write your reply..."
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm
                                   focus:ring-indigo-500 focus:border-indigo-500">
                        </textarea>
                        <button type="submit"
                            class="mt-2 bg-indigo-600 text-white px-4 py-1.5 rounded-md
                                   text-xs font-medium hover:bg-indigo-700">
                            Post Reply
                        </button>
                    </form>
                </div>

            </div>
            @endforeach

            {{-- Main reply form at the bottom --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h4 class="font-semibold text-gray-800 mb-3">Post a Reply</h4>
                <form method="POST" action="/topics/{{ $topic->id }}/posts">
                    @csrf
                    <textarea name="content" rows="4" required
                        placeholder="Write your reply here..."
                        class="w-full border-gray-300 rounded-md shadow-sm
                               focus:ring-indigo-500 focus:border-indigo-500">
                    </textarea>
                    <button type="submit"
                        class="mt-3 bg-indigo-600 text-white px-6 py-2 rounded-md
                               text-sm font-medium hover:bg-indigo-700">
                        Submit Reply
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>