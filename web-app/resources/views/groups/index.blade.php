<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Discussion Groups</h2>
            <a href="{{ route('groups.create') }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                + New Group
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            @if($groups->isEmpty())
                <div class="bg-white p-8 rounded-lg shadow-sm text-center text-gray-500">
                    No groups exist yet.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($groups as $group)
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $group->name }}</h3>
                            @if($group->course_name)
                                <p class="text-xs font-semibold text-indigo-600 mt-0.5">{{ $group->course_name }}</p>
                            @endif
                        </div>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full font-medium">
                            {{ $group->memberships_count }} members
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ $group->description ?? 'No description provided.' }}
                    </p>
                    <div class="flex items-center justify-between text-xs text-gray-400 mb-4">
                        <span>{{ $group->topics_count }} topics</span>
                        <span>Admin: {{ $group->admin->username ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('groups.show', $group->group_id) }}"
                           class="flex-1 text-center bg-gray-100 text-gray-700 px-3 py-2 rounded text-sm font-medium hover:bg-gray-200">
                            View
                        </a>
                        @if(!$group->isMember(auth()->id()))
                            <form method="POST" action="{{ route('groups.join', $group->group_id) }}" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-indigo-600 text-white px-3 py-2 rounded text-sm font-medium hover:bg-indigo-700">
                                    Join
                                </button>
                            </form>
                        @else
                            <span class="flex-1 text-center bg-green-100 text-green-700 px-3 py-2 rounded text-sm font-medium">
                                ✓ Joined
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
