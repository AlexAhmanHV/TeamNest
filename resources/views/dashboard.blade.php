<x-app-layout>
    <x-slot name="header">
        <div class="tn-page-header mb-0">
            <div>
                <h1 class="tn-page-title">Dashboard</h1>
                <p class="tn-page-description">{{ $workspace?->name ?? 'Select or create a workspace to continue.' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(! $workspace)
                <div class="tn-card max-w-xl">
                    <h2 class="text-xl font-bold text-white">Create your first workspace</h2>
                    <p class="text-sm text-slate-400 mt-2">Workspaces keep your projects, teammates, and activity isolated from other teams.</p>
                    <a href="{{ route('workspaces.index') }}" class="tn-btn-primary inline-block mt-4">Create a workspace</a>
                </div>
            @else
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Total Tasks</div><div class="text-3xl font-extrabold text-white mt-1">{{ $total }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Done</div><div class="text-3xl font-extrabold text-white mt-1">{{ $done }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Overdue</div><div class="text-3xl font-extrabold text-white mt-1">{{ $overdue }}</div></div>
                    <div class="tn-card"><div class="text-xs uppercase tracking-wider text-slate-500">Completion Rate</div><div class="text-3xl font-extrabold text-brand-400 mt-1">{{ $completionRate }}%</div></div>
                </div>

                <div class="tn-card">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">Recent Activity</h3>
                        <a href="{{ route('activity.index') }}" class="text-sm tn-link">View all</a>
                    </div>
                    @forelse($recentActivities as $activity)
                        <div class="py-2 border-t border-slate-800 first:border-t-0">
                            <div class="text-sm text-slate-200"><strong class="text-white">{{ $activity->actor?->name ?? 'System' }}</strong> {{ $activity->action }}</div>
                            <div class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Nothing here yet.</p>
                    @endforelse
                </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <a href="{{ route('projects.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Projects</p>
                    </a>
                    <a href="{{ route('members.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Members</p>
                    </a>
                    <a href="{{ route('activity.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Activity</p>
                    </a>
                    <a href="{{ route('notifications.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Notifications</p>
                    </a>
                    <a href="{{ route('analytics.index') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Analytics</p>
                    </a>
                    <a href="{{ route('workspaces.settings.edit') }}" class="tn-card hover:border-brand-500 transition">
                        <p class="text-sm font-semibold text-white">Workspace Settings</p>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
