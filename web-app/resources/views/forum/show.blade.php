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
    <header class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center sticky top-0 z-10">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}"
                class="text-gray-400 hover:text-gray-700 transition-colors text-sm">
                ← Back
            </a>
            <h1 class="font-semibold text-gray-900 text-base">
                {{ $topic->title ?? 'Why are we still using Webpack in 2024?' }}
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <button class="text-gray-400 hover:text-gray-700 transition-colors text-lg">⊞</button>
            <a href="/topics/{{ $topic->id ?? 1 }}/export-pdf"
                class="text-gray-400 hover:text-gray-700 transition-colors text-lg">↗↗</a>
        </div>
    </header>

    {{-- THREAD CONTENT --}}
    <main class="max-w-3xl mx-auto py-8 px-4 pb-32">

        {{-- @foreach($posts as $post) --}}

        {{-- ORIGINAL POST --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-indigo-200 overflow-hidden flex items-center justify-center">
                    <span class="text-indigo-700 font-semibold text-sm">S</span>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">
                        Sarah Chen
                        <span class="font-normal text-gray-400 text-xs ml-2">2h ago • Author</span>
                    </p>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-gray-700 text-sm leading-relaxed">
                    {{ $topic->description ?? 'With Vite, Esbuild, and Turbopack offering near-instant cold starts and lightning-fast HMR, why is the industry still so tied to Webpack? Is it just inertia, or are there complex enterprise use cases that these newer tools still haven\'t solved?' }}
                </p>
            </div>
            <div class="flex items-center gap-4 mt-3 px-1">
                <button class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-700 transition-colors">
                    👍 <span>124</span>
                </button>
                <button class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-gray-700 transition-colors">
                    💬 <span>42</span>
                </button>
            </div>
        </div>

        {{-- REPLY 1 --}}
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-purple-200 flex items-center justify-center">
                    <span class="text-purple-700 font-semibold text-sm">D</span>
                </div>
                <p class="text-sm font-semibold text-gray-900">
                    David Müller
                    <span class="font-normal text-gray-400 text-xs ml-2">1h ago</span>
                </p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-gray-700 text-sm leading-relaxed">
                    Plugin ecosystem. If you have a custom build pipeline developed over 5 years with 20+ specialized plugins, migrating to Vite isn't a weekend project. Enterprise stability > HMR speed for many.
                </p>
            </div>

            {{-- NESTED REPLY --}}
            <div class="ml-10 mt-3">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-orange-200 flex items-center justify-center">
                        <span class="text-orange-700 font-semibold text-xs">A</span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900">
                        Alex Rivera
                        <span class="font-normal text-gray-400 text-xs ml-2">45m ago</span>
                    </p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 border-l-4 border-l-gray-300">
                    <p class="text-gray-700 text-sm leading-relaxed">
                        This. We tried migrating a legacy monorepo and hit a wall with some obscure federated module requirements that Vite didn't quite handle the same way.
                    </p>
                </div>
            </div>
        </div>

        {{-- REPLY 2 --}}
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-green-200 flex items-center justify-center">
                    <span class="text-green-700 font-semibold text-sm">J</span>
                </div>
                <p class="text-sm font-semibold text-gray-900">
                    Jessica Wu
                    <span class="font-normal text-gray-400 text-xs ml-2">30m ago</span>
                </p>
            </div>
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-gray-700 text-sm leading-relaxed">
                    I think the "inertia" argument is getting weaker. New projects almost exclusively start with Vite now. Webpack is becoming the "COBOL of the frontend" — it'll be around forever in legacy, but the innovation has moved on.
                </p>
            </div>
        </div>

        {{-- REPLY 3 (highlighted/recent) --}}
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-blue-200 flex items-center justify-center">
                    <span class="text-blue-700 font-semibold text-sm">M</span>
                </div>
                <p class="text-sm font-semibold text-gray-900">
                    Marcus Thorne
                    <span class="font-normal text-gray-400 text-xs ml-2">12m ago</span>
                </p>
            </div>
            <div class="bg-white border-2 border-gray-900 rounded-lg p-5">
                <p class="text-gray-700 text-sm leading-relaxed">
                    Rspack is actually the middle ground. It's a high-performance Rust drop-in for Webpack. That might be the real future for those "stuck" in the ecosystem.
                </p>
            </div>
        </div>

        {{-- @endforeach --}}

        {{-- Lecturer: Mark as Answer --}}
        @if(isset(auth()->user()->role) && auth()->user()->role === 'lecturer')
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <p class="text-sm text-green-700 font-semibold">
                ✓ Mark a reply as the accepted answer by clicking the checkmark next to it.
            </p>
        </div>
        @endif

    </main>

    {{-- STICKY REPLY BAR AT BOTTOM --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 z-10">
        <form method="POST"
            action="/topics/{{ $topic->id ?? 1 }}/posts"
            class="max-w-3xl mx-auto flex items-center gap-3">
            @csrf
            <input type="text"
                name="content"
                placeholder="Add to the conversation..."
                class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm
                       focus:outline-none focus:border-gray-400 focus:ring-0 transition-colors" />
            <button type="submit"
                class="bg-gray-900 text-white w-10 h-10 rounded-lg flex items-center justify-center
                       hover:bg-gray-700 transition-colors shrink-0">
                →
            </button>
            <button type="button"
                class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center
                       hover:bg-gray-50 transition-colors shrink-0 text-gray-400">
                +
            </button>
        </form>
    </div>

</body>
</html>