<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">Verify your email</h1>
        <p class="text-sm text-slate-300">Before continuing, confirm your email address using the verification link we sent.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-lg border border-emerald-800 bg-emerald-950/60 px-3 py-2 text-sm text-emerald-300">
            A new verification link has been sent to your email.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Resend verification email</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-300 hover:text-white">Log out</button>
        </form>
    </div>
</x-guest-layout>
