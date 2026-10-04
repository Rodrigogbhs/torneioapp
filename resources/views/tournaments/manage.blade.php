<x-ui.layout :title="'Gerenciar '.$tournament->name">
    <a href="{{ route('tournaments.index') }}" class="back-link">← Voltar aos meus torneios</a>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Gerenciar torneio</p>
            <h1 class="page-title">{{ $tournament->name }}</h1>
            <div class="info-line"><span>{{ $tournament->sport }}</span><span><x-ui.date :value="$tournament->event_date" /></span><span>{{ $tournament->location }}</span></div>
        </div>
        <x-ui.button :href="route('tournaments.show', $tournament)" variant="filled">Ver página pública ↗</x-ui.button>
    </div>
    <x-ui.feedback />
    <section class="admin-participants" aria-labelledby="participants-title">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="section-title" id="participants-title">Participantes inscritos <span class="text-muted text-sm font-normal">/ {{ $tournament->registrations->count() }}</span></h2><p class="form-hint mt-2">Confirme os pagamentos recebidos e acompanhe cada inscrição.</p></div>
            <a href="{{ route('tournaments.show', $tournament) }}" class="text-link">Inscrever outro participante neste torneio ↗</a>
        </div>
        @if ($tournament->registrations->isEmpty())
            <div class="empty-state mt-6"><span class="empty-symbol" aria-hidden="true">↗</span><h2>Nenhum participante inscrito neste torneio.</h2><p>Compartilhe a página pública para convidar os primeiros participantes.</p></div>
        @else
            <div class="participant-table-head mt-5" aria-hidden="true"><span>Participante</span><span>Pagamento</span><span class="participant-date">Inscrição</span><span>Ações</span></div>
            @foreach ($tournament->registrations as $registration)
                <article class="participant-row">
                    <h3 class="participant-name">{{ $registration->athlete?->name ?? 'Participante não informado' }}</h3>
                    <div><x-ui.payment-status :status="$registration->payment_status" :free="(float) $tournament->registration_fee === 0.0" context="manage" /></div>
                    <div class="participant-date">@if ($registration->created_at)<time datetime="{{ $registration->created_at->toIso8601String() }}">Inscrito em {{ $registration->created_at->format('d/m/Y H:i') }}</time>@endif</div>
                    <div class="row-actions">
                        @if ((float) $tournament->registration_fee > 0 && $registration->payment_status === 'pending')
                            @if ($registration->user_id !== auth()->id())
                                <form action="{{ route('tournaments.registrations.confirm-payment', [$tournament, $registration]) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="filled" class="button-small">Confirmar pagamento</x-ui.button>
                                </form>
                            @else
                                <p class="form-hint max-w-40">A conta responsável não pode confirmar o próprio pagamento.</p>
                            @endif
                        @endif
                        <form action="{{ route('tournaments.registrations.destroy', ['tournament' => $tournament->id, 'registration' => $registration->id]) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover esta inscrição?');">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="subtle" class="button-small button-danger">Remover inscrição</x-ui.button>
                        </form>
                    </div>
                </article>
            @endforeach
        @endif
        <p class="form-hint mt-6 max-w-2xl">Cadastrar um participante na conta não conclui sua inscrição neste torneio. Para adicioná-lo à lista, abra a página de inscrição, selecione o nome cadastrado e clique em "Inscrever-se".</p>
    </section>
    <div class="admin-bottom">
        <section class="panel" aria-labelledby="pix-config-title">
            <p class="eyebrow mb-3">Pagamento Pix</p>
            <h2 class="section-title" id="pix-config-title">Configurar Pix</h2>
            <p class="form-hint mt-3 mb-6">{{ $tournament->pix_key ? 'Chave Pix configurada. Para alterar, informe a nova chave abaixo.' : 'Este torneio ainda não possui chave Pix.' }}</p>
            <form action="{{ route('tournaments.pix-key.update', $tournament) }}" method="POST" class="form-stack">
                @csrf
                @method('PATCH')
                <x-ui.field name="pix_key" label="Chave Pix" hint="A chave existente fica protegida e não é exibida aqui.">
                    <input class="form-input" type="password" id="pix_key" name="pix_key" maxlength="255" placeholder="Informe a chave Pix" autocomplete="off" aria-invalid="{{ $errors->has('pix_key') ? 'true' : 'false' }}" aria-describedby="pix_key-hint{{ $errors->has('pix_key') ? ' pix_key-error' : '' }}" required>
                </x-ui.field>
                <x-ui.button type="submit" variant="filled">Salvar chave Pix</x-ui.button>
            </form>
        </section>
        <section class="soft-panel" aria-labelledby="share-title" x-data="{
            copied: false,
            copyError: false,
            async copyLink() {
                this.copied = false;
                this.copyError = false;
                try {
                    await navigator.clipboard.writeText(this.$refs.publicLink.value);
                    this.copied = true;
                } catch {
                    this.copyError = true;
                }
            }
        }">
            <p class="eyebrow mb-3">Convide para o jogo</p>
            <h2 class="section-title" id="share-title">Compartilhe o encontro.</h2>
            <p class="page-description">Uma página pública, pronta para receber os seus participantes.</p>
            <label for="public-link" class="form-label block mt-6">Link público para compartilhar</label>
            <div class="share-field">
                <input type="text" id="public-link" class="form-input" x-ref="publicLink" x-on:focus="$el.select()" value="{{ route('tournaments.show', $tournament) }}" readonly>
                <x-ui.button variant="filled" aria-label="Copiar link público" x-on:click="copyLink()"><span x-text="copied ? 'Copiado!' : 'Copiar'" aria-live="polite">Copiar</span></x-ui.button>
            </div>
            <p x-show="copyError" x-cloak class="form-hint mt-3" role="status">Não foi possível copiar automaticamente. Selecione e copie o link acima.</p>
            <p class="mt-6"><a href="{{ route('tournaments.show', $tournament) }}" class="text-link">Abrir link público ↗</a></p>
        </section>
    </div>
</x-ui.layout>
