@props(['active'])

@php
$classes = ($active ?? false) ? 'tn-nav-link-active' : 'tn-nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
