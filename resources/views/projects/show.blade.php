<x-app-layout>
    <x-slot name="header">
        <h1 class="tn-page-title">{{ $project->name }}</h1>
    </x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="tn-card">
            <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-3">@csrf @method('PATCH')
                <x-text-input name="name" value="{{ $project->name }}" class="w-full" required/>
                <textarea name="description" class="tn-input w-full">{{ $project->description }}</textarea>
                <x-primary-button>Update Project</x-primary-button>
            </form>
        </div>
        <a href="{{ route('tasks.index', $project) }}" class="tn-link">Open Tasks</a>
    </div></div>
</x-app-layout>
