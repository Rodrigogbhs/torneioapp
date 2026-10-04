<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Models\Registration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        DB::transaction(function (): void {
            $user = Auth::user()->newQuery()->lockForUpdate()->findOrFail(Auth::id());
            $tournaments = $user->tournaments()->lockForUpdate()->get();

            if ($user->registrations()->exists()
                || Registration::whereIn('athlete_id', $user->athletes()->select('id'))->exists()
                || $tournaments->contains(fn ($tournament) => $tournament->registrations()->exists())) {
                throw ValidationException::withMessages([
                    'deletion' => 'Sua conta possui inscrições ou torneios com inscritos. Peça ao organizador para remover suas inscrições e remova as inscrições dos seus torneios antes de excluir a conta.',
                ]);
            }

            $user->athletes()->delete();
            $user->tournaments()->delete();
            $user->delete();
        }, attempts: 3);

        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form method="POST" wire:submit="deleteUser" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure you want to delete your account?') }}</flux:heading>

            <flux:subheading>
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </flux:subheading>
        </div>

        <flux:input wire:model="password" :label="__('Password')" type="password" viewable />

        <flux:error name="deletion" />

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button variant="danger" type="submit" data-test="confirm-delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
