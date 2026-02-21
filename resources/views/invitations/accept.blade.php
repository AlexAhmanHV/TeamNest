<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">Invitation for <strong>{{ $invitation->email }}</strong> to join <strong>{{ $invitation->workspace->name }}</strong> as <strong>{{ $invitation->role }}</strong>.</div>

    @auth
    <form method="POST" action="{{ route('invites.accept', ['token' => $token]) }}">@csrf
        <x-primary-button>Accept Invitation</x-primary-button>
    </form>
    @else
    <a href="{{ route('login') }}" class="underline text-sm text-gray-600">Login to accept</a>
    @endauth
</x-guest-layout>
