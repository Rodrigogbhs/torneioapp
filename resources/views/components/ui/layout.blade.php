@props(['title' => null, 'authPage' => false])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="ui-shell {{ auth()->check() && ! $authPage ? 'ui-shell--authenticated' : '' }}">
    <a href="#main-content" class="skip-link">Pular para o conteúdo</a>
    <header class="site-header">
        <div class="header-inner">
            <x-app-logo :href="route('home')" />
            @auth
                @unless ($authPage)
                    <x-ui.button :href="route('tournaments.create')" class="header-create" aria-label="Criar torneio">
                        <span class="plus" aria-hidden="true">+</span> Criar torneio
                    </x-ui.button>
                @endunless
            @endauth
            @unless ($authPage)
                @include('partials.tournament-navigation')
            @endunless
            <div class="header-actions">
                @if ($authPage)
                    <a class="nav-link hidden sm:block" href="{{ route('tournaments.available') }}">Explorar torneios</a>
                @endif
                <x-ui.theme-toggle />
                @auth
                    <details class="account-menu">
                        <summary aria-label="Menu da conta">{{ auth()->user()->initials() }}</summary>
                        <div class="account-dropdown">
                            <p><strong>{{ auth()->user()->name }}</strong><br><span class="text-muted">{{ auth()->user()->email }}</span></p>
                            <a href="{{ route('profile.edit') }}">Configurações da conta</a>
                            <button type="submit" form="account-logout" data-test="logout-button">Sair da conta</button>
                        </div>
                    </details>
                @else
                    @unless ($authPage)
                        <a href="{{ route('login') }}" class="nav-link">Entrar</a>
                        <x-ui.button :href="route('register')" class="guest-register">Criar conta</x-ui.button>
                    @endunless
                @endauth
            </div>
        </div>
    </header>
    <main id="main-content" class="page-container" tabindex="-1">
        {{ $slot }}
    </main>
    <footer class="site-footer">
        <span>Menos organização complicada. Mais jogo.</span>
        <span>torneio. &nbsp; / &nbsp; Feito para o esporte.</span>
    </footer>
    @auth
        <form id="account-logout" method="POST" action="{{ route('logout') }}" hidden>
            @csrf
        </form>
        @unless ($authPage)
            <nav class="mobile-nav" aria-label="Navegação no celular">
                <a href="{{ route('dashboard') }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">Painel</a>
                <a href="{{ route('tournaments.available') }}" aria-current="{{ request()->routeIs('tournaments.available', 'tournaments.show') ? 'page' : 'false' }}">Torneios disponíveis</a>
                <a href="{{ route('tournaments.index') }}" aria-current="{{ request()->routeIs('tournaments.index', 'tournaments.manage', 'tournaments.create') ? 'page' : 'false' }}">Meus torneios</a>
                <a href="{{ route('registrations.index') }}" aria-current="{{ request()->routeIs('registrations.*') ? 'page' : 'false' }}">Minhas inscrições</a>
            </nav>
        @endunless
    @endauth
    @persist('toast')
        <flux:toast.group><flux:toast /></flux:toast.group>
    @endpersist
    @fluxScripts
    @livewireScripts
</body>
</html>
