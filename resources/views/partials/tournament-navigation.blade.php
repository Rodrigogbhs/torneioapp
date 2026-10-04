<nav class="desktop-nav" aria-label="Navegação principal">
    @auth
        <a class="nav-link" href="{{ route('dashboard') }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">Dashboard</a>
    @endauth
    <a class="nav-link" href="{{ route('tournaments.available') }}" aria-current="{{ request()->routeIs('tournaments.available', 'tournaments.show') ? 'page' : 'false' }}">Torneios disponíveis</a>
    @auth
        <a class="nav-link" href="{{ route('tournaments.index') }}" aria-current="{{ request()->routeIs('tournaments.index', 'tournaments.manage', 'tournaments.create') ? 'page' : 'false' }}">Meus torneios</a>
        <a class="nav-link" href="{{ route('registrations.index') }}" aria-current="{{ request()->routeIs('registrations.*') ? 'page' : 'false' }}">Minhas inscrições</a>
    @endauth
</nav>
