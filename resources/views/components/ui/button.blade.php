@props(['href' => null, 'variant' => 'primary', 'type' => 'button'])
<flux:button :href="$href" :variant="$variant" :type="$href ? null : $type" {{ $attributes->class(['button', 'button--'.$variant]) }}>
    {{ $slot }}
</flux:button>
