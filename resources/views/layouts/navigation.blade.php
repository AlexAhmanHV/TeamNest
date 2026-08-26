<nav x-data="{ open: false }" class="bg-slate-950 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto" />
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link :href="route('workspaces.index')" :active="request()->routeIs('workspaces.*')">Workspaces</x-nav-link>
                    <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*') || request()->routeIs('tasks.*')">Projects</x-nav-link>
                    <x-nav-link :href="route('members.index')" :active="request()->routeIs('members.*') || request()->routeIs('invites.*')">Members</x-nav-link>
                    <x-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')">Activity</x-nav-link>
                    <x-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')">Analytics</x-nav-link>
                    <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                        Notifications
                        @php
                            $unreadCount = auth()->user()->workspaceNotifications()
                                ->where('workspace_id', session('current_workspace_id'))
                                ->whereNull('read_at')
                                ->count();
                        @endphp
                        @if($unreadCount > 0)
                            <span class="ml-1 tn-badge-brand">{{ $unreadCount }}</span>
                        @endif
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-3">
                <form method="POST" action="{{ route('workspaces.switch') }}">
                    @csrf
                    <select name="workspace_id" onchange="this.form.submit()" class="tn-input py-1.5 text-sm">
                        @foreach(auth()->user()->workspaces()->orderBy('name')->get() as $navWorkspace)
                            <option value="{{ $navWorkspace->id }}" @selected(session('current_workspace_id') == $navWorkspace->id)>{{ $navWorkspace->name }}</option>
                        @endforeach
                    </select>
                </form>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-300 hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        <x-dropdown-link :href="route('workspaces.settings.edit')">Workspace Settings</x-dropdown-link>
                        <x-dropdown-link :href="route('tokens.index')">API Tokens</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log Out</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-800">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('workspaces.index')" :active="request()->routeIs('workspaces.*')">Workspaces</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*') || request()->routeIs('tasks.*')">Projects</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('members.index')" :active="request()->routeIs('members.*') || request()->routeIs('invites.*')">Members</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('activity.index')" :active="request()->routeIs('activity.*')">Activity</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')">Analytics</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">Notifications</x-responsive-nav-link>
        </div>
    </div>
</nav>
