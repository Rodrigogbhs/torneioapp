<x-ui.layout title="Novo participante">
    <div class="form-page">
        <a href="{{ $tournament ? route('tournaments.show', $tournament) : route('tournaments.available') }}" class="back-link">← {{ $tournament ? 'Voltar ao torneio' : 'Voltar aos torneios' }}</a>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Sua equipe começa aqui</p>
                <h1 class="page-title">Novo participante</h1>
                <p class="page-description">Cadastre quem vai entrar em jogo com você.</p>
            </div>
        </div>
        <x-ui.feedback />
        <form action="{{ route('athletes.store') }}" method="POST" class="panel form-stack">
            @csrf
            @if ($tournament)
                <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">
            @endif
            <x-ui.field name="name" label="Nome do participante" hint="Use o nome que deve aparecer na lista de inscritos.">
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" maxlength="255" placeholder="Nome completo" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-hint{{ $errors->has('name') ? ' name-error' : '' }}" autocomplete="name" required>
            </x-ui.field>
            <x-ui.button type="submit" class="button-wide">Cadastrar participante</x-ui.button>
        </form>
        <div class="form-note">
            <span class="form-note-number">01 → 02</span>
            @if ($tournament)
                <p>Este cadastro adiciona um participante à sua conta. Depois, você voltará ao torneio <strong>{{ $tournament->name }}</strong> para concluir a inscrição clicando em "Inscrever-se".</p>
            @else
                <p>Este cadastro adiciona um participante à sua conta. Para inscrevê-lo, abra um torneio, selecione o participante e clique em "Inscrever-se".</p>
            @endif
        </div>
    </div>
</x-ui.layout>
