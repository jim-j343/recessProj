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

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Search bar. Filters as you type via JS; the button still
                 works normally if JavaScript is unavailable. --}}
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <form id="topic-search-form" method="GET" action="{{ route('forum.index') }}" class="flex gap-2">
                    <input type="text" id="topic-search" name="search" value="{{ $search }}"
                           placeholder="Search topics..." autocomplete="off"
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                        class="shrink-0 bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                        Search
                    </button>
                    <button type="button" id="topic-search-clear"
                        class="shrink-0 px-3 text-sm text-gray-500 hover:text-gray-700 {{ $search ? '' : 'hidden' }}">
                        Clear
                    </button>
                </form>
            </div>

            {{-- Swapped out wholesale when the search text changes --}}
            <div id="topic-list" class="space-y-4 transition-opacity duration-150">
                @include('forum._topic-list')
            </div>

        </div>
    </div>

    <script>
        // ---- Live topic search ----
        // Waits until typing pauses, then asks the server for the filtered
        // list and swaps it in. Server-side so it searches every topic, not
        // just the ones already on screen.
        (function () {
            const form  = document.getElementById('topic-search-form');
            const input = document.getElementById('topic-search');
            const clear = document.getElementById('topic-search-clear');
            const list  = document.getElementById('topic-list');

            let timer = null;
            let inFlight = null;

            async function runSearch() {
                const q = input.value.trim();

                // Keep the address bar in step, so refreshing or sharing
                // the URL reproduces what's on screen
                const url = new URL(window.location.href);
                url.searchParams.delete('page');   // a new search starts at page 1
                if (q) {
                    url.searchParams.set('search', q);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.replaceState({}, '', url);

                clear.classList.toggle('hidden', q === '');

                // Cancel any earlier request still running, so a slow
                // response for "dat" can't land after "database"
                if (inFlight) inFlight.abort();
                inFlight = new AbortController();

                list.style.opacity = '0.5';
                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: inFlight.signal,
                    });
                    if (res.ok) {
                        list.innerHTML = await res.text();
                    }
                } catch (e) {
                    // aborted or offline — leave the current list in place
                } finally {
                    list.style.opacity = '1';
                }
            }

            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(runSearch, 300);
            });

            // Enter shouldn't reload the page when live search is available
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(timer);
                runSearch();
            });

            clear.addEventListener('click', () => {
                input.value = '';
                clearTimeout(timer);
                runSearch();
                input.focus();
            });
        })();
    </script>
</x-app-layout>
