@props(['name', 'label', 'hint' => null])
<div {{ $attributes->class(['form-field']) }}>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $name }}-hint" class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="field-error" role="alert">{{ $message }}</p>
    @enderror
</div>
