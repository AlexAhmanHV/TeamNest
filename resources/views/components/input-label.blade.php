@props(['value'])

<label {{ $attributes->merge(['class' => 'tn-label']) }}>
    {{ $value ?? $slot }}
</label>
