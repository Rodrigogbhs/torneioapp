<x-ui.layout title="Meus torneios">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Área do organizador</p>
            <h1 class="page-title">Meus torneios</h1>
            <p class="page-description">Os encontros que você coloca em movimento.</p>
        </div>
        <x-ui.button :href="route('tournaments.create')"><span class="plus" aria-hidden="true">+</span> Criar novo torneio</x-ui.button>
    </div>
    <x-ui.feedback />
    @forelse ($tournaments as $tournament)
        <article class="owned-event">
            <div>
                <span class="sport-tag">{{ $tournament->sport }}</span>
                <h2>{{ $tournament->name }}</h2>
                <div class="info-line">
                    <span><x-ui.date :value="$tournament->event_date" /></span>
                    <span>{{ $tournament->location }}</span>
                    <span>{{ $tournament->registrations_count }} {{ $tournament->registrations_count === 1 ? 'inscrito' : 'inscritos' }}</span>
                    <span>{{ (float) $tournament->registration_fee === 0.0 ? 'Inscrição gratuita' : 'R$ '.number_format($tournament->registration_fee, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="row-actions">
                <x-ui.button :href="route('tournaments.manage', $tournament)" variant="filled">Gerenciar torneio</x-ui.button>
                <x-ui.button :href="route('tournaments.show', $tournament)" variant="subtle" class="button-small">Página pública ↗</x-ui.button>
                <details class="action-disclosure">
                    <summary aria-label="Mais ações para {{ $tournament->name }}">⋯</summary>
                    <div>
                        <form action="{{ route('tournaments.destroy', $tournament) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este torneio?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="subtle" class="button-small button-danger button-wide">Excluir torneio</x-ui.button>
                        </form>
                    </div>
                </details>
            </div>
        </article>
    @empty
        <div class="empty-state">
            <span class="empty-symbol" aria-hidden="true">+</span>
            <h2>Você ainda não criou nenhum torneio.</h2>
            <p>Seu próximo encontro pode começar agora. Escolha um esporte e convide os participantes.</p>
            <x-ui.button :href="route('tournaments.create')">Criar meu primeiro torneio</x-ui.button>
        </div>
    @endforelse
</x-ui.layout>
