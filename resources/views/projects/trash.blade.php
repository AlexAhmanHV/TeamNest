<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Project Trash</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <a href="{{ route('projects.index') }}" class="text-sm text-gray-600">Back to Projects</a>
        <table class="w-full text-sm mt-4"><thead><tr class="text-left"><th>Name</th><th>Deleted At</th><th>Actions</th></tr></thead><tbody>
            @foreach($projects as $project)
            <tr class="border-t"><td class="py-2">{{ $project->name }}</td><td>{{ $project->deleted_at }}</td><td class="flex gap-2 py-2">
                <form method="POST" action="{{ route('projects.restore', $project) }}">@csrf <button class="text-blue-600">Restore</button></form>
                <form method="POST" action="{{ route('projects.forceDelete', $project) }}">@csrf @method('DELETE') <button class="text-red-600">Force Delete</button></form>
            </td></tr>
            @endforeach
        </tbody></table>
        <div class="mt-4">{{ $projects->links() }}</div>
    </div></div>
</x-app-layout>
