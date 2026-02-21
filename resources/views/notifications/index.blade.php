<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Notifications</h2></x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                <x-secondary-button>Mark all as read</x-secondary-button>
            </form>

            <div class="bg-white rounded shadow-sm divide-y">
                @forelse($notifications as $notification)
                    <div class="p-4 {{ $notification->read_at ? '' : 'bg-cyan-50' }}">
                        <div class="text-sm font-medium text-gray-800">{{ str_replace('.', ' ', $notification->type) }}</div>
                        <div class="text-xs text-gray-600 mt-1">{{ json_encode($notification->data) }}</div>
                        <div class="text-xs text-gray-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-gray-500">No notifications yet.</div>
                @endforelse
            </div>

            <div>{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>
