<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>TeamNest — {{ config('app.name', 'Projects, tracked. Nothing lost.') }}</title>
        <meta name="description" content="Multi-tenant team workspaces with a Kanban board and a full audit trail — every change, who made it, what it was before.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-950 text-slate-100">
        <div class="min-h-screen">
            <header class="border-b border-slate-800">
                <nav class="max-w-6xl mx-auto flex items-center justify-between px-6 py-5">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="h-9 w-9" />
                        <span class="text-lg font-bold text-white">TeamNest</span>
                    </a>

                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="tn-btn-primary">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold uppercase tracking-wider text-slate-400 hover:text-white">Log in</a>
                            <a href="{{ route('register') }}" class="tn-btn-primary">Get started</a>
                        @endauth
                    </div>
                </nav>
            </header>

            <main>
                <!-- Hero -->
                <section class="max-w-6xl mx-auto px-6 pt-20 pb-16">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">Multi-tenant team workspaces</p>
                    <h1 class="mt-4 text-5xl sm:text-7xl font-extrabold leading-[0.95] text-white">
                        Projects,<br>tracked.<br>Nothing lost.
                    </h1>
                    <p class="mt-6 max-w-xl text-lg text-slate-400">
                        Workspace-isolated project management with a full audit trail — every change, who made it, and what it was before.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a href="{{ route('login') }}" class="tn-btn-primary text-base px-6 py-3">Se demo &rarr;</a>
                        <a href="{{ route('register') }}" class="text-sm font-semibold uppercase tracking-wider text-slate-400 hover:text-white">Create an account</a>
                    </div>

                    <p class="mt-6 text-xs text-slate-500">
                        Demo accounts: <span class="text-slate-300">admin@example.com</span> or <span class="text-slate-300">member@example.com</span>, password <span class="text-slate-300">password</span>
                    </p>
                </section>

                <!-- Proof row -->
                <section class="border-t border-slate-800">
                    <div class="max-w-6xl mx-auto grid gap-8 px-6 py-12 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-brand-400">Kanban board</p>
                            <p class="mt-2 text-sm text-slate-400">Drag-and-drop tasks between To Do, Doing, and Done with optimistic UI updates.</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-brand-400">Audit trail</p>
                            <p class="mt-2 text-sm text-slate-400">Every tracked field change stores a before/after diff, plus who did it and when.</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-brand-400">Workspace isolation</p>
                            <p class="mt-2 text-sm text-slate-400">Cross-workspace access returns a plain 404 — no data ever leaks between tenants.</p>
                        </div>
                    </div>
                </section>

                <!-- How it works -->
                <section class="border-t border-slate-800">
                    <div class="max-w-6xl mx-auto grid gap-8 px-6 py-12 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">1/3</p>
                            <p class="mt-2 text-base font-semibold text-white">Skapa arbetsyta</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">2/3</p>
                            <p class="mt-2 text-base font-semibold text-white">Bjud in teamet</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">3/3</p>
                            <p class="mt-2 text-base font-semibold text-white">Följ arbetet</p>
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-800">
                <div class="max-w-6xl mx-auto flex flex-col items-center justify-between gap-3 px-6 py-8 text-xs text-slate-500 sm:flex-row">
                    <span>TeamNest</span>
                    <span>Byggt av <a href="https://alexahman.se" target="_blank" rel="noopener noreferrer" class="tn-link">alexahman.se</a></span>
                </div>
            </footer>
        </div>
    </body>
</html>
