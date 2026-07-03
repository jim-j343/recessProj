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

            {{-- 2. NEW: Recess Brief Settings Row (Merged Here) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Inactivity Warn & Blacklist Configurator (Requirement #4) --}}
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Automated Blacklist Engine</h3>
                    <p class="text-xs text-gray-500 mb-4">Configure triggers for silent or non-communicative accounts.</p>

                    <form class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Warning Count Before Penalty</label>
                            <input type="number" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm" value="2" readonly>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Days Before Warning</label>
                                <input type="number" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm" placeholder="e.g. 14">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Blacklist Duration (Days)</label>
                                <input type="number" class="mt-1 block w-full border-gray-300 rounded shadow-sm text-sm" placeholder="e.g. 30">
                            </div>
                        </div>
                        <button type="button" class="w-full bg-gray-900 text-white text-xs font-semibold py-2 rounded uppercase tracking-wide">
                            Apply Compliance Rules
                        </button>
                    </form>
                </div>

                {{-- Granular Subgroup Exclusions Panel (Requirement #3) --}}
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Communication Exclusions</h3>
                    <p class="text-xs text-gray-500 mb-4">Set system baseline visibility properties for subgroup communications.</p>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">Enable Target Exclusions</h4>
                                <p class="text-xs text-gray-500">Allows users to isolate individuals from selective feeds.</p>
                            </div>
                            <input type="checkbox" checked class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        </div>
                    </div>
                </div>
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
