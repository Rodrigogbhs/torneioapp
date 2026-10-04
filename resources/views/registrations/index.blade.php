<x-ui.layout title="Minhas inscrições">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Área do participante</p>
            <h1 class="page-title">Minhas inscrições</h1>
            <p class="page-description">Cada participante. Cada encontro. Tudo por aqui.</p>
        </div>
        <x-ui.button :href="route('tournaments.available')" variant="filled">Explorar torneios ↗</x-ui.button>
    </div>
    <x-ui.feedback />
    @if ($registrations->isEmpty())
        <div class="empty-state">
            <span class="empty-symbol" aria-hidden="true">↗</span>
            <h2>Você ainda não possui inscrições.</h2>
            <p>Encontre um torneio e inscreva os participantes da sua conta.</p>
            <x-ui.button :href="route('tournaments.available')">Encontrar um torneio</x-ui.button>
        </div>
    @else
        <div class="list-header" aria-hidden="true"><span>Participante</span><span>Torneio</span><span>Pagamento</span><span>Detalhes</span></div>
        @foreach ($registrations as $registration)
            <article class="registration-row">
                <div><h2>{{ $registration->athlete?->name ?? 'Participante não informado' }}</h2><small>Participante</small></div>
                <div><h2>{{ $registration->tournament->name }}</h2><small>{{ $registration->tournament->sport }} · <x-ui.date :value="$registration->tournament->event_date" /></small></div>
                <div><x-ui.payment-status :status="$registration->payment_status" :free="(float) $registration->tournament->registration_fee === 0.0" /></div>
                <x-ui.button :href="route('registrations.show', $registration)" variant="filled" class="button-small">Ver inscrição</x-ui.button>
            </article>
        @endforeach
    @endif
</x-ui.layout>
