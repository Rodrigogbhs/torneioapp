@props(['status'])
@if ($status)
    <div {{ $attributes->class(['feedback', 'feedback--success']) }} role="status">{{ $status }}</div>
@endif
