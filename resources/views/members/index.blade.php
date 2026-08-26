<x-app-layout>
    <x-slot name="header">
        <h1 class="tn-page-title">Members & Invitations</h1>
    </x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Invite Member</h3>
            <form method="POST" action="{{ route('invites.store') }}" class="grid md:grid-cols-4 gap-3">@csrf
                <x-text-input name="email" type="email" placeholder="Email" required />
                <select name="role" class="tn-input">
                    <option value="member" @selected($workspace->default_invite_role === 'member')>member</option>
                    <option value="admin" @selected($workspace->default_invite_role === 'admin')>admin</option>
                </select>
                <x-primary-button class="justify-center">Send Invite</x-primary-button>
            </form>
        </div>

        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Members</h3>
            <table class="tn-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody>
                @foreach($workspace->users as $member)
                    <tr>
                        <td>{{ $member->name }}</td><td>{{ $member->email }}</td><td><span class="tn-badge-neutral">{{ $member->pivot->role }}</span></td>
                        <td class="flex gap-2 items-center">
                            <form method="POST" action="{{ route('members.role.update', $member) }}">@csrf @method('PATCH')
                                <select name="role" class="tn-input text-xs py-1" onchange="this.form.submit()">
                                    <option value="member" @selected($member->pivot->role==='member')>member</option>
                                    <option value="admin" @selected($member->pivot->role==='admin')>admin</option>
                                </select>
                            </form>
                            @if($workspace->owner_user_id !== $member->id)
                            <form method="POST" action="{{ route('members.destroy', $member) }}">@csrf @method('DELETE')
                                <button class="text-xs text-rose-400 hover:text-rose-300">Remove</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>

        <div class="tn-card">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">Pending Invitations</h3>
            <table class="tn-table"><thead><tr><th>Email</th><th>Role</th><th>Expires</th><th>Actions</th></tr></thead><tbody>
                @foreach($workspace->invitations as $invitation)
                    <tr><td>{{ $invitation->email }}</td><td><span class="tn-badge-neutral">{{ $invitation->role }}</span></td><td>{{ $invitation->expires_at->toDateString() }}</td>
                        <td class="flex gap-2">
                            <form method="POST" action="{{ route('invites.resend', $invitation) }}">@csrf
                                <button class="text-xs tn-link">Resend</button>
                            </form>
                            <form method="POST" action="{{ route('invites.revoke', $invitation) }}">@csrf @method('DELETE')
                                <button class="text-xs text-rose-400 hover:text-rose-300">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>
    </div></div>
</x-app-layout>
