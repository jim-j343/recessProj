<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Moderation</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            {{-- Section tabs — Reports and Removals share one Moderation area --}}
            <div class="flex gap-2 mb-6 border-b border-gray-200">
                <a href="{{ route('admin.reports') }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px {{ request()->routeIs('admin.reports') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Post Reports
                </a>
                <a href="{{ route('admin.removals') }}"
                   class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px {{ request()->routeIs('admin.removals') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Member Removals
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="flex gap-2 mb-6">
                <a href="{{ route('admin.removals', ['filter' => 'unreviewed']) }}"
                   class="px-4 py-2 rounded-md text-sm font-medium {{ $filter === 'unreviewed' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                    Needs Review
                </a>
                <a href="{{ route('admin.removals', ['filter' => 'reviewed']) }}"
                   class="px-4 py-2 rounded-md text-sm font-medium {{ $filter === 'reviewed' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                    Reviewed
                </a>
                <a href="{{ route('admin.removals', ['filter' => 'all']) }}"
                   class="px-4 py-2 rounded-md text-sm font-medium {{ $filter === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                    All
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200"></table>
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Removed Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Removed By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($removals as $removal)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $removal->removedUser->username ?? 'Unknown' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $removal->group->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $removal->removedBy->username ?? 'Unknown' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $removal->reason ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $removal->created_at->diffForHumans() }}</td>
                            <td class="px-6 py-4">
                                @if($removal->reviewed)
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">
                                        Reviewed{{ $removal->reviewedBy ? ' by '.$removal->reviewedBy->username : '' }}
                                    </span>
                                @else
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-medium">
                                        Needs review
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(!$removal->reviewed)
                                <form method="POST" action="{{ route('admin.removals.review', $removal->removal_id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">
                                        Mark Reviewed
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400">No removal reports.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
              </div>
            </div>

            <div class="mt-6">
                {{ $removals->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
