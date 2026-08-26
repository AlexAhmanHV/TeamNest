<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tn-btn-danger']) }}>
    {{ $slot }}
</button>
