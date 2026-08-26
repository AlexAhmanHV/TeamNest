<x-app-layout>
    <x-slot name="header"><p class="tn-page-title">Analytics</p></x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid md:grid-cols-4 gap-4">
                <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Total Tasks</div><div class="text-3xl font-extrabold text-white mt-1">{{ $total }}</div></div>
                <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Done</div><div class="text-3xl font-extrabold text-white mt-1">{{ $done }}</div></div>
                <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Overdue</div><div class="text-3xl font-extrabold text-white mt-1">{{ $overdue }}</div></div>
                <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Completion Rate</div><div class="text-3xl font-extrabold text-brand-400 mt-1">{{ $completionRate }}%</div></div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="tn-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Status Mix</h3>
                    @foreach($byStatus as $status => $count)
                        <div class="flex justify-between text-sm py-1 text-slate-300"><span>{{ $status }}</span><span class="text-white font-medium">{{ $count }}</span></div>
                    @endforeach
                </div>

                <div class="tn-card">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Assignee Workload (Open)</h3>
                    @forelse($workload as $row)
                        <div class="flex justify-between text-sm py-1 text-slate-300"><span>{{ $row->name }}</span><span class="text-white font-medium">{{ $row->open_tasks }}</span></div>
                    @empty
                        <p class="text-sm text-slate-500">No assigned open tasks.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
