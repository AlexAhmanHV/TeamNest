<x-app-layout>
    <x-slot name="header">
        <p class="tn-page-title">Workspace Settings</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="tn-card">
                <form method="POST" action="{{ route('workspaces.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <x-input-label value="Workspace name" />
                        <x-text-input name="name" class="w-full" :value="$workspace->name" required />
                    </div>

                    <div>
                        <x-input-label value="Default invite role" />
                        <select name="default_invite_role" class="tn-input w-full">
                            <option value="member" @selected($workspace->default_invite_role === 'member')>member</option>
                            <option value="admin" @selected($workspace->default_invite_role === 'admin')>admin</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Timezone" />
                        <select name="timezone" class="tn-input w-full">
                            @foreach($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected($workspace->timezone === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Logo" />
                        <input type="file" name="logo" class="w-full text-sm text-slate-300" />
                    </div>

                    <div>
                        <x-input-label value="Retention days (optional)" />
                        <x-text-input type="number" name="retention_days" class="w-full" :value="$workspace->retention_days" />
                    </div>

                    <x-primary-button>Save settings</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
