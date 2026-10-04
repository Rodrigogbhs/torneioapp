<x-ui.layout title="Minha inscrição">
    <a href="{{ route('registrations.index') }}" class="back-link">← Voltar às minhas inscrições</a>
    <div class="page-heading">
        <div><p class="eyebrow">Você está no jogo</p><h1 class="page-title">Minha inscrição</h1><p class="page-description">As informações do seu participante e a confirmação do encontro.</p></div>
        <x-ui.payment-status :status="$registration->payment_status" :free="(float) $registration->tournament->registration_fee === 0.0" />
    </div>
    <x-ui.feedback />
    <div class="content-grid">
        <section class="panel" aria-labelledby="registration-info-title">
            <p class="eyebrow mb-5" id="registration-info-title">Informações da inscrição</p>
            <span class="sport-tag">{{ $registration->tournament->sport }}</span>
            <h2 class="section-title mt-5 mb-8">{{ $registration->tournament->name }}</h2>
            <dl class="definition-list">
                <div><dt>Participante</dt><dd>{{ $registration->athlete?->name ?? 'Participante não informado' }}</dd></div>
                <div><dt>Esporte</dt><dd>{{ $registration->tournament->sport }}</dd></div>
                <div><dt>Data</dt><dd><x-ui.date :value="$registration->tournament->event_date" /></dd></div>
                <div><dt>Local</dt><dd>{{ $registration->tournament->location }}</dd></div>
                <div><dt>Valor da inscrição</dt><dd>R$ {{ number_format($registration->tournament->registration_fee, 2, ',', '.') }}</dd></div>
            </dl>
            <p class="mt-8"><a href="{{ route('tournaments.show', $registration->tournament->id) }}" class="text-link">Voltar ao torneio ↗</a></p>
        </section>
        <section class="panel payment-state" aria-labelledby="payment-info-title">
            <p class="eyebrow" id="payment-info-title">Pagamento</p>
            @if ((float) $registration->tournament->registration_fee === 0.0)
                <span class="payment-check" aria-hidden="true">✓</span>
                <h2 class="section-title">Inscrição gratuita</h2>
                <p class="page-description">Nenhum pagamento é necessário. Sua inscrição já está confirmada.</p>
            @elseif ($registration->payment_status === 'confirmed')
                <span class="payment-check" aria-hidden="true">✓</span>
                <h2 class="section-title">Pagamento: confirmado</h2>
                <p class="page-description">Tudo certo com o pagamento. Agora é se preparar para o encontro.</p>
                @if ($registration->payment_confirmed_at)
                    <p class="form-hint">Confirmado em {{ $registration->payment_confirmed_at->format('d/m/Y H:i') }}.</p>
                @endif
            @elseif ($registration->payment_status === 'pending')
                <x-ui.payment-status status="pending" />
                <h2 class="section-title">Pagamento Pix</h2>
                <p class="form-hint">Pagamento: aguardando confirmação</p>
                @if ($registration->tournament->pix_key)
                    <p class="page-description">Use a chave Pix abaixo para pagar a inscrição ao organizador. A confirmação do pagamento é manual.</p>
                    <p class="pix-key">Chave Pix: {{ $registration->tournament->pix_key }}</p>
                    <p class="form-hint">O status será atualizado aqui depois da confirmação pelo organizador.</p>
                @else
                    <div class="feedback feedback--warning">O organizador ainda não cadastrou uma chave Pix para este torneio.</div>
                @endif
            @else
                <h2 class="section-title">Pagamento: status indisponível</h2>
            @endif
        </section>
    </div>
</x-ui.layout>
