<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tn-btn-primary']) }}>
    {{ $slot }}
</button>
