<x-layouts::auth title="Criar conta">
    <div class="flex flex-col gap-7">
        <div><p class="eyebrow mb-4">O primeiro passo é seu</p><x-auth-header title="Entre para o jogo." description="Uma conta para organizar encontros e inscrever seus participantes." /></div>
        <x-auth-session-status :status="session('status')" />
        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5">
            @csrf
            <flux:input name="name" label="Seu nome" :value="old('name')" type="text" required autofocus autocomplete="name" placeholder="Nome completo" />
            <flux:input name="email" label="E-mail" :value="old('email')" type="email" required autocomplete="email" placeholder="voce@exemplo.com" />
            <flux:input name="password" label="Senha" type="password" required autocomplete="new-password" placeholder="Crie uma senha" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <flux:input name="password_confirmation" label="Confirmar senha" type="password" required autocomplete="new-password" placeholder="Repita a senha" passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" viewable />
            <flux:button variant="primary" type="submit" class="w-full button button--primary" data-test="register-user-button">Criar conta <span aria-hidden="true">↗</span></flux:button>
        </form>
        <p class="form-hint text-center">Já tem uma conta? <flux:link :href="route('login')" wire:navigate>Entrar</flux:link></p>
    </div>
</x-layouts::auth>
