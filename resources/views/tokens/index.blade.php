<x-app-layout>
    <x-slot name="header"><p class="tn-page-title">API Tokens</p></x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="tn-card">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Create Token</h3>
                <form method="POST" action="{{ route('tokens.store') }}" class="flex gap-2">@csrf
                    <input name="name" class="tn-input flex-1" placeholder="Token name" required>
                    <x-primary-button>Create</x-primary-button>
                </form>
                <p class="text-xs text-slate-500 mt-2">Use header: <code class="text-slate-300">Authorization: Bearer YOUR_TOKEN</code></p>
            </div>

            <div class="tn-card">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Active Tokens</h3>
                <table class="tn-table">
                    <thead><tr><th>Name</th><th>Last Used</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        @forelse($tokens as $token)
                            <tr><td>{{ $token->name }}</td><td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td><td>{{ $token->created_at->toDateTimeString() }}</td><td>
                                <form method="POST" action="{{ route('tokens.destroy', $token->id) }}">@csrf @method('DELETE')<button class="text-rose-400 hover:text-rose-300">Revoke</button></form>
                            </td></tr>
                        @empty
                            <tr><td colspan="4" class="text-slate-500 py-2">No tokens yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
