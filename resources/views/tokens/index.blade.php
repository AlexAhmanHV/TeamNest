<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">API Tokens</h2></x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white p-6 rounded shadow-sm">
                <h3 class="font-semibold mb-3">Create Token</h3>
                <form method="POST" action="{{ route('tokens.store') }}" class="flex gap-2">@csrf
                    <input name="name" class="rounded border-gray-300 flex-1" placeholder="Token name" required>
                    <x-primary-button>Create</x-primary-button>
                </form>
                <p class="text-xs text-gray-500 mt-2">Use header: <code>Authorization: Bearer YOUR_TOKEN</code></p>
            </div>

            <div class="bg-white p-6 rounded shadow-sm">
                <h3 class="font-semibold mb-3">Active Tokens</h3>
                <table class="w-full text-sm">
                    <thead><tr class="text-left"><th>Name</th><th>Last Used</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        @forelse($tokens as $token)
                            <tr class="border-t"><td class="py-2">{{ $token->name }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->created_at->toDateTimeString() }}</td><td>
                                <form method="POST" action="{{ route('tokens.destroy', $token->id) }}">@csrf @method('DELETE')<button class="text-red-600">Revoke</button></form>
                            </td></tr>
                        @empty
                            <tr><td colspan="4" class="text-gray-500 py-2">No tokens yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
