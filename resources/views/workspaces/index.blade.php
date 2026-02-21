<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Workspaces</h2></x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-4">Your Workspaces</h3>
            <ul class="space-y-2">
                @foreach($workspaces as $workspace)
                    <li class="flex justify-between border p-3 rounded">
                        <span>{{ $workspace->name }} ({{ $workspace->pivot->role }})</span>
                        <form method="POST" action="{{ route('workspaces.switch') }}">@csrf
                            <input type="hidden" name="workspace_id" value="{{ $workspace->id }}">
                            <x-primary-button>{{ session('current_workspace_id') == $workspace->id ? 'Current' : 'Switch' }}</x-primary-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-4">Create Workspace</h3>
            <form method="POST" action="{{ route('workspaces.store') }}" class="space-y-3">@csrf
                <x-input-label for="name" value="Name" />
                <x-text-input name="name" id="name" class="w-full" required />
                <x-input-error :messages="$errors->get('name')" />
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>
    </div></div>
</x-app-layout>
