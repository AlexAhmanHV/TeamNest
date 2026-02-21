<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $project->name }}</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white p-6 rounded shadow-sm">
            <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-3">@csrf @method('PATCH')
                <x-text-input name="name" value="{{ $project->name }}" class="w-full" required/>
                <textarea name="description" class="w-full rounded border-gray-300">{{ $project->description }}</textarea>
                <x-primary-button>Update Project</x-primary-button>
            </form>
        </div>
        <a href="{{ route('tasks.index', $project) }}" class="text-blue-600">Open Tasks</a>
    </div></div>
</x-app-layout>
