<x-app-layout>
    <x-slot name="header"><p class="tn-page-title">Notifications</p></x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                <x-secondary-button>Mark all as read</x-secondary-button>
            </form>

            <div class="tn-card divide-y divide-slate-800 p-0">
                @forelse($notifications as $notification)
                    <div class="p-4 {{ $notification->read_at ? '' : 'bg-brand-500/10' }}">
                        <div class="text-sm font-medium text-white">{{ str_replace('.', ' ', $notification->type) }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ json_encode($notification->data) }}</div>
                        <div class="text-xs text-slate-500 mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-slate-500">No notifications yet.</div>
                @endforelse
            </div>

            <div>{{ $notifications->links() }}</div>
        </div>
    </div>
</x-app-layout>
