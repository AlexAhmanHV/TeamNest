<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Reset your password</h1>
        <p class="text-sm text-slate-300">Enter your account email and we will send a secure reset link.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" class="text-slate-400" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-2 flex items-center justify-between gap-3">
            <a class="text-sm tn-link" href="{{ route('login') }}">Back to login</a>
            <x-primary-button>Send reset link</x-primary-button>
        </div>
    </form>
</x-guest-layout>
