<x-layouts::app title="Dashboard">
    <section class="dashboard-hero">
        <div>
            <p class="eyebrow">Olá, {{ explode(' ', auth()->user()->name)[0] }}. Vamos jogar?</p>
            <h1 class="hero-title">Grandes encontros.<br><em>Começos simples.</em></h1>
            <p class="hero-copy">Reúna os participantes. Crie o seu torneio.<br>A organização fica por aqui; o esporte acontece lá fora.</p>
            <x-ui.button :href="route('tournaments.create')"><span class="plus" aria-hidden="true">+</span> Criar torneio</x-ui.button>
        </div>
        <x-ui.court />
    </section>
    <section class="mt-9" aria-labelledby="quick-links-title">
        <p class="eyebrow" id="quick-links-title">Seu jogo, em movimento</p>
        <div class="shortcut-grid">
            <a href="{{ route('tournaments.available') }}" class="shortcut">
                <span class="shortcut-number">01 / DESCOBRIR</span>
                <span class="shortcut-title">Torneios disponíveis <span class="shortcut-arrow" aria-hidden="true">↗</span></span>
                <p>Encontre o próximo torneio para você e os seus participantes.</p>
            </a>
            <a href="{{ route('tournaments.index') }}" class="shortcut">
                <span class="shortcut-number">02 / ORGANIZAR</span>
                <span class="shortcut-title">Meus torneios <span class="shortcut-arrow" aria-hidden="true">↗</span></span>
                <p>Gerencie inscritos, pagamentos e os eventos que você criou.</p>
            </a>
            <a href="{{ route('registrations.index') }}" class="shortcut">
                <span class="shortcut-number">03 / PARTICIPAR</span>
                <span class="shortcut-title">Minhas inscrições <span class="shortcut-arrow" aria-hidden="true">↗</span></span>
                <p>Seus participantes, suas inscrições e cada confirmação.</p>
            </a>
        </div>
    </section>
</x-layouts::app>
