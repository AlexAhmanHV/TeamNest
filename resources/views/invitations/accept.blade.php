<x-guest-layout>
    <div class="space-y-1 mb-6">
        <h1 class="text-2xl font-extrabold text-white">You're invited</h1>
        <p class="text-sm text-slate-300">Invitation for <strong class="text-white">{{ $invitation->email }}</strong> to join <strong class="text-white">{{ $invitation->workspace->name }}</strong> as <strong class="text-white">{{ $invitation->role }}</strong>.</p>
    </div>

    @auth
    <form method="POST" action="{{ route('invites.accept', ['token' => $token]) }}">@csrf
        <x-primary-button>Accept Invitation</x-primary-button>
    </form>
    @else
    <a href="{{ route('login') }}" class="tn-link text-sm">Login to accept</a>
    @endauth
</x-guest-layout>
