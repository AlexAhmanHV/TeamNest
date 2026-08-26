<x-app-layout>
    <x-slot name="header">
        <p class="tn-page-title">Projects</p>
    </x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex justify-end"><a href="{{ route('projects.trash') }}" class="text-sm tn-link">View Trash</a></div>
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Create Project</h3>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-3">@csrf
                <x-text-input name="name" placeholder="Name" class="w-full" required />
                <textarea name="description" class="tn-input w-full" placeholder="Description"></textarea>
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>

        <div class="tn-card">
            <table class="tn-table"><thead><tr><th>Name</th><th>Actions</th></tr></thead><tbody>
                @foreach($projects as $project)
                    <tr><td>{{ $project->name }}</td>
                        <td class="flex gap-3">
                            <a href="{{ route('projects.show', $project) }}" class="tn-link">Open</a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')<button class="text-rose-400 hover:text-rose-300">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
            <div class="mt-4">{{ $projects->links() }}</div>
        </div>
    </div></div>
</x-app-layout>
