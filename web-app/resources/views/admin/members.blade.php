<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-3xl mx-auto px-4">

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Compliance Monitoring</h1>
                <p class="text-sm text-gray-500 mt-1">Manage flagged accounts and system restrictions.</p>
            </div>

            {{-- Filter Tabs --}}
            <div class="flex gap-2 mb-6">
                @foreach(['All Flags', 'Blacklisted', 'Warning', 'Review'] as $i => $tab)
                <button class="px-4 py-2 rounded-full text-sm font-semibold transition-colors
                    {{ $i === 0
                        ? 'bg-gray-900 text-white'
                        : 'border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    {{ $tab }}
                </button>
                @endforeach
            </div>

            {{-- Member Cards --}}
            <div class="space-y-4">

                {{-- Blacklisted Member (Expanded) --}}
                <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between p-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-300 rounded-lg overflow-hidden flex items-center justify-center">
                                <span class="text-gray-600 font-bold">JV</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">Julian Vance</h3>
                                <p class="text-sm text-gray-400">ID: #UX-9021</p>
                            </div>
                            <span class="flex items-center gap-1.5 bg-red-50 text-red-600 text-xs font-semibold px-3 py-1 rounded-full border border-red-200">
                                🚫 Blacklisted
                            </span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-700">∧</button>
                    </div>

                    {{-- Expanded Detail --}}
                    <div class="px-5 pb-5 border-t border-gray-100">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 my-4 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm">⏱</span>
                                </div>
                                <div>
                                    <p class="text-xs text-red-600 font-bold uppercase tracking-wide">Access Restriction</p>
                                    <p class="text-xl font-bold text-red-600">6 Days Remaining</p>
                                </div>
                            </div>
                            <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700">
                                Extend
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Reason for Flag</p>
                                <p class="text-sm font-semibold text-gray-800">Academic Integrity Violation</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Reported By</p>
                                <p class="text-sm font-semibold text-gray-800">AI Auditor (System)</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Detection Confidence</p>
                                <p class="text-sm font-semibold text-gray-800">98.4%</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 mb-1">Last Action</p>
                                <p class="text-sm font-semibold text-gray-800">Dec 12, 2023</p>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <button class="flex-1 flex items-center justify-center gap-2 bg-gray-900 text-white py-3 rounded-lg text-sm font-semibold hover:bg-gray-700">
                                🛡 Full Review
                            </button>
                            <button class="flex-1 flex items-center justify-center gap-2 border border-gray-200 text-gray-700 py-3 rounded-lg text-sm font-semibold hover:bg-gray-50">
                                ✉ Notify
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Warned Member --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-300 rounded-lg flex items-center justify-center">
                                <span class="text-gray-600 font-bold">ER</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">Elena Rodriguez</h3>
                                <p class="text-sm text-gray-400">ID: #UX-7742</p>
                            </div>
                            <span class="flex items-center gap-1.5 bg-yellow-50 text-yellow-700 text-xs font-semibold px-3 py-1 rounded-full border border-yellow-200">
                                ⚠ Warning
                            </span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-700">∨</button>
                    </div>
                </div>

                {{-- Active Member --}}
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-300 rounded-lg flex items-center justify-center">
                                <span class="text-gray-600 font-bold">KA</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">Kaleb Aris</h3>
                                <p class="text-sm text-gray-400">ID: #UX-5521</p>
                            </div>
                            <span class="flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1 rounded-full border border-green-200">
                                ✓ Active
                            </span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-700">∨</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>