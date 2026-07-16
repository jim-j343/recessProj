<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $group->name }}
                </h2>
                @if($group->course_name)
                    <p class="text-sm font-semibold text-indigo-600 mt-0.5">{{ $group->course_name }}</p>
                @endif
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
                    {{-- admin can edit settings or delete --}}
                    @if($group->admin_id === auth()->id())
                        <a href="{{ route('groups.edit', $group->group_id) }}"
                           class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50">
                            Edit Group
                        </a>
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

            {{-- WhatsApp-style "X was removed" announcements, visible to
                 everyone still in the group --}}
            @if($removalAnnouncements->isNotEmpty())
                <div class="flex flex-col items-center gap-2 mb-6">
                    @foreach($removalAnnouncements as $entry)
                        <div class="bg-gray-200 text-gray-600 text-xs px-3 py-1.5 rounded-full">
                            👋 {{ $entry->meta['removed_username'] ?? 'A member' }} was removed from the group by {{ $entry->user->username ?? 'an admin' }}
                            <span class="text-gray-400">· {{ $entry->logged_at->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
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
                        <div class="flex items-center justify-between gap-2 py-2 border-b border-gray-100 last:border-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                    <span class="text-indigo-700 text-xs font-semibold">
                                        {{ strtoupper(substr($membership->user->username ?? 'U', 0, 1)) }}
                                    </span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ $membership->user->username ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ ucfirst($membership->role) }}</p>
                                </div>
                            </div>

                            @if($group->admin_id === auth()->id() && $membership->user_id !== $group->admin_id)
                                <button type="button"
                                    onclick="openRemoveModal({{ $membership->user_id }}, '{{ addslashes($membership->user->username ?? 'this member') }}')"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium shrink-0">
                                    Remove
                                </button>
                            @endif
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

    {{-- Remove member modal — reason is optional but gets attached to the
         report the system admin reviews --}}
    <div id="remove-member-modal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-gray-900">Remove <span id="remove-member-name"></span>?</h3>
                <button onclick="closeRemoveModal()" class="text-gray-400 hover:text-gray-700 text-lg leading-none">✕</button>
            </div>
            <p class="text-xs text-gray-500 mb-3">
                This removes them from the group and files a report for the system admin to review. It does not blacklist them.
            </p>
            <form id="remove-member-form" method="POST" action="">
                @csrf
                <textarea name="reason" rows="2" placeholder="Optional reason..."
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm mb-3"></textarea>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeRemoveModal()" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                        Remove Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRemoveModal(userId, username) {
            document.getElementById('remove-member-name').textContent = username;
            document.getElementById('remove-member-form').action = `/groups/{{ $group->group_id }}/members/${userId}/remove`;
            document.getElementById('remove-member-modal').classList.remove('hidden');
        }
        function closeRemoveModal() {
            document.getElementById('remove-member-modal').classList.add('hidden');
        }
    </script>
</x-app-layout>