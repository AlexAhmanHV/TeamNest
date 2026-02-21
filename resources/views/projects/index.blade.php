<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Projects</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex justify-between items-center"><a href="{{ route('projects.trash') }}" class="text-sm text-gray-600">View Trash</a></div>
        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-3">Create Project</h3>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-3">@csrf
                <x-text-input name="name" placeholder="Name" class="w-full" required />
                <textarea name="description" class="w-full rounded border-gray-300" placeholder="Description"></textarea>
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>

        <div class="bg-white p-6 rounded shadow-sm">
            <table class="w-full text-sm"><thead><tr class="text-left"><th>Name</th><th>Actions</th></tr></thead><tbody>
                @foreach($projects as $project)
                    <tr class="border-t"><td class="py-2">{{ $project->name }}</td>
                        <td class="py-2 flex gap-3">
                            <a href="{{ route('projects.show', $project) }}" class="text-blue-600">Open</a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
            <div class="mt-4">{{ $projects->links() }}</div>
        </div>
    </div></div>
</x-app-layout>
