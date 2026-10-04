@props(['status', 'free' => false, 'context' => 'participant'])
@php
    $confirmed = $free || $status === 'confirmed';
    $label = $free ? 'Inscrição gratuita' : ($status === 'confirmed' ? ($context === 'manage' ? 'Pagamento confirmado' : 'Pagamento: confirmado') : ($context === 'manage' ? 'Pagamento aguardando confirmação' : 'Pagamento: aguardando confirmação'));
@endphp
<span class="badge {{ $confirmed ? 'badge--confirmed' : 'badge--pending' }}" aria-label="{{ $label }}">
    {{ $free ? 'Inscrição gratuita' : ($confirmed ? 'Confirmado' : 'Pendente') }}
</span>
