<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Activity Feed</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <ul class="space-y-3">
            @foreach($activities as $activity)
                <li class="border-b pb-3">
                    <div class="text-sm"><strong>{{ $activity->actor?->name ?? 'System' }}</strong> {{ $activity->action }}</div>
                    <div class="text-xs text-gray-500">{{ $activity->created_at->diffForHumans() }}</div>
                    @if($activity->metadata)
                        <pre class="text-xs bg-gray-100 mt-2 p-2 rounded overflow-x-auto">{{ json_encode($activity->metadata, JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $activities->links() }}</div>
    </div></div>
</x-app-layout>
