<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8" x-data="{
        search: '',
        activeTab: 'All Flags',
        expanded: 'JV',
        members: [
            { initials: 'JV', name: 'Julian Vance', id: '#UX-9021', status: 'Blacklisted', reason: 'Academic Integrity Violation', reportedBy: 'AI Auditor (System)', confidence: '98.4%', lastAction: 'Dec 12, 2023', daysLeft: 6 },
            { initials: 'ER', name: 'Elena Rodriguez', id: '#UX-7742', status: 'Warning', reason: 'Disruptive Behaviour', reportedBy: 'Dr. Sarah Jenkins', confidence: '76.1%', lastAction: 'Dec 10, 2023', daysLeft: null },
            { initials: 'KA', name: 'Kaleb Aris', id: '#UX-5521', status: 'Active', reason: null, reportedBy: null, confidence: null, lastAction: null, daysLeft: null },
        ],
        get filtered() {
            return this.members.filter(m => {
                const matchSearch = this.search === '' ||
                    m.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    m.id.toLowerCase().includes(this.search.toLowerCase());
                const matchTab = this.activeTab === 'All Flags' || m.status === this.activeTab;
                return matchSearch && matchTab;
            });
        },
        statusColor(status) {
            if (status === 'Blacklisted') return 'bg-red-50 text-red-600 border-red-200';
            if (status === 'Warning') return 'bg-yellow-50 text-yellow-700 border-yellow-200';
            if (status === 'Active') return 'bg-green-50 text-green-700 border-green-200';
            return 'bg-gray-100 text-gray-600 border-gray-200';
        },
        statusIcon(status) {
            if (status === 'Blacklisted') return '🚫';
            if (status === 'Warning') return '⚠';
            if (status === 'Active') return '✓';
            return '';
        }
    }">
        <div class="max-w-3xl mx-auto px-4">

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Member Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage flagged accounts and issue warnings or restrictions.</p>
            </div>

            {{-- Search Bar --}}
            <div class="relative mb-4">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Search by name or member ID..."
                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white
                           focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                           placeholder-gray-400 transition-all"
                />
                <span x-show="search !== ''" @click="search = ''"
                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 cursor-pointer hover:text-gray-700">✕</span>
            </div>

            {{-- Filter Tabs --}}
            <div class="flex gap-2 mb-6">
                @foreach(['All Flags', 'Blacklisted', 'Warning', 'Review', 'Active'] as $tab)
                <button
                    @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}'
                        ? 'bg-gray-900 text-white'
                        : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                    class="px-4 py-2 rounded-full text-sm font-semibold transition-colors">
                    {{ $tab }}
                </button>
                @endforeach
            </div>

            {{-- Member Cards --}}
            <div class="space-y-4">
                <template x-for="m in filtered" :key="m.id">
                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

                        {{-- Card Header --}}
                        <div class="flex items-center justify-between p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <span class="text-gray-700 font-bold text-sm" x-text="m.initials"></span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900" x-text="m.name"></h3>
                                    <p class="text-sm text-gray-400" x-text="'ID: ' + m.id"></p>
                                </div>
                                <span :class="statusColor(m.status)"
                                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1 rounded-full border">
                                    <span x-text="statusIcon(m.status)"></span>
                                    <span x-text="m.status"></span>
                                </span>
                            </div>
                            <button @click="expanded = expanded === m.initials ? null : m.initials"
                                class="text-gray-400 hover:text-gray-700 text-sm font-semibold px-2">
                                <span x-text="expanded === m.initials ? '∧' : '∨'"></span>
                            </button>
                        </div>

                        {{-- Expanded Detail --}}
                        <div x-show="expanded === m.initials" x-cloak class="px-5 pb-5 border-t border-gray-100">

                            {{-- Blacklist restriction banner --}}
                            <template x-if="m.status === 'Blacklisted'">
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4 my-4 flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm">⏱</span>
                                        </div>
                                        <div>
                                            <p class="text-xs text-red-600 font-bold uppercase tracking-wide">Access Restriction</p>
                                            <p class="text-xl font-bold text-red-600" x-text="m.daysLeft + ' Days Remaining'"></p>
                                        </div>
                                    </div>
                                    <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700">
                                        Extend
                                    </button>
                                </div>
                            </template>

                            {{-- Warning banner --}}
                            <template x-if="m.status === 'Warning'">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 my-4 flex items-center gap-3">
                                    <span class="text-yellow-600 text-lg">⚠</span>
                                    <p class="text-sm text-yellow-700 font-semibold">This member has an active warning on record.</p>
                                </div>
                            </template>

                            {{-- Detail grid --}}
                            <template x-if="m.reason">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-xs text-gray-400 mb-1">Reason for Flag</p>
                                        <p class="text-sm font-semibold text-gray-800" x-text="m.reason"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-1">Reported By</p>
                                        <p class="text-sm font-semibold text-gray-800" x-text="m.reportedBy"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-1">Detection Confidence</p>
                                        <p class="text-sm font-semibold text-gray-800" x-text="m.confidence"></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-1">Last Action</p>
                                        <p class="text-sm font-semibold text-gray-800" x-text="m.lastAction"></p>
                                    </div>
                                </div>
                            </template>

                            {{-- SDD Action Buttons: Warn + Blacklist + extras --}}
                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                {{-- Warn Member (SDD requirement) --}}
                                <button class="flex-1 flex items-center justify-center gap-2 bg-yellow-50 border border-yellow-200
                                               text-yellow-700 py-2.5 rounded-lg text-sm font-semibold hover:bg-yellow-100 transition-colors">
                                    ⚠ Warn Member
                                </button>
                                {{-- Blacklist Member (SDD requirement) --}}
                                <button class="flex-1 flex items-center justify-center gap-2 bg-red-50 border border-red-200
                                               text-red-600 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors">
                                    🚫 Blacklist
                                </button>
                                {{-- Full Review --}}
                                <button class="flex-1 flex items-center justify-center gap-2 bg-gray-900 text-white
                                               py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-700 transition-colors">
                                    🛡 Full Review
                                </button>
                                {{-- Notify --}}
                                <button class="flex-1 flex items-center justify-center gap-2 border border-gray-200
                                               text-gray-700 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-colors">
                                    ✉ Notify
                                </button>
                            </div>
                        </div>

                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="filtered.length === 0" class="text-center py-12 text-gray-400">
                    <p class="text-2xl mb-2">🔍</p>
                    <p class="text-sm font-medium">No members match "<span x-text="search"></span>"</p>
                    <p class="text-xs mt-1">Try a different name or ID.</p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>