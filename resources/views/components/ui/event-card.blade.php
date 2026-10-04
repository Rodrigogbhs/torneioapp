@props(['tournament'])
@php($eventDate = \Illuminate\Support\Carbon::parse($tournament->event_date)->locale('pt_BR'))
<article class="event-card" aria-labelledby="event-name-{{ $tournament->id }}">
    <div class="event-card-top">
        <span class="sport-tag">{{ $tournament->sport }}</span>
        <span class="text-muted" aria-hidden="true">↗</span>
    </div>
    <h2 id="event-name-{{ $tournament->id }}"><a href="{{ route('tournaments.show', $tournament) }}">{{ $tournament->name }}</a></h2>
    <div class="event-meta">
        <div class="date-tile" aria-hidden="true"><strong>{{ $eventDate->format('d') }}</strong><span>{{ mb_strtoupper(str_replace('.', '', $eventDate->translatedFormat('M'))) }}</span></div>
        <div class="event-location">{{ $tournament->location }}<small><x-ui.date :value="$tournament->event_date" /></small></div>
    </div>
    <div class="event-card-bottom">
        <div><span class="price-label">Inscrição</span><span class="price">{{ (float) $tournament->registration_fee === 0.0 ? 'Gratuita' : 'R$ '.number_format($tournament->registration_fee, 2, ',', '.') }}</span></div>
        <x-ui.button :href="route('tournaments.show', $tournament)" variant="filled" class="button-small">Inscrever-se</x-ui.button>
    </div>
</article>
