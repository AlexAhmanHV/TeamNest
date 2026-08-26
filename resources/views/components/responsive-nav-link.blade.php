@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand-500 text-start text-sm font-semibold uppercase tracking-wider text-white bg-slate-800/60 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-sm font-semibold uppercase tracking-wider text-slate-400 hover:text-white hover:bg-slate-800/40 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
