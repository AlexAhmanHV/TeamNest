<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Workspace Projects') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased" style="font-family: Manrope, sans-serif;">
        <div class="min-h-screen bg-slate-950 text-slate-100">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/70 shadow-2xl shadow-black/40 backdrop-blur">
                    <div class="grid min-h-[82vh] lg:grid-cols-2">
                        <div class="relative hidden overflow-hidden border-r border-slate-800 p-10 lg:block">
                            <div class="absolute -top-24 -left-24 h-64 w-64 rounded-full bg-cyan-500/20 blur-3xl"></div>
                            <div class="absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>

                            <a href="{{ url('/') }}" class="relative inline-flex items-center gap-3">
                                <x-application-logo class="h-11 w-11" />
                                <div>
                                    <p class="text-xs uppercase tracking-[0.25em] text-cyan-300">Mini SaaS</p>
                                    <p class="text-lg font-bold text-white">Workspace Projects</p>
                                </div>
                            </a>

                            <div class="relative mt-16 space-y-5">
                                <h1 class="text-4xl font-extrabold leading-tight text-white">Plan, assign, and ship work faster.</h1>
                                <p class="max-w-md text-slate-300">Multi-workspace project management with tasks, invites, permissions, and audit-ready activity logs.</p>
                                <div class="grid max-w-sm grid-cols-2 gap-3 pt-4 text-sm text-slate-200">
                                    <div class="rounded-xl border border-slate-700/80 bg-slate-800/60 p-3">Workspace RBAC</div>
                                    <div class="rounded-xl border border-slate-700/80 bg-slate-800/60 p-3">Task Filters</div>
                                    <div class="rounded-xl border border-slate-700/80 bg-slate-800/60 p-3">Invite Flow</div>
                                    <div class="rounded-xl border border-slate-700/80 bg-slate-800/60 p-3">Activity Log</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center p-6 sm:p-10">
                            <div class="w-full max-w-md space-y-6">
                                <div class="flex items-center gap-3 lg:hidden">
                                    <x-application-logo class="h-10 w-10" />
                                    <div>
                                        <p class="text-xs uppercase tracking-[0.22em] text-cyan-300">Workspace Projects</p>
                                        <p class="text-sm text-slate-300">Account Access</p>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-700/70 bg-slate-800/70 p-6 shadow-xl">
                                    {{ $slot }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
