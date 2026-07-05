@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded mb-4">{{ session('info') }}</div>
    @endif

    <div class="bg-white border rounded-lg p-6 shadow-sm mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold">{{ $group->name }}</h1>
                <p class="text-gray-500 mt-1">{{ $group->description ?? 'No description.' }}</p>
                <p class="text-sm text-gray-400 mt-2">Admin: {{ $group->admin->name }}</p>
            </div>
            <div class="flex flex-col gap-2 items-end">
                @if($membership && $membership->status === 'active')
                    @if($membership->role === 'admin' || $membership->role === 'moderator')
                        <a href="{{ route('groups.members', $group->group_id) }}"
                           class="text-sm bg-gray-100 px-3 py-1 rounded hover:bg-gray-200">Manage Members</a>
                    @endif
                    <form method="POST" action="{{ route('groups.leave', $group->group_id) }}">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-500 hover:underline">Leave Group</button>
                    </form>
                @elseif($membership && $membership->status === 'pending')
                    <span class="text-sm text-yellow-600 font-medium">Request Pending</span>
                @else
                    <form method="POST" action="{{ route('groups.join', $group->group_id) }}">
                        @csrf
                        <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">Join Group</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-3">Active Members ({{ $group->members->count() }})</h2>
    <div class="bg-white border rounded-lg divide-y shadow-sm">
        @forelse($group->members as $member)
            <div class="flex justify-between items-center px-4 py-3">
                <span class="font-medium">{{ $member->name }}</span>
                <span class="text-xs px-2 py-1 rounded-full
                    {{ $member->pivot->role === 'admin' ? 'bg-blue-100 text-blue-700' :
                      ($member->pivot->role === 'moderator' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ ucfirst($member->pivot->role) }}
                </span>
            </div>
        @empty
            <p class="px-4 py-3 text-gray-400">No active members yet.</p>
        @endforelse
    </div>
</div>
@endsection