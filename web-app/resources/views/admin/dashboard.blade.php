<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Admin Analytics Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 1. Existing Stats cards row (Kept intact) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <p class="text-3xl font-bold text-indigo-600">{{ $totalMembers }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total Members</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <p class="text-3xl font-bold text-green-600">{{ $activeToday }}</p>
                    <p class="text-sm text-gray-500 mt-1">Active Today</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <p class="text-3xl font-bold text-yellow-600">{{ $warned }}</p>
                    <p class="text-sm text-gray-500 mt-1">Members Warned</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <p class="text-3xl font-bold text-red-600">{{ $blacklisted }}</p>
                    <p class="text-sm text-gray-500 mt-1">Blacklisted</p>
                </div>
            </div>

            {{-- 2. Real per-group inactivity/blacklist settings —
                 replaces the old "Automated Blacklist Engine" panel, which
                 was a single global-looking form not wired to any group,
                 and a fabricated "Communication Exclusions" toggle with no
                 backing feature anywhere in the app. Per-group control of
                 these belongs to each group's own admin (see Groups). --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900">Group Inactivity & Blacklist Settings</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Each group sets its own thresholds. This is a read-only overview — group admins manage their own settings.
                    </p>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Members</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Before Warning</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blacklist Duration</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($groupSettings as $group)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $group->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $group->course_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $group->memberships_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $group->inactivity_warning_days }} days</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $group->blacklist_duration_days }} days</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No groups exist yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 3. Existing Member table (Kept intact) --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Active</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($members as $member)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $member->username }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $member->last_active_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $member->posts_count?? 0 }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $member->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $member->status === 'warned_once' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ $member->status === 'blacklisted' ? 'bg-red-100 text-red-700' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $member->status)) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>

