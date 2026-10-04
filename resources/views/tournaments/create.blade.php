<x-ui.layout title="Criar torneio">
    <div class="form-page">
        <a href="{{ route('tournaments.index') }}" class="back-link">← Meus torneios</a>
        <div class="page-heading">
            <div>
                <p class="eyebrow">Dê o primeiro passo</p>
                <h1 class="page-title">Criar torneio</h1>
                <p class="page-description">O essencial para tirar o seu próximo encontro do papel.</p>
            </div>
        </div>
        <x-ui.feedback :validation="false" />
        <form action="{{ route('tournaments.store') }}" method="POST" class="panel form-stack">
            @csrf
            <x-ui.field name="name" label="Nome do torneio">
                <input class="form-input" type="text" id="name" name="name" value="{{ old('name') }}" maxlength="255" placeholder="Ex.: Copa da Areia" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @if ($errors->has('name')) aria-describedby="name-error" @endif required>
            </x-ui.field>
            <div class="form-row">
                <x-ui.field name="sport" label="Esporte">
                    <select class="form-input" id="sport" name="sport" aria-invalid="{{ $errors->has('sport') ? 'true' : 'false' }}" @if ($errors->has('sport')) aria-describedby="sport-error" @endif required>
                        <option value="">Selecione um esporte</option>
                        @foreach (['Vôlei', 'Futevôlei', 'Beach Tennis', 'Futebol', 'Futsal'] as $sport)
                            <option value="{{ $sport }}" @selected(old('sport') === $sport)>{{ $sport }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <x-ui.field name="event_date" label="Data do torneio">
                    <input class="form-input" type="date" id="event_date" name="event_date" value="{{ old('event_date') }}" aria-invalid="{{ $errors->has('event_date') ? 'true' : 'false' }}" @if ($errors->has('event_date')) aria-describedby="event_date-error" @endif required>
                </x-ui.field>
            </div>
            <x-ui.field name="location" label="Local">
                <input class="form-input" type="text" id="location" name="location" value="{{ old('location') }}" maxlength="255" placeholder="Onde o jogo vai acontecer?" aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" @if ($errors->has('location')) aria-describedby="location-error" @endif required>
            </x-ui.field>
            <x-ui.field name="registration_fee" label="Valor da inscrição (R$)" hint="Para um torneio gratuito, deixe o valor em zero.">
                <input class="form-input" type="number" id="registration_fee" name="registration_fee" min="0" max="99999999.99" step="0.01" value="{{ old('registration_fee', '0') }}" aria-invalid="{{ $errors->has('registration_fee') ? 'true' : 'false' }}" aria-describedby="registration_fee-hint{{ $errors->has('registration_fee') ? ' registration_fee-error' : '' }}" required>
            </x-ui.field>
            <div class="form-actions">
                <x-ui.button type="submit">Criar torneio <span aria-hidden="true">↗</span></x-ui.button>
                <x-ui.button :href="route('tournaments.index')" variant="subtle">Cancelar</x-ui.button>
            </div>
        </form>
        <p class="form-note"><span class="form-note-number">01 →</span><span>Depois de criar, você poderá configurar o Pix e compartilhar a página pública.</span></p>
    </div>
</x-ui.layout>
