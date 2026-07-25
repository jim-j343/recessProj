{{-- The topic list, extracted so it can be re-rendered on its own for
     live search without reloading the whole page --}}

@if($topics->isEmpty())
    <div class="bg-white p-8 rounded-lg shadow-sm text-center text-gray-500">
        @if($search)
            No topics match "{{ $search }}".
        @else
            No topics yet. Be the first to start a discussion!
        @endif
    </div>
@endif

@foreach($topics as $topic)
<div class="bg-white overflow-hidden shadow-sm rounded-lg p-4 sm:p-6 flex justify-between items-start gap-3">
    <div class="min-w-0">
        <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">
            {{ $topic->category ?? 'General' }}
        </span>
        <h3 class="mt-2 text-lg font-bold text-gray-800">
            <a href="/topics/{{ $topic->topic_id }}" class="hover:text-indigo-600 break-words">
                {{ $topic->title }}
            </a>
        </h3>
        <p class="text-sm text-gray-500 mt-1">
            Posted by {{ $topic->creator->username ?? 'Unknown' }}
            · {{ $topic->posts_count ?? 0 }} replies
        </p>
    </div>
    <div class="text-right text-sm shrink-0">
        @if($topic->posts_count === 0)
            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap">
                Unanswered
            </span>
        @else
            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap">
                Answered
            </span>
        @endif
    </div>
</div>
@endforeach

@if($topics->hasPages())
<div class="bg-white p-4 rounded-lg shadow-sm">
    {{ $topics->links() }}
</div>
@endif
