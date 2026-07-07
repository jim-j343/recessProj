<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $group->name }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $group->description }}</p>
            </div>
            <div class="flex gap-2">
                @if($isMember)
                    <a href="{{ route('forum.index') }}"
                       class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                        Go to Forum
                    </a>
                    {{-- non-admin can leave --}}
                    @if($group->admin_id !== auth()->id())
                        <form method="POST" action="{{ route('groups.leave', $group->group_id) }}">
                            @csrf
                            <button type="submit"
                                class="bg-red-100 text-red-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-red-200"
                                onclick="return confirm('Are you sure you want to leave this group?')">
                                Leave group
                            </button>
                        </form>
                    @endif
                    {{-- admin can delete --}}
                    @if($group->admin_id === auth()->id())
                        <form method="POST" action="{{ route('groups.destroy', $group->group_id) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700"
                                onclick="return confirm('Delete this group? This cannot be undone.')">
                                Delete Group
                            </button>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route('groups.join', $group->group_id) }}">
                        @csrf
                        <button type="submit"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                            Join this group
                        </button>
                    </form>
                @endif
            </div>
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

            {{-- Stats --}}
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">{{ $group->memberships->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Members</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $group->topics->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Topics</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-gray-700">{{ $group->inactivity_warning_days }}</p>
                    <p class="text-xs text-gray-500 mt-1">Days before warning</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $group->blacklist_duration_days }}</p>
                    <p class="text-xs text-gray-500 mt-1">Blacklist duration (days)</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">

                {{-- Recent Topics --}}
                <div class="col-span-2 bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Recent Topics</h3>
                    @if($group->topics->isEmpty())
                        <p class="text-sm text-gray-400">No topics yet in this group.</p>
                    @endif
                    @foreach($group->topics->take(5) as $topic)
                        <div class="flex justify-between items-start py-3 border-b border-gray-100 last:border-0">
                            <div>
                                <a href="{{ route('topics.show', $topic->topic_id) }}"
                                   class="text-sm font-medium text-gray-800 hover:text-indigo-600">
                                    {{ $topic->title }}
                                </a>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    by {{ $topic->creator->username ?? 'Unknown' }}
                                    · {{ $topic->created_at->diffForHumans() }}
                                </p>
                            </div>
                            @if($topic->category)
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full ml-3 shrink-0">
                                    {{ $topic->category }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Members --}}
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Members</h3>
                    @foreach($group->memberships->take(8) as $membership)
                        <div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                <span class="text-indigo-700 text-xs font-semibold">
                                    {{ strtoupper(substr($membership->user->username ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $membership->user->username ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ ucfirst($membership->role) }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if($group->memberships->count() > 8)
                        <p class="text-xs text-gray-400 mt-3 text-center">
                            +{{ $group->memberships->count() - 8 }} more members
                        </p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
