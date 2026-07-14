<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Member Management</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <p class="text-sm text-gray-500 mb-6">Manage flagged accounts and issue warnings or restrictions.</p>

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            {{-- Search --}}
            <form method="GET" action="{{ route('admin.members') }}" class="mb-4">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search by username or email..."
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </form>

            {{-- Filter tabs --}}
            <div class="flex gap-2 mb-6">
                @foreach(['all' => 'All', 'blacklisted' => 'Blacklisted', 'warning' => 'Warning', 'active' => 'Active'] as $key => $label)
                    <a href="{{ route('admin.members', ['filter' => $key, 'search' => $search]) }}"
                       class="px-4 py-2 rounded-full text-sm font-medium
                              {{ $filter === $key ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Member list --}}
            <div class="space-y-4">
                @forelse($members as $member)
                    @php
                        $activeBlacklist = $member->blacklists
                            ->firstWhere(fn ($b) => $b->expires_at->isFuture() && !$b->lifted_by);
                        $unheededWarnings = $member->warnings->where('is_heeded', false);
                    @endphp

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="{ open: false }">
                        <div class="flex items-center justify-between cursor-pointer" @click="open = !open">
                            <div class="flex items-center gap-4">
                                <div class="w-11 h-11 rounded-lg bg-gray-100 flex items-center justify-center font-semibold text-gray-700">
                                    {{ strtoupper(substr($member->username, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-gray-900">{{ $member->username }}</p>
                                        @if($member->status === 'blacklisted')
                                            <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-medium">⛔ Blacklisted</span>
                                        @elseif($unheededWarnings->count())
                                            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">⚠ Warning #{{ $unheededWarnings->max('warning_number') }}</span>
                                        @else
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Active</span>
                                        @endif
                                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ ucfirst(str_replace('_', ' ', $member->system_role)) }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $member->email }} · {{ $member->posts_count }} posts ·
                                        last active {{ $member->last_active_at?->diffForHumans() ?? 'never' }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-gray-400" x-text="open ? '⌃' : '⌄'"></span>
                        </div>

                        <div x-show="open" x-cloak class="mt-5 pt-5 border-t border-gray-100">

                            @if($activeBlacklist)
                                <div class="flex items-center justify-between bg-red-50 border border-red-100 rounded-lg p-4 mb-4">
                                    <div>
                                        <p class="text-xs font-bold text-red-700 uppercase tracking-wide">Access Restriction</p>
                                        <p class="text-lg font-bold text-red-800">
                                            {{ max(0, (int) now()->diffInDays($activeBlacklist->expires_at, false)) }} days remaining
                                        </p>
                                        <p class="text-xs text-red-600 mt-1">Reason: {{ $activeBlacklist->reason }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.liftBlacklist', $member->user_id) }}">
                                        @csrf
                                        <button class="bg-white border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100">
                                            Lift Blacklist
                                        </button>
                                    </form>
                                </div>
                                @elseif($member->status === 'blacklisted')
                                    <div class="flex items-center justify-between bg-red-50 border border-red-100 rounded-lg p-4 mb-4">
                                        <div>
                                            <p class="text-xs font-bold text-red-700 uppercase tracking-wide">Blacklisted</p>
                                            <p class="text-xs text-red-600 mt-1">No active restriction record found for this member.</p>
                                        </div>
                                        <form method="POST" action="{{ route('admin.liftBlacklist', $member->user_id) }}">
                                            @csrf
                                            <button class="bg-white border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-100">
                                                Lift Blacklist
                                            </button>
                                        </form>
                                    </div>
                            @endif

                            @if($member->warnings->count())
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Warning History</p>
                                    @foreach($member->warnings->take(3) as $warning)
                                        <p class="text-xs text-gray-600 py-1">
                                            Warning #{{ $warning->warning_number }} — issued {{ $warning->issued_at->format('d M Y') }},
                                            deadline {{ $warning->deadline->format('d M Y') }}
                                            {{ $warning->is_heeded ? '· heeded ✓' : '· unheeded' }}
                                        </p>
                                    @endforeach
                                </div>
                            @endif

                            @if($member->status !== 'blacklisted' && $member->system_role !== 'system_admin')
                                <form method="POST" action="{{ route('admin.blacklist', $member->user_id) }}"
                                      class="flex flex-wrap items-end gap-3 bg-gray-50 rounded-lg p-4">
                                    @csrf
                                    <div class="flex-1 min-w-48">
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Reason</label>
                                        <input type="text" name="reason" required placeholder="e.g. Repeated irrelevant posting"
                                            class="w-full border-gray-300 rounded-lg text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 mb-1">Days</label>
                                        <input type="number" name="days" value="30" min="1" max="365" required
                                            class="w-24 border-gray-300 rounded-lg text-sm" />
                                    </div>
                                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700"
                                        onclick="return confirm('Blacklist {{ $member->username }}?')">
                                        Blacklist
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-8 rounded-lg text-center text-gray-400">No members match this filter.</div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $members->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
