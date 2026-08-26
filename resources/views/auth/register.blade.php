<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Create your account</h1>
        <p class="text-sm text-slate-300">Join Workspace Projects and start collaborating.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Name" class="text-slate-400" />
            <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" class="text-slate-400" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email', request('email'))" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" class="text-slate-400" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" class="text-slate-400" />
            <x-text-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-2 flex items-center justify-between gap-3">
            <a class="text-sm tn-link" href="{{ route('login') }}">Already registered?</a>
            <x-primary-button>Register</x-primary-button>
        </div>
    </form>
</x-guest-layout>
