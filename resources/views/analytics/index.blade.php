<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Analytics</h2></x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid md:grid-cols-4 gap-4">
                <div class="bg-white border rounded p-4"><div class="text-xs text-gray-500">Total Tasks</div><div class="text-2xl font-bold">{{ $total }}</div></div>
                <div class="bg-white border rounded p-4"><div class="text-xs text-gray-500">Done</div><div class="text-2xl font-bold">{{ $done }}</div></div>
                <div class="bg-white border rounded p-4"><div class="text-xs text-gray-500">Overdue</div><div class="text-2xl font-bold">{{ $overdue }}</div></div>
                <div class="bg-white border rounded p-4"><div class="text-xs text-gray-500">Completion Rate</div><div class="text-2xl font-bold">{{ $completionRate }}%</div></div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-white border rounded p-4">
                    <h3 class="font-semibold mb-2">Status Mix</h3>
                    @foreach($byStatus as $status => $count)
                        <div class="flex justify-between text-sm py-1"><span>{{ $status }}</span><span>{{ $count }}</span></div>
                    @endforeach
                </div>

                <div class="bg-white border rounded p-4">
                    <h3 class="font-semibold mb-2">Assignee Workload (Open)</h3>
                    @forelse($workload as $row)
                        <div class="flex justify-between text-sm py-1"><span>{{ $row->name }}</span><span>{{ $row->open_tasks }}</span></div>
                    @empty
                        <p class="text-sm text-gray-500">No assigned open tasks.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
