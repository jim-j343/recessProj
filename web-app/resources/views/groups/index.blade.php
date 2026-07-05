@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto py-8 px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Discussion Groups</h1>
        <a href="{{ route('groups.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + New Group
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($groups as $group)
            <div class="border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $group->name }}</h2>
                        <p class="text-gray-500 text-sm mt-1">{{ $group->description ?? 'No description.' }}</p>
                        <p class="text-xs text-gray-400 mt-2">{{ $group->memberships_count }} member(s)</p>
                    </div>
                    <div class="ml-4 flex flex-col gap-2">
                        <a href="{{ route('groups.show', $group->group_id) }}"
                           class="text-blue-600 text-sm hover:underline">View</a>
                        @if($myGroups->contains($group->group_id))
                            <span class="text-xs text-green-600 font-medium">Joined</span>
                        @else
                            <form method="POST" action="{{ route('groups.join', $group->group_id) }}">
                                @csrf
                                <button class="text-sm bg-gray-100 px-3 py-1 rounded hover:bg-gray-200">Join</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-gray-500 col-span-2">No groups yet. Be the first to create one!</p>
        @endforelse
    </div>
</div>
@endsection