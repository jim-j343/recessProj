<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800">👥 Member Management</h2>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- STATS ROW --}}
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600">24</p>
                    <p class="text-xs text-gray-500 mt-1">Total Members</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">18</p>
                    <p class="text-xs text-gray-500 mt-1">Active</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-500">4</p>
                    <p class="text-xs text-gray-500 mt-1">Warned</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">2</p>
                    <p class="text-xs text-gray-500 mt-1">Blacklisted</p>
                </div>
            </div>

            {{-- SEARCH & FILTER --}}
            <div class="bg-white rounded-lg shadow p-4 flex gap-4 items-end">
                <div class="flex-1">
                    <x-input-label :value="__('Search Member')" />
                    <x-text-input type="text" class="block mt-1 w-full"
                        placeholder="Search by name or email..." />
                </div>
                <div>
                    <x-input-label :value="__('Status')" />
                    <select class="block mt-1 border-gray-300 rounded-md shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="warned">Warned</option>
                        <option value="blacklisted">Blacklisted</option>
                    </select>
                </div>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg
                               text-sm hover:bg-indigo-700">
                    Search
                </button>
            </div>

            {{-- MEMBERS TABLE --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-left">Member</th>
                            <th class="px-6 py-3 text-left">Role</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-left">Last Active</th>
                            <th class="px-6 py-3 text-left">Posts</th>
                            <th class="px-6 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        {{-- Later: @foreach($members as $member) --}}

                        {{-- Active member --}}
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-indigo-500 text-white rounded-full
                                                flex items-center justify-center text-sm font-bold">
                                        J
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">Jane Doe</p>
                                        <p class="text-xs text-gray-400">jane@example.com</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">Student</td>
                            <td class="px-6 py-4">
                                <span class="bg-green-100 text-green-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">2 hours ago</td>
                            <td class="px-6 py-4 text-gray-500">24</td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <form method="POST" action="/admin/members/1/warn">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs text-yellow-600 border border-yellow-300
                                                   px-2 py-1 rounded hover:bg-yellow-50">
                                            ⚠️ Warn
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/members/1/blacklist">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs text-red-600 border border-red-300
                                                   px-2 py-1 rounded hover:bg-red-50">
                                            🚫 Blacklist
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/members/1/remove">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Remove this member?')"
                                            class="text-xs text-gray-500 border border-gray-300
                                                   px-2 py-1 rounded hover:bg-gray-50">
                                            ✕ Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Warned member --}}
                        <tr class="hover:bg-yellow-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-purple-500 text-white rounded-full
                                                flex items-center justify-center text-sm font-bold">
                                        J
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">John Smith</p>
                                        <p class="text-xs text-gray-400">john@example.com</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">Student</td>
                            <td class="px-6 py-4">
                                <span class="bg-yellow-100 text-yellow-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    ⚠️ Warned
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">1 day ago</td>
                            <td class="px-6 py-4 text-gray-500">8</td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <form method="POST" action="/admin/members/2/unwarn">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs text-green-600 border border-green-300
                                                   px-2 py-1 rounded hover:bg-green-50">
                                            ✓ Remove Warn
                                        </button>
                                    </form>
                                    <form method="POST" action="/admin/members/2/blacklist">
                                        @csrf
                                        <button type="submit"
                                            class="text-xs text-red-600 border border-red-300
                                                   px-2 py-1 rounded hover:bg-red-50">
                                            🚫 Blacklist
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Blacklisted member --}}
                        <tr class="hover:bg-red-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-400 text-white rounded-full
                                                flex items-center justify-center text-sm font-bold">
                                        A
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-400 line-through">
                                            Alice Nakato
                                        </p>
                                        <p class="text-xs text-gray-400">alice@example.com</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-400">Student</td>
                            <td class="px-6 py-4">
                                <span class="bg-red-100 text-red-700 px-2 py-1
                                             rounded-full text-xs font-medium">
                                    🚫 Blacklisted
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-400">5 days ago</td>
                            <td class="px-6 py-4 text-gray-400">3</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="/admin/members/3/unblacklist">
                                    @csrf
                                    <button type="submit"
                                        class="text-xs text-green-600 border border-green-300
                                               px-2 py-1 rounded hover:bg-green-50">
                                        ✓ Unblacklist
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- @endforeach --}}

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>