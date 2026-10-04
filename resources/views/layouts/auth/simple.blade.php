<x-ui.layout :title="$title ?? null" :auth-page="true">
    <div class="auth-layout">
        <aside class="auth-story" aria-label="Sobre o Torneio">
            <p class="eyebrow">O esporte aproxima.</p>
            <h2 class="hero-title">A gente cuida<br>do <em>começo.</em></h2>
            <p class="hero-copy">Do primeiro convite à última inscrição. Um lugar simples para organizar torneios e entrar em jogo.</p>
            <x-ui.court />
        </aside>
        <div class="auth-form">{{ $slot }}</div>
    </div>
</x-ui.layout>
