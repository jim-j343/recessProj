<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Admin Analytics Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Stats cards row --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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

            {{-- Member table --}}
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
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $member->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $member->last_activity_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $member->posts_count }}</td>
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