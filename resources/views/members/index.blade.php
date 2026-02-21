<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Members & Invitations</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-3">Invite Member</h3>
            <form method="POST" action="{{ route('invites.store') }}" class="grid md:grid-cols-4 gap-3">@csrf
                <x-text-input name="email" type="email" placeholder="Email" required />
                <select name="role" class="rounded border-gray-300">
                    <option value="member" @selected($workspace->default_invite_role === 'member')>member</option>
                    <option value="admin" @selected($workspace->default_invite_role === 'admin')>admin</option>
                </select>
                <x-primary-button class="justify-center">Send Invite</x-primary-button>
            </form>
        </div>

        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-3">Members</h3>
            <table class="w-full text-sm"><thead><tr class="text-left"><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody>
                @foreach($workspace->users as $member)
                    <tr class="border-t">
                        <td class="py-2">{{ $member->name }}</td><td>{{ $member->email }}</td><td>{{ $member->pivot->role }}</td>
                        <td class="py-2 flex gap-2">
                            <form method="POST" action="{{ route('members.role.update', $member) }}">@csrf @method('PATCH')
                                <select name="role" class="rounded border-gray-300 text-xs" onchange="this.form.submit()">
                                    <option value="member" @selected($member->pivot->role==='member')>member</option>
                                    <option value="admin" @selected($member->pivot->role==='admin')>admin</option>
                                </select>
                            </form>
                            @if($workspace->owner_user_id !== $member->id)
                            <form method="POST" action="{{ route('members.destroy', $member) }}">@csrf @method('DELETE')
                                <button class="text-red-600 text-xs">Remove</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>

        <div class="bg-white p-6 rounded shadow-sm">
            <h3 class="font-semibold mb-3">Pending Invitations</h3>
            <table class="w-full text-sm"><thead><tr class="text-left"><th>Email</th><th>Role</th><th>Expires</th><th>Actions</th></tr></thead><tbody>
                @foreach($workspace->invitations as $invitation)
                    <tr class="border-t"><td class="py-2">{{ $invitation->email }}</td><td>{{ $invitation->role }}</td><td>{{ $invitation->expires_at->toDateString() }}</td>
                        <td class="py-2 flex gap-2">
                            <form method="POST" action="{{ route('invites.resend', $invitation) }}">@csrf
                                <button class="text-blue-600 text-xs">Resend</button>
                            </form>
                            <form method="POST" action="{{ route('invites.revoke', $invitation) }}">@csrf @method('DELETE')
                                <button class="text-red-600 text-xs">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>
    </div></div>
</x-app-layout>
