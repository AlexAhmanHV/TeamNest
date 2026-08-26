<x-app-layout>
    <x-slot name="header">
        <p class="tn-page-title">Project Trash</p>
    </x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 tn-card">
        <a href="{{ route('projects.index') }}" class="text-sm tn-link">Back to Projects</a>
        <table class="tn-table mt-4"><thead><tr><th>Name</th><th>Deleted At</th><th>Actions</th></tr></thead><tbody>
            @foreach($projects as $project)
            <tr><td>{{ $project->name }}</td><td>{{ $project->deleted_at }}</td><td class="flex gap-2">
                <form method="POST" action="{{ route('projects.restore', $project) }}">@csrf <button class="tn-link">Restore</button></form>
                <form method="POST" action="{{ route('projects.forceDelete', $project) }}">@csrf @method('DELETE') <button class="text-rose-400 hover:text-rose-300">Force Delete</button></form>
            </td></tr>
            @endforeach
        </tbody></table>
        <div class="mt-4">{{ $projects->links() }}</div>
    </div></div>
</x-app-layout>
