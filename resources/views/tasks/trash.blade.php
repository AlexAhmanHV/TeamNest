<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Task Trash: {{ $project->name }}</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow-sm">
        <a href="{{ route('tasks.index', $project) }}" class="text-sm text-gray-600">Back to Tasks</a>
        <table class="w-full text-sm mt-4"><thead><tr class="text-left"><th>Title</th><th>Deleted At</th><th>Actions</th></tr></thead><tbody>
            @foreach($tasks as $task)
            <tr class="border-t"><td class="py-2">{{ $task->title }}</td><td>{{ $task->deleted_at }}</td><td class="flex gap-2 py-2">
                <form method="POST" action="{{ route('tasks.restore', $task) }}">@csrf <button class="text-blue-600">Restore</button></form>
                <form method="POST" action="{{ route('tasks.forceDelete', $task) }}">@csrf @method('DELETE') <button class="text-red-600">Force Delete</button></form>
            </td></tr>
            @endforeach
        </tbody></table>
        <div class="mt-4">{{ $tasks->links() }}</div>
    </div></div>
</x-app-layout>
