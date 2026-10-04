<x-ui.layout :title="$tournament->name">
    <a href="{{ route('tournaments.available') }}" class="back-link">← Voltar aos torneios disponíveis</a>
    <x-ui.feedback />
    <header class="event-hero">
        <span class="sport-tag">{{ $tournament->sport }}</span>
        <h1 class="page-title">{{ $tournament->name }}</h1>
        <div class="info-line"><span><x-ui.date :value="$tournament->event_date" /></span><span>{{ $tournament->location }}</span></div>
    </header>
    <div class="content-grid">
        <section class="panel" aria-labelledby="participant-choice-title">
            <p class="eyebrow mb-3">Entre em jogo</p>
            <h2 class="section-title" id="participant-choice-title">Quem vai participar?</h2>
            @auth
                @if ($athletes->isEmpty())
                    <p class="page-description mb-6">Você ainda não possui participantes cadastrados.</p>
                    <p class="form-hint mb-6">Cadastre o primeiro participante. Depois, você volta aqui para concluir a inscrição.</p>
                    <x-ui.button :href="route('athletes.create', ['tournament' => $tournament->id])">Cadastrar participante</x-ui.button>
                @else
                    <p class="page-description mb-6">Selecione um participante da sua conta para este torneio.</p>
                    <form action="{{ route('registrations.store') }}" method="POST" class="form-stack">
                        @csrf
                        <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
                        <x-ui.field name="athlete_id" label="Selecione o participante">
                            <select class="form-input" id="athlete_id" name="athlete_id" aria-invalid="{{ $errors->has('athlete_id') ? 'true' : 'false' }}" @if ($errors->has('athlete_id')) aria-describedby="athlete_id-error" @endif required>
                                <option value="" @selected(! old('athlete_id', session('selected_athlete_id')))>Selecione um participante</option>
                                @foreach ($athletes as $athlete)
                                    <option value="{{ $athlete->id }}" @selected(old('athlete_id', session('selected_athlete_id')) == $athlete->id)>{{ $athlete->name }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.button type="submit" class="button-wide">Inscrever-se <span aria-hidden="true">↗</span></x-ui.button>
                    </form>
                    <p class="mt-5"><a href="{{ route('athletes.create', ['tournament' => $tournament->id]) }}" class="text-link">Cadastrar outro participante</a></p>
                    <p class="form-hint section-divider">O cadastro do participante não conclui a inscrição no torneio. Selecione o participante e clique em "Inscrever-se" para inscrevê-lo.</p>
                @endif
            @else
                <p class="page-description mb-6">Crie sua conta ou entre para cadastrar seus participantes e fazer a inscrição.</p>
                <x-ui.button :href="route('login')" class="button-wide">Entre para se inscrever</x-ui.button>
                <p class="mt-5 text-center"><a href="{{ route('register') }}" class="text-link">Ainda não tem conta? Criar conta</a></p>
            @endauth
        </section>
        <aside class="soft-panel" aria-labelledby="event-summary-title">
            <p class="eyebrow mb-6" id="event-summary-title">O encontro</p>
            <dl class="definition-list">
                <div><dt>Esporte</dt><dd>{{ $tournament->sport }}</dd></div>
                <div><dt>Data</dt><dd><x-ui.date :value="$tournament->event_date" /></dd></div>
                <div><dt>Local</dt><dd>{{ $tournament->location }}</dd></div>
            </dl>
            <div class="summary-price">
                <span class="form-hint">Valor da inscrição<br>por participante</span>
                <span class="price">{{ (float) $tournament->registration_fee === 0.0 ? 'Gratuita' : 'R$ '.number_format($tournament->registration_fee, 2, ',', '.') }}</span>
            </div>
            <p class="form-hint section-divider">Uma conta pode inscrever até 4 participantes neste torneio.</p>
        </aside>
    </div>
</x-ui.layout>
