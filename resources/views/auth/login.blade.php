<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Welcome back</h1>
        <p class="text-sm text-slate-300">Sign in to continue to your workspaces.</p>
    </div>

    <x-auth-session-status class="mb-4 text-emerald-300" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" class="text-slate-200" />
            <x-text-input id="email" class="mt-1 block w-full border-slate-600 bg-slate-900 text-slate-100" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" class="text-slate-200" />
            <x-text-input id="password" class="mt-1 block w-full border-slate-600 bg-slate-900 text-slate-100" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-300">
            <input id="remember_me" type="checkbox" class="rounded border-slate-500 bg-slate-800 text-cyan-500 focus:ring-cyan-500" name="remember">
            Remember me
        </label>

        <div class="pt-2 flex items-center justify-between gap-3">
            @if (Route::has('password.request'))
                <a class="text-sm text-cyan-300 hover:text-cyan-200" href="{{ route('password.request') }}">Forgot password?</a>
            @endif

            <x-primary-button class="bg-cyan-600 hover:bg-cyan-500 focus:bg-cyan-500">Log in</x-primary-button>
        </div>
    </form>

    <p class="mt-5 text-sm text-slate-300">
        New here?
        <a href="{{ route('register') }}" class="text-cyan-300 hover:text-cyan-200">Create your account</a>
    </p>
</x-guest-layout>
