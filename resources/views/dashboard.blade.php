<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold">Current Workspace</h3>
                <p class="text-gray-700 mt-2">{{ $workspace?->name ?? 'Select or create a workspace to continue.' }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <a href="{{ route('projects.index') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Projects</a>
                <a href="{{ route('members.index') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Members</a>
                <a href="{{ route('activity.index') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Activity</a>
                <a href="{{ route('notifications.index') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Notifications</a>
                <a href="{{ route('analytics.index') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Analytics</a>
                <a href="{{ route('workspaces.settings.edit') }}" class="bg-white shadow-sm rounded-lg p-4 border hover:bg-gray-50">Workspace Settings</a>
            </div>
        </div>
    </div>
</x-app-layout>
