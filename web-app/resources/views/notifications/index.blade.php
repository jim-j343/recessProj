<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2>
            @if($notifications->where('is_read', false)->count() > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700">
                        Mark all read
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-5 py-4 flex items-start gap-3 hover:bg-gray-50 {{ $notification->is_read ? '' : 'bg-indigo-50/40' }}">
                            <x-icon :name="$notification->icon()" class="w-5 h-5 text-gray-400 mt-0.5 shrink-0" />
                            <div class="flex-1">
                                <p class="text-sm {{ $notification->is_read ? 'text-gray-600' : 'text-gray-900 font-medium' }}">
                                    {{ $notification->message() }}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            @if(! $notification->is_read)
                                <span class="mt-1 w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                            @endif
                        </button>
                    </form>
                @empty
                    <div class="px-5 py-10 text-center text-gray-400">
                        You don't have any notifications yet.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
