<x-ui.layout title="O seu próximo jogo">
    <section class="dashboard-hero">
        <div>
            <p class="eyebrow">Para quem vive o esporte</p>
            <h1 class="hero-title">Mais jogo.<br><em>Menos complicação.</em></h1>
            <p class="hero-copy">Organize o seu torneio, reúna os participantes e encontre o próximo encontro. Tudo começa por aqui.</p>
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('tournaments.available')">Torneios disponíveis ↗</x-ui.button>
                @auth
                    <x-ui.button :href="route('dashboard')" variant="filled">Abrir meu painel</x-ui.button>
                @else
                    <x-ui.button :href="route('register')" variant="filled">Criar conta</x-ui.button>
                @endauth
            </div>
        </div>
        <x-ui.court />
    </section>
    <section class="shortcut-grid" aria-label="Como começar">
        <div class="shortcut"><span class="shortcut-number">01 / ENCONTRE</span><h2 class="shortcut-title">Um torneio para você.</h2><p>Explore os encontros disponíveis e escolha onde quer jogar.</p></div>
        <div class="shortcut"><span class="shortcut-number">02 / REÚNA</span><h2 class="shortcut-title">Quem entra em quadra.</h2><p>Cadastre seus participantes e faça cada inscrição com clareza.</p></div>
        <div class="shortcut"><span class="shortcut-number">03 / ORGANIZE</span><h2 class="shortcut-title">O próximo encontro.</h2><p>Crie um torneio, compartilhe o convite e acompanhe seus inscritos.</p></div>
    </section>
</x-ui.layout>
