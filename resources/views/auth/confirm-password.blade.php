<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Confirm your password</h1>
        <p class="text-sm text-slate-300">This is a secure action. Re-enter your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Password" class="text-slate-200" />
            <x-text-input id="password" class="mt-1 block w-full border-slate-600 bg-slate-900 text-slate-100" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-2 flex justify-end">
            <x-primary-button class="bg-cyan-600 hover:bg-cyan-500 focus:bg-cyan-500">Confirm</x-primary-button>
        </div>
    </form>
</x-guest-layout>
