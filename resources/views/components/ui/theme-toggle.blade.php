<button type="button" class="theme-toggle" x-data x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'" x-bind:aria-label="$flux.dark ? 'Ativar tema claro' : 'Ativar tema escuro'" aria-label="Ativar tema escuro" title="Alternar tema">
    <svg class="dark:hidden" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M20.5 14A8.5 8.5 0 0 1 10 3.5 8.5 8.5 0 1 0 20.5 14Z" stroke-linecap="round" stroke-linejoin="round" /></svg>
    <svg class="hidden dark:block" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="4" /><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5" stroke-linecap="round" /></svg>
</button>
