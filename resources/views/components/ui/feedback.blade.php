@props(['validation' => true])
@if (session('success'))
    <div class="feedback feedback--success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="feedback feedback--error" role="alert">{{ session('error') }}</div>
@endif
@if ($validation && $errors->any())
    <div class="feedback feedback--error" role="alert">
        <strong>Confira as informações antes de continuar.</strong>
        <ul class="mt-2 list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
