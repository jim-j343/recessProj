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

            {{-- Edit/Delete (Owner or Admin only) — topics.edit is a real,
                 working route (TopicController@edit/@update) --}}
            @if(auth()->id() === $topic->creator_id || auth()->user()->isAdmin())

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

    {{-- SHARE MODAL — real brand icons instead of emoji/unicode approximations --}}
    <div id="share-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Share this topic</h3>
                <button onclick="document.getElementById('share-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
            </div>
            <div class="grid grid-cols-2 gap-3">

                <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($topic->title) }}"
                   target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    X (Twitter)
                </a>

                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->fullUrl()) }}"
                   target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="#0A66C2">
                        <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.03-1.85-3.03-1.86 0-2.14 1.45-2.14 2.94v5.66H9.36V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.38-1.85 3.6 0 4.27 2.37 4.27 5.46zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.11 20.45H3.56V9h3.55z"/>
                    </svg>
                    LinkedIn
                </a>

                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"
                   target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="#1877F2">
                        <path d="M22 12.06C22 6.51 17.52 2 12 2S2 6.51 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.91h-2.34v7.03C18.34 21.21 22 17.06 22 12.06z"/>
                    </svg>
                    Facebook
                </a>

                <a href="https://wa.me/?text={{ urlencode($topic->title.' — '.request()->fullUrl()) }}"
                   target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 border border-green-200 rounded-lg py-2.5 text-sm font-semibold text-green-700 hover:bg-green-50">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="#25D366">
                        <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm5.9 14.2c-.3.7-1.5 1.3-2.1 1.4-.5.1-1.2.2-3.6-.8-3.1-1.3-5.1-4.5-5.3-4.7-.1-.2-1.3-1.7-1.3-3.2s.8-2.3 1.1-2.6c.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .6.5.2.5.7 1.8.8 1.9.1.2.1.3 0 .5-.1.2-.1.3-.3.5l-.4.5c-.1.1-.3.3-.1.6.2.3.9 1.4 1.9 2.3 1.3 1.2 2.4 1.5 2.7 1.7.3.2.5.1.6-.1l.9-1c.2-.3.4-.2.7-.1l1.7.8c.2.1.4.2.5.3.1.2.1.9-.2 1.6z"/>
                    </svg>
                    WhatsApp
                </a>

                <button
                    onclick="navigator.clipboard.writeText(window.location.href); this.textContent='✓ Link copied';"
                    class="col-span-2 flex items-center justify-center gap-2 border border-gray-200 rounded-lg py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                    🔗 Copy Link
                </button>
            </div>
        </div>
    </div>

    {{-- THREAD CONTENT --}}
    <main class="max-w-3xl mx-auto py-8 px-4 pb-32">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">
                <p class="font-semibold mb-1">Your reply couldn't be posted:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm font-semibold">
                {{ session('error') }}
            </div>
        @endif

        {{-- ORIGINAL TOPIC POST --}}
        <div class="mb-6">
            <div class="bg-white rounded-xl border border-indigo-200 shadow-md overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b bg-indigo-50">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center text-lg font-bold">
                            {{ strtoupper(substr($topic->creator->username ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-900">
                                    {{ $topic->creator->username ?? 'Unknown User' }}
                                </h3>
                                <span class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full">
                                    Author
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ $topic->created_at->format('M d, Y • h:i A') }}
                            </p>
                        </div>
                    </div>

                    @if($topic->category)
                        <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
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

        {{-- CHAT THREAD — WhatsApp-style bubbles: your own replies align right
             in green, everyone else's align left in white --}}
        <div class="bg-slate-100 rounded-xl p-4 sm:p-6 space-y-2">
            @forelse($posts as $post)

                {{-- Skip the first post because it is already displayed as the topic --}}
                @continue($firstPost && $post->post_id == $firstPost->post_id)

                @php $isOwn = auth()->id() == $post->author_id; @endphp

                <div id="post-{{ $post->post_id }}"
                     x-data="{ menuOpen: false }"
                     class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">

                    <div class="flex items-end gap-2 max-w-[85%] sm:max-w-[70%] {{ $isOwn ? 'flex-row-reverse' : '' }}">

                        {{-- Avatar — only shown for other people's messages,
                             same convention WhatsApp uses in group chats --}}
                        @unless($isOwn)
                            <div class="w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs font-bold shrink-0 mb-1">
                                {{ strtoupper(substr($post->author->username ?? 'U', 0, 1)) }}
                            </div>
                        @endunless

                        <div class="relative {{ $isOwn
                                ? 'bg-green-200 rounded-3xl rounded-tr-md'
                                : 'bg-white rounded-3xl rounded-tl-md border border-gray-200' }}
                                px-4 py-3 pr-8 shadow">

                            {{-- Menu trigger — anchored INSIDE the bubble's own
                                 corner with a visible background circle, not
                                 floating in the blank page margin like before --}}
                            @if($isOwn || auth()->user()->system_role == 'system_admin')
                                <button @click="menuOpen = !menuOpen" @click.outside="menuOpen = false"
                                    class="absolute top-1.5 right-1.5 w-5 h-5 flex items-center justify-center
                                           rounded-full bg-black/10 hover:bg-black/20 text-gray-700 text-xs leading-none">
                                    ⋮
                                </button>
                                <div x-show="menuOpen" x-cloak
                                     class="absolute top-7 right-1.5 bg-white border border-gray-200 rounded-lg shadow-lg text-xs w-28 py-1 z-20">
                                    <a href="{{ route('posts.edit', $post) }}"
                                       class="block px-3 py-1.5 text-gray-700 hover:bg-gray-50">✏️ Edit</a>
                                    <form method="POST" action="{{ route('posts.destroy', $post) }}"
                                          onsubmit="return confirm('Delete this reply?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-1.5 text-red-600 hover:bg-red-50">🗑️ Delete</button>
                                    </form>
                                </div>
                            @else
                                {{-- Report trigger — same corner as the own-message
                                     menu, always visible so it's easy to find --}}
                                <button type="button" onclick="openReportModal({{ $post->post_id }})"
                                    class="absolute top-1.5 right-1.5 w-5 h-5 flex items-center justify-center
                                           rounded-full bg-black/10 hover:bg-black/20 text-gray-700 text-xs leading-none"
                                    title="Report this post">
                                    🚩
                                </button>
                            @endif

                            @unless($isOwn)
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="text-xs font-bold text-purple-700">
                                        {{ $post->author->username }}
                                    </span>
                                    @if($post->author_id == $topic->creator_id)
                                        <span class="bg-indigo-100 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded-full font-medium">Author</span>
                                    @elseif($post->author->system_role == 'lecturer')
                                        <span class="bg-green-100 text-green-700 text-[10px] px-1.5 py-0.5 rounded-full font-medium">Lecturer</span>
                                    @elseif($post->author->system_role == 'system_admin')
                                        <span class="bg-red-100 text-red-700 text-[10px] px-1.5 py-0.5 rounded-full font-medium">Admin</span>
                                    @endif
                                </div>
                            @endunless

                            @if($post->content)
                                <p class="text-gray-900 text-sm leading-6 whitespace-pre-wrap break-words">{{ $post->content }}</p>
                            @endif

                            {{-- Attachment --}}
                            @if($post->attachment)
                                <div class="mt-2">
                                    @if(Str::startsWith($post->attachment_type, 'image/'))
                                        <a href="{{ asset('storage/'.$post->attachment) }}" target="_blank">
                                            <img src="{{ asset('storage/'.$post->attachment) }}"
                                                 alt="{{ $post->attachment_name }}"
                                                 class="rounded-lg max-h-60 max-w-full border border-black/10">
                                        </a>
                                    @else
                                        <a href="{{ asset('storage/'.$post->attachment) }}"
                                           target="_blank"
                                           class="flex items-center gap-2 bg-black/5 rounded-lg px-3 py-2 text-xs font-medium text-gray-700 hover:bg-black/10">
                                            📎 <span class="truncate">{{ $post->attachment_name }}</span>
                                        </a>
                                    @endif
                                </div>
                            @endif

                            <p class="text-[10px] text-gray-500 text-right mt-1.5 select-none">
                                {{ $post->created_at->format('h:i A') }}
                            </p>
                        </div>
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

    {{-- Report post modal --}}
    <div id="report-post-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Report this post</h3>
                <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
            </div>
            <p class="text-xs text-gray-500 mb-3">
                This sends a report to the system admin for review. It won't remove the post or notify its author.
            </p>
            <form id="report-post-form" method="POST" action="">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea name="reason" rows="3" required placeholder="Why are you reporting this post?"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm mb-3"></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeReportModal()" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                        Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- STICKY REPLY BAR --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-3 z-10">
        <div class="max-w-3xl mx-auto">
            <div id="attachment-preview" class="hidden items-center gap-2 mb-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs text-gray-600 w-fit">
                <span id="attachment-preview-name" class="truncate max-w-[220px]"></span>
                <button type="button" onclick="clearAttachment()" class="text-gray-400 hover:text-gray-700 font-bold">✕</button>
            </div>

            <form method="POST"
                action="/topics/{{ $topic->topic_id ?? 1 }}/posts"
                enctype="multipart/form-data"
                class="flex items-end gap-2">
                @csrf

                <button type="button" onclick="document.getElementById('attachment-input').click()"
                    class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                    title="Attach a file">
                    <svg viewBox="0 0 30 30" class="w-5 h-5" fill="currentColor">
                        <path d="m 22.778176,294.87011 c -1.946455,-1.94646 -5.124615,-1.94645 -7.071068,0 l -0.707107,0.70711 -4.949747,4.94974 -2.8284274,2.82843 c -2.7299272,2.72993 -2.7299293,7.16957 0,9.8995 2.7299292,2.72992 7.1695674,2.72992 9.8994944,0 l 7.778175,-7.77818 -1.414213,-1.41421 -7.778175,7.77817 c -1.970919,1.97092 -5.100151,1.97092 -7.0710679,0 -1.9709166,-1.97091 -1.9709187,-5.10015 0,-7.07107 l 2.8284269,-2.82842 4.949748,-4.94975 0.707106,-0.70711 c 1.187445,-1.18744 3.055199,-1.18744 4.242641,0 1.187442,1.18744 1.187445,3.0552 0,4.24264 l -0.302454,0.30246 -5.3544,5.3544 -2.12132,2.12132 c -0.403971,0.40397 -1.010246,0.40397 -1.414214,0 -0.403968,-0.40397 -0.40397,-1.01025 0,-1.41422 l 7.475721,-7.47572 -1.414214,-1.41421 -7.475721,7.47572 c -1.1629781,1.16298 -1.1629802,3.07966 0,4.24264 1.162981,1.16298 3.079663,1.16298 4.242641,0 l 2.12132,-2.12132 5.354401,-5.3544 0.302454,-0.30245 c 1.946453,-1.94646 1.946455,-5.12462 0,-7.07107 z"
                              transform="translate(0,-289.0625)"/>
                    </svg>
                </button>
                <input type="file" id="attachment-input" name="attachment" class="hidden"
                    accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.ppt,.pptx,.zip"
                    onchange="showAttachment(this)">

                <textarea
                    id="reply-input"
                    name="content"
                    rows="1"
                    placeholder="Add to the conversation..."
                    class="flex-1 bg-gray-50 border border-gray-200 rounded-2xl px-4 py-2.5 text-sm resize-none
                           max-h-32 overflow-y-auto leading-6
                           focus:outline-none focus:border-gray-400 focus:ring-0 transition-colors"
                    oninput="autoGrow(this)"
                    onkeydown="handleReplyKeydown(event)"></textarea>

                <button type="submit"
                    class="bg-green-500 text-white w-10 h-10 rounded-full flex items-center justify-center
                           hover:bg-green-600 transition-colors shrink-0"
                    title="Send">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 -translate-x-px" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <script>
        function showAttachment(input) {
            const preview = document.getElementById('attachment-preview');
            const nameEl = document.getElementById('attachment-preview-name');
            if (input.files && input.files[0]) {
                nameEl.textContent = '📎 ' + input.files[0].name;
                preview.classList.remove('hidden');
                preview.classList.add('flex');
            }
        }
        function clearAttachment() {
            document.getElementById('attachment-input').value = '';
            const preview = document.getElementById('attachment-preview');
            preview.classList.add('hidden');
            preview.classList.remove('flex');
        }

        function openReportModal(postId) {
            document.getElementById('report-post-form').action = `/posts/${postId}/flag`;
            document.getElementById('report-post-modal').classList.remove('hidden');
        }
        function closeReportModal() {
            document.getElementById('report-post-modal').classList.add('hidden');
        }

        // Auto-grow the reply textarea as the user types, so the full
        // message is always visible without scrolling inside the box
        function autoGrow(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 128) + 'px';
        }

        // Enter sends the reply, Shift+Enter inserts a newline — matches
        // the convention most chat apps use
        function handleReplyKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                event.target.closest('form').requestSubmit();
            }
        }
    </script>

    {{-- After posting a reply, jump to it instead of landing back at the
         top of the page (the default behaviour of a full-page redirect) --}}
    @if(session('success') === 'Reply posted!')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const posts = document.querySelectorAll('[id^="post-"]');
                const lastPost = posts[posts.length - 1];
                if (lastPost) {
                    lastPost.scrollIntoView({ behavior: 'instant', block: 'end' });
                } else {
                    window.scrollTo(0, document.body.scrollHeight);
                }
            });
        </script>
    @endif

</body>
</html>
