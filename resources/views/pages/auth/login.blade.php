<x-layouts::auth title="Entrar">
    <div class="flex flex-col gap-7">
        <div><p class="eyebrow mb-4">Seu próximo encontro</p><x-auth-header title="Bom ter você de volta." description="Entre na sua conta e continue de onde parou." /></div>
        <x-auth-session-status :status="session('status')" />
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf
            <flux:input name="email" label="E-mail" :value="old('email')" type="email" required autofocus autocomplete="email" placeholder="voce@exemplo.com" />
            <flux:input name="password" label="Senha" type="password" required autocomplete="current-password" placeholder="Sua senha" viewable />
            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <flux:checkbox name="remember" label="Lembrar de mim" :checked="old('remember')" />
                @if (Route::has('password.request'))
                    <flux:link :href="route('password.request')" wire:navigate>Esqueceu a senha?</flux:link>
                @endif
            </div>
            <flux:button variant="primary" type="submit" class="w-full button button--primary" data-test="login-button">Entrar na conta <span aria-hidden="true">↗</span></flux:button>
        </form>
        <p class="form-hint text-center">Ainda não tem conta? <flux:link :href="route('register')" wire:navigate>Criar conta</flux:link></p>
    </div>
</x-layouts::auth>
