<x-app-layout>
    <x-slot name="header">
        <div class="tn-page-header mb-0">
            <div>
                <p class="tn-page-title">Dashboard</p>
                <p class="tn-page-description">{{ $workspace?->name ?? 'Select or create a workspace to continue.' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
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
        </div>
    </div>
</x-app-layout>
