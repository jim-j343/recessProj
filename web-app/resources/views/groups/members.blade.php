@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Members — {{ $group->name }}</h1>
        <a href="{{ route('groups.show', $group->group_id) }}" class="text-sm text-blue-600 hover:underline">← Back to Group</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- Pending Requests --}}
    <h2 class="text-lg font-semibold mb-3">Pending Requests ({{ $pending->count() }})</h2>
    <div class="bg-white border rounded-lg divide-y shadow-sm mb-8">
        @forelse($pending as $member)
            <div class="flex justify-between items-center px-4 py-3">
                <span>{{ $member->name }}</span>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('groups.approve', [$group->group_id, $member->user_id]) }}">
                        @csrf @method('PATCH')
                        <button class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded hover:bg-green-200">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('groups.remove', [$group->group_id, $member->user_id]) }}">
                        @csrf @method('DELETE')
                        <button class="text-sm bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200">Reject</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="px-4 py-3 text-gray-400">No pending requests.</p>
        @endforelse
    </div>

    {{-- Active Members --}}
    <h2 class="text-lg font-semibold mb-3">Active Members ({{ $active->count() }})</h2>
    <div class="bg-white border rounded-lg divide-y shadow-sm">
        @forelse($active as $member)
            <div class="flex justify-between items-center px-4 py-3">
                <div>
                    <span class="font-medium">{{ $member->name }}</span>
                    <span class="ml-2 text-xs text-gray-400">{{ ucfirst($member->pivot->role) }}</span>
                </div>
                @if($member->pivot->role === 'member')
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('groups.promote', [$group->group_id, $member->user_id]) }}">
                            @csrf @method('PATCH')
                            <button class="text-sm bg-purple-100 text-purple-700 px-3 py-1 rounded hover:bg-purple-200">Promote</button>
                        </form>
                        <form method="POST" action="{{ route('groups.remove', [$group->group_id, $member->user_id]) }}">
                            @csrf @method('DELETE')
                            <button class="text-sm bg-red-100 text-red-700 px-3 py-1 rounded hover:bg-red-200">Remove</button>
                        </form>
                    </div>
                @else
                    <span class="text-xs text-gray-400 italic">No actions</span>
                @endif
            </div>
        @empty
            <p class="px-4 py-3 text-gray-400">No active members yet.</p>
        @endforelse
    </div>
</div>
@endsection