<x-app-layout>
    <x-slot name="header"><h1 class="tn-page-title">Activity Feed</h1></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 tn-card">
        <ul class="divide-y divide-slate-800">
            @foreach($activities as $activity)
                <li class="py-3">
                    <div class="text-sm text-slate-200"><strong class="text-white">{{ $activity->actor?->name ?? 'System' }}</strong> {{ $activity->action }}</div>
                    <div class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</div>
                    @if($activity->metadata)
                        <pre class="text-xs bg-slate-950 border border-slate-800 mt-2 p-2 rounded overflow-x-auto text-slate-400">{{ json_encode($activity->metadata, JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $activities->links() }}</div>
    </div></div>
</x-app-layout>
