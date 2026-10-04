@props(['value'])
<time datetime="{{ $value }}">{{ \Illuminate\Support\Carbon::parse($value)->locale('pt_BR')->translatedFormat('d M Y') }}</time>
