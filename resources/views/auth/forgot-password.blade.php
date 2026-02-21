<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Reset your password</h1>
        <p class="text-sm text-slate-300">Enter your account email and we will send a secure reset link.</p>
    </div>

    <x-auth-session-status class="mb-4 text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" class="text-slate-200" />
            <x-text-input id="email" class="mt-1 block w-full border-slate-600 bg-slate-900 text-slate-100" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2 flex items-center justify-between gap-3">
            <a class="text-sm text-cyan-300 hover:text-cyan-200" href="{{ route('login') }}">Back to login</a>
            <x-primary-button class="bg-cyan-600 hover:bg-cyan-500 focus:bg-cyan-500">Send reset link</x-primary-button>
        </div>
    </form>
</x-guest-layout>
