@php
use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Topic | Smart Discussion Forum</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- TOP BAR --}}
    <header class="bg-white border-b border-gray-200 px-6 py-3 flex justify-between items-center sticky top-0 z-10">
        <div class="flex items-center gap-4 min-w-0">
            <a href="{{ route('dashboard') }}"
                class="text-gray-400 hover:text-gray-700 transition-colors text-sm shrink-0">
                ← Back
            </a>
            <h1 class="font-semibold text-gray-900 text-sm truncate">
                {{ $topic->title ?? 'Why are we still using Webpack in 2024?' }}
            </h1>
        </div>

        {{-- SDD: Export to PDF + Forward to Social Media --}}
        <div class="flex items-center gap-2 shrink-0 ml-4">

    {{-- Export PDF --}}
    <a href="{{ route('topics.pdf', $topic) }}"
        class="flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50
               px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
        ↓ Export PDF
    </a>

    {{-- Share --}}
    <button onclick="document.getElementById('share-modal').classList.remove('hidden')"
        class="flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50
               px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
        ↗ Share
    </button>
    

    {{-- Edit/Delete (Owner or Admin only) --}}
   @if(true)
        <a href="{{ route('topics.edit', $topic) }}"
           class="flex items-center gap-1.5 border border-blue-200 text-blue-700 hover:bg-blue-50
                  px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
            ✏ Edit
        </a>

        <form method="POST"
              action="{{ route('topics.destroy', $topic) }}"
              onsubmit="return confirm('Delete this topic?')"
              class="inline">

            @csrf
            @method('DELETE')

            <button type="submit"
                class="flex items-center gap-1.5 border border-red-200 text-red-700 hover:bg-red-50
                       px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                🗑 Delete
            </button>

        </form>

    @endif
        </div>
    </header>
        <div class="bg-yellow-100 p-3 rounded text-sm mb-4">
            Logged in ID: {{ auth()->id() }} <br>
            Topic creator ID: {{ $topic->creator_id }} <br>
            Role: {{ auth()->user()->system_role }}
        </div>  

    {{-- SHARE MODAL (Forward to Social Media) --}}
    <div id="share-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Share this topic</h3>
                <button onclick="document.getElementById('share-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}
                https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->fullUrl()) }}
                https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}
                <a href="https://wa.me/?text={{ urlencode(request()->fullUrl()) }}"
                     target="_blank"
                     class="...">

                      WhatsApp

                </a>
                <button onclick="navigator.clipboard.writeText(window.location.href); this.textContent='✓ Copied!'"
                    class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    🔗 Copy link
                </button>
            </div>
        </div>
    </div>

    {{-- THREAD CONTENT --}}
    <main class="max-w-3xl mx-auto py-8 px-4 pb-32">

        {{-- ORIGINAL TOPIC POST --}}
    <div class="mb-8">

        <div class="bg-white rounded-xl border border-indigo-200 shadow-md overflow-hidden">

         {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-indigo-50">

                <div class="flex items-center gap-3">

                 <div
                     class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center text-lg font-bold">

                     {{ strtoupper(substr($topic->creator->username ?? 'U',0,1)) }}

                    </div>

                 <div>

                     <div class="flex items-center gap-2">

                        <h3 class="font-semibold text-gray-900">
                            {{ $topic->creator->username ?? 'Unknown User' }}
                        </h3>

                        <span
                            class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">
                            Author
                        </span>

                    </div>

                    <p class="text-xs text-gray-500">
                        {{ $topic->created_at->format('M d, Y • h:i A') }}
                    </p>

                </div>

            </div>

            @if($topic->category)

                <span
                    class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">

                    {{ $topic->category }}

                </span>

            @endif

        </div>

        {{-- Body --}}
        <div class="p-6">

            <h2 class="text-2xl font-bold text-gray-900 mb-4">

                {{ $topic->title }}

            </h2>

            <p class="text-gray-700 leading-8">

                {{ $firstPost->content ?? 'No description available.' }}

            </p>

            </div>

        </div>

    </div>

        {{-- RUN DYNAMIC DB POSTS/REPLIES LOOP --}}
        <div class="space-y-6">
            @forelse($posts as $post)

    {{-- Skip the first post because it is already displayed as the topic --}}
    @continue($firstPost && $post->post_id == $firstPost->post_id)

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-lg hover:border-indigo-300 transition-all duration-200">

        {{-- Reply Header --}}
        <div class="flex justify-between items-center px-5 py-4 border-b">

            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">

                    {{ strtoupper(substr($post->author->username ?? 'U',0,1)) }}

                </div>

                <div>

                    <div class="flex items-center gap-2">

                        <span class="font-semibold text-gray-900">
                            {{ $post->author->username }}
                        </span>

                        @if($post->author_id == $topic->creator_id)

                            <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">
                                Author
                            </span>

                        @elseif($post->author->system_role == 'lecturer')

                            <span class="bg-green-600 text-white text-xs px-2 py-1 rounded-full">
                                Lecturer
                            </span>

                        @elseif($post->author->system_role == 'system_admin')

                            <span class="bg-red-600 text-white text-xs px-2 py-1 rounded-full">
                                Admin
                            </span>

                        @endif

                    </div>

                    <p class="text-xs text-gray-500">

                        {{ $post->created_at->format('M d, Y • h:i A') }}

                    </p>

                </div>

            </div>

        </div>

        {{-- Reply Body --}}
        <div class="p-5">

            <p class="text-gray-700 leading-7">

                {{ $post->content }}

            </p>
          @if($post->attachment)

    <div class="mt-4">

        @if(Str::startsWith($post->attachment_type, 'image/'))

            <img src="{{ asset('storage/'.$post->attachment) }}"
                 alt="Attachment"
                 class="rounded-lg border max-h-72">

        @else

            <a href="{{ asset('storage/'.$post->attachment) }}"
               target="_blank"
               class="inline-flex items-center gap-2 text-blue-600 hover:underline">

                📎 {{ $post->attachment_name }}

            </a>

        @endif

    </div>

@endif  
        </div>

        {{-- Reply Footer --}}
        <div class="px-5 py-3 border-t flex gap-4 text-sm">

            <button class="text-indigo-600 hover:text-indigo-800">
                💬 Reply
            </button>
<div class="text-xs text-red-500">
    Logged in: {{ auth()->id() }} |
    Author: {{ $post->author_id }} |
    Role: {{ auth()->user()->system_role }}
</div>
           @if(auth()->id()==$post->author_id || auth()->user()->system_role=='system_admin')

    <a href="{{ route('posts.edit', $post) }}"
       class="text-blue-600 hover:text-blue-800">
        ✏ Edit
    </a>

    <form method="POST"
          action="{{ route('posts.destroy', $post) }}"
          onsubmit="return confirm('Delete this reply?')"
          class="inline">

        @csrf
        @method('DELETE')

        <button type="submit"
                class="text-red-600 hover:text-red-800">
            🗑 Delete
        </button>

    </form>

@endif
        </div>

    </div>

@empty

    <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">

        <p class="text-gray-400">

            No replies yet.

            <br>

            Be the first to join the discussion!

        </p>

    </div>

@endforelse
        </div>

        {{-- Lecturer Action Guide --}}
        @if(auth()->check() && auth()->user()->system_role === 'lecturer')
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mt-6">
            <p class="text-sm text-green-700 font-semibold">
                ✓ Lecturer Console Action: You can award marks or highlight solutions directly inside thread panels.
            </p>
        </div>
        @endif

    </main>

    {{-- STICKY REPLY BAR --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 z-10">
       <form method="POST"
    action="/topics/{{ $topic->topic_id ?? 1 }}/posts"
    enctype="multipart/form-data"
    class="max-w-3xl mx-auto flex items-center gap-3">
            @csrf
            <input type="text"
                id="reply-input"
                name="content"
                placeholder="Add to the conversation..."
                class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm
                       focus:outline-none focus:border-gray-400 focus:ring-0 transition-colors" />
                <input
            type="file"
            name="attachment"
            accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.ppt,.pptx,.zip"
            class="text-sm text-gray-600">      

            <button type="submit"
                class="bg-gray-900 text-white px-4 h-10 rounded-lg flex items-center gap-1.5 text-xs font-semibold
                       hover:bg-gray-700 transition-colors shrink-0">
                ↩ Reply
            </button>
        </form>
    </div>

</body>
</html>
