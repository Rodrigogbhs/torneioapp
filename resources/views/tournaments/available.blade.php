<x-ui.layout title="Torneios disponíveis">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Encontre seu próximo jogo</p>
            <h1 class="page-title">Torneios disponíveis</h1>
            <p class="page-description">Um bom encontro começa com vontade de jogar.<br>Escolha o seu torneio e entre em quadra.</p>
        </div>
        <span class="eyebrow">{{ $tournaments->count() }} {{ $tournaments->count() === 1 ? 'torneio' : 'torneios' }}</span>
    </div>
    <x-ui.feedback />
    @if ($tournaments->isEmpty())
        <div class="empty-state">
            <span class="empty-symbol" aria-hidden="true">↗</span>
            <h2>Nenhum torneio disponível no momento.</h2>
            <p>Os próximos encontros aparecerão aqui assim que forem criados.</p>
            @auth
                <x-ui.button :href="route('tournaments.create')">Criar torneio</x-ui.button>
            @else
                <x-ui.button :href="route('register')" variant="filled">Criar conta</x-ui.button>
            @endauth
        </div>
    @else
        <div class="event-grid">
            @foreach ($tournaments as $tournament)
                <x-ui.event-card :tournament="$tournament" />
            @endforeach
        </div>
    @endif
</x-ui.layout>
