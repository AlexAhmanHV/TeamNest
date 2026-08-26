<x-app-layout>
    <x-slot name="header">
        <h1 class="tn-page-title">Workspaces</h1>
    </x-slot>
    <div class="py-8"><div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Your Workspaces</h3>
            <ul class="space-y-2">
                @foreach($workspaces as $workspace)
                    <li class="flex justify-between items-center tn-row">
                        <span class="text-sm text-slate-200">{{ $workspace->name }} <span class="tn-badge-neutral ml-2">{{ $workspace->pivot->role }}</span></span>
                        <form method="POST" action="{{ route('workspaces.switch') }}">@csrf
                            <input type="hidden" name="workspace_id" value="{{ $workspace->id }}">
                            <x-primary-button>{{ session('current_workspace_id') == $workspace->id ? 'Current' : 'Switch' }}</x-primary-button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Create Workspace</h3>
            <form method="POST" action="{{ route('workspaces.store') }}" class="space-y-3">@csrf
                <x-input-label for="name" value="Name" />
                <x-text-input name="name" id="name" class="w-full" required />
                <x-input-error :messages="$errors->get('name')" />
                <x-primary-button>Create</x-primary-button>
            </form>
        </div>
    </div></div>
</x-app-layout>
