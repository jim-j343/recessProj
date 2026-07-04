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
            {{-- [Export to PDF] Button (SDD requirement) --}}
            <a href="{{ route('topics.pdf', $topic->topic_id ?? 1) }}"
                class="flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50
                       px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                ↓ Export PDF
            </a>
            {{-- [Share] Button --}}
            <button onclick="document.getElementById('share-modal').classList.remove('hidden')"
                class="flex items-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50
                       px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                ↗ Share
            </button>
        </div>
    </header>

    {{-- SHARE MODAL (Forward to Social Media) --}}
    <div id="share-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Share this topic</h3>
                <button onclick="document.getElementById('share-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="#" class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    𝕏 Twitter / X
                </a>
                <a href="#" class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    in LinkedIn
                </a>
                <a href="#" class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    f Facebook
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
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                    <span class="text-indigo-700 font-semibold text-sm">
                        {{ strtoupper(substr($topic->creator->username ?? 'S', 0, 1)) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $topic->creator->username ?? 'Unknown' }}
                        <span class="font-normal text-gray-400 text-xs ml-2">{{ $topic->created_at?->diffForHumans() ?? '2h ago' }}</span>
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full ml-1 font-medium">Author</span>
                    </p>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-gray-700 text-sm leading-relaxed">
                    {{ $topic->description ?? 'With Vite, Esbuild, and Turbopack offering near-instant cold starts and lightning-fast HMR, why is the industry still so tied to Webpack?' }}
                </p>
            </div>
        </div>

        {{-- RUN DYNAMIC DB POSTS/REPLIES LOOP --}}
        <div class="space-y-6">
            @forelse($posts as $post)
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center shrink-0">
                            <span class="text-purple-700 font-semibold text-sm">
                                {{ strtoupper(substr($post->author->username ?? $post->author->name ?? 'D', 0, 1)) }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $post->author->username ?? $post->author->name ?? 'Anonymous User' }}
                            <span class="font-normal text-gray-400 text-xs ml-2">{{ $post->created_at->diffForHumans() }}</span>
                        </p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-5">
                        <p class="text-gray-700 text-sm leading-relaxed">
                            {{ $post->content }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-400 text-sm py-8">No replies posted yet. Start the conversation below!</p>
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
            class="max-w-3xl mx-auto flex items-center gap-3">
            @csrf
            <input type="text"
                id="reply-input"
                name="content"
                placeholder="Add to the conversation..."
                class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm
                       focus:outline-none focus:border-gray-400 focus:ring-0 transition-colors" />

            <button type="submit"
                class="bg-gray-900 text-white px-4 h-10 rounded-lg flex items-center gap-1.5 text-xs font-semibold
                       hover:bg-gray-700 transition-colors shrink-0">
                ↩ Reply
            </button>
        </form>
    </div>

</body>
</html>
