<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Confirm your password</h1>
        <p class="text-sm text-slate-300">This is a secure action. Re-enter your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Password" class="text-slate-400" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-2 flex justify-end">
            <x-primary-button>Confirm</x-primary-button>
        </div>
    </form>
</x-guest-layout>
