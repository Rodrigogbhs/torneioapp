<?php

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/** @param array<string, mixed> $attributes */
function mvpTournament(User $owner, array $attributes = []): Tournament
{
    return $owner->tournaments()->create(array_merge([
        'name' => 'Copa MVP',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena MVP',
        'registration_fee' => 25,
    ], $attributes));
}

it('rejects excessive decimal places and scientific notation without saving a tournament', function (string $fee) {
    $owner = User::factory()->create();

    $this->actingAs($owner)->from(route('tournaments.create'))->post(route('tournaments.store'), [
        'name' => 'Copa inválida', 'sport' => 'Vôlei', 'event_date' => '2026-10-10',
        'location' => 'Arena', 'registration_fee' => $fee,
    ])->assertRedirect(route('tournaments.create'))->assertSessionHasErrors('registration_fee');

    $this->assertDatabaseCount('tournaments', 0);
})->with(['three decimals' => '12.345', 'fraction of a cent' => '0.001', 'rounded overflow' => '99999999.999', 'exponent' => '1e2']);

it('accepts fees within numeric ten two boundaries', function (string $fee) {
    $owner = User::factory()->create();

    $this->actingAs($owner)->post(route('tournaments.store'), [
        'name' => 'Copa válida', 'sport' => 'Vôlei', 'event_date' => '2026-10-10',
        'location' => 'Arena', 'registration_fee' => $fee,
    ])->assertRedirect(route('tournaments.index'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('tournaments', ['user_id' => $owner->id, 'registration_fee' => $fee]);
})->with(['zero' => '0', 'one cent' => '0.01', 'one decimal' => '12.3', 'maximum' => '99999999.99']);

it('hides past tournaments and includes today and future tournaments in date order', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
    $owner = User::factory()->create();
    mvpTournament($owner, ['name' => 'Copa passada', 'event_date' => '2026-10-03']);
    mvpTournament($owner, ['name' => 'Copa futura', 'event_date' => '2026-10-05']);
    mvpTournament($owner, ['name' => 'Copa hoje', 'event_date' => '2026-10-04']);

    $this->get(route('tournaments.available'))->assertSeeInOrder(['Copa hoje', 'Copa futura'])
        ->assertDontSee('Copa passada');
});

it('automatically confirms a free registration without showing Pix or pending payment', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner, ['registration_fee' => 0, 'pix_key' => 'pix-ficticio-gratis']);
    $athlete = $account->athletes()->create(['name' => 'Participante gratuito']);

    $response = $this->actingAs($account)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id, 'athlete_id' => $athlete->id,
        'payment_status' => 'pending', 'payment_confirmed_at' => null,
    ]);

    $registration = $account->registrations()->sole();
    $response->assertRedirect(route('registrations.show', $registration));
    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id, 'payment_status' => 'confirmed', 'payment_confirmed_at' => '2026-10-04 12:00:00',
    ]);
    $this->get(route('registrations.show', $registration))->assertSee('Inscrição gratuita')
        ->assertDontSee('Pagamento Pix')->assertDontSee('aguardando confirmação')->assertDontSee('pix-ficticio-gratis');
});

it('presents an existing free pending registration as free on participant and management pages', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner, ['registration_fee' => 0, 'pix_key' => 'pix-ficticio-legado']);
    $athlete = $account->athletes()->create(['name' => 'Participante gratuito antigo']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($account)->get(route('registrations.show', $registration))->assertSee('Inscrição gratuita')
        ->assertDontSee('Pagamento Pix')->assertDontSee('aguardando confirmação')->assertDontSee('pix-ficticio-legado');
    $this->get(route('registrations.index'))->assertSee('Inscrição gratuita')->assertDontSee('aguardando confirmação');
    $this->actingAs($owner)->get(route('tournaments.manage', $tournament))->assertSee('Inscrição gratuita')
        ->assertDontSee('Confirmar pagamento')->assertDontSee('Pagamento aguardando');
});

it('lets the tournament owner confirm payment and removes payment instructions from the registration', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner, ['pix_key' => 'pix-ficticio-confirmacao']);
    $athlete = $account->athletes()->create(['name' => 'Participante pagante']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($owner)->patch(route('tournaments.registrations.confirm-payment', [$tournament, $registration]), [
        'payment_status' => 'pending', 'payment_confirmed_at' => '2000-01-01',
    ])->assertRedirect(route('tournaments.manage', $tournament))->assertSessionHas('success', 'Pagamento confirmado com sucesso!');

    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id, 'payment_status' => 'confirmed', 'payment_confirmed_at' => '2026-10-04 12:00:00',
    ]);
    $this->assertModelExists($athlete);
    $this->actingAs($account)->get(route('registrations.show', $registration))->assertSee('Pagamento: confirmado')
        ->assertDontSee('Pagamento Pix')->assertDontSee('pix-ficticio-confirmacao')->assertDontSee('aguardando confirmação');
    $this->actingAs($owner)->get(route('tournaments.manage', $tournament))->assertSee('Pagamento confirmado')
        ->assertDontSee('Confirmar pagamento');
});

it('keeps the original payment confirmation date on repeated confirmation', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $registration->payment_status = 'confirmed';
    $registration->payment_confirmed_at = '2026-10-01 09:30:00';
    $registration->save();

    $this->actingAs($owner)->patch(route('tournaments.registrations.confirm-payment', [$tournament, $registration]))
        ->assertRedirect(route('tournaments.manage', $tournament));

    expect($registration->fresh()->payment_confirmed_at->format('Y-m-d H:i:s'))->toBe('2026-10-01 09:30:00');
});

it('refuses payment confirmation from a participant or another organizer', function (bool $isParticipant) {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $unauthorized = $isParticipant ? $account : User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($unauthorized)->patch(route('tournaments.registrations.confirm-payment', [$tournament, $registration]))
        ->assertNotFound();

    $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'payment_status' => 'pending', 'payment_confirmed_at' => null]);
})->with(['participant' => true, 'other organizer' => false]);

it('refuses an organizers own payment and hides its confirmation button', function () {
    $owner = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $owner->athletes()->create(['name' => 'Organizador inscrito']);
    $registration = $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($owner)->patch(route('tournaments.registrations.confirm-payment', [$tournament, $registration]))->assertForbidden();

    $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'payment_status' => 'pending', 'payment_confirmed_at' => null]);
    $this->get(route('tournaments.manage', $tournament))->assertDontSee('Confirmar pagamento')
        ->assertSee('não pode confirmar o próprio pagamento');
});

it('refuses payment confirmation for a registration from another tournament', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $otherTournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create(['tournament_id' => $otherTournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($owner)->patch(route('tournaments.registrations.confirm-payment', [$tournament, $registration]))->assertNotFound();

    $this->assertDatabaseHas('registrations', ['id' => $registration->id, 'payment_status' => 'pending', 'payment_confirmed_at' => null]);
});

it('lists only the accounts registrations with their participants tournaments sport status and detail links', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner, ['pix_key' => 'pix-ficticio-lista']);
    $otherTournament = mvpTournament($owner, ['name' => 'Torneio de outra conta']);
    $athlete = $account->athletes()->create(['name' => 'Participante próprio']);
    $otherAthlete = $owner->athletes()->create(['name' => 'Participante privado']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $otherRegistration = $owner->registrations()->create(['tournament_id' => $otherTournament->id, 'athlete_id' => $otherAthlete->id]);

    $this->actingAs($account)->get(route('registrations.index'))->assertSee('Participante próprio')->assertSee('Copa MVP')
        ->assertSee('Vôlei')->assertSee('Pagamento: aguardando confirmação')->assertSee('Ver inscrição')
        ->assertSee(route('registrations.show', $registration))->assertDontSee('Participante privado')
        ->assertDontSee('Torneio de outra conta')->assertDontSee(route('registrations.show', $otherRegistration))
        ->assertDontSee('pix-ficticio-lista');
});

it('shows an empty registration list and a link to available tournaments', function () {
    $this->actingAs(User::factory()->create())->get(route('registrations.index'))
        ->assertSee('Você ainda não possui inscrições.')->assertSee(route('tournaments.available'));
});

it('shows confirmed registrations in my registrations without Pix', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner, ['pix_key' => 'pix-ficticio-confirmado']);
    $athlete = $account->athletes()->create(['name' => 'Participante confirmado']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $registration->payment_status = 'confirmed';
    $registration->save();

    $this->actingAs($account)->get(route('registrations.index'))->assertSee('Participante confirmado')
        ->assertSee('Pagamento: confirmado')->assertDontSee('aguardando confirmação')->assertDontSee('pix-ficticio-confirmado');
});

it('shows registered participants registration dates and payment actions without unregistered account athletes', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante inscrito']);
    $account->athletes()->create(['name' => 'Participante sem inscrição']);
    $owner->athletes()->create(['name' => 'Participante apenas cadastrado']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($owner)->get(route('tournaments.manage', $tournament))->assertSee('Participante inscrito')
        ->assertSee('Inscrito em 04/10/2026 12:00')->assertSee('Pagamento aguardando confirmação')
        ->assertSee(route('tournaments.registrations.confirm-payment', [$tournament, $registration]))
        ->assertSee(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertDontSee('Participante sem inscrição')->assertDontSee('Participante apenas cadastrado');
});

it('prioritizes tournament creation and links my registrations in the dashboard', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertSeeInOrder(['Criar torneio', 'Torneios disponíveis', 'Meus torneios', 'Minhas inscrições'])
        ->assertSee(route('registrations.index'));
});

it('blocks account deletion for own registrations while keeping the account signed in', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante ativo']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $this->actingAs($account);

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')
        ->assertHasErrors('deletion')->assertSee('Sua conta possui inscrições ou torneios com inscritos.')->assertNoRedirect();

    $this->assertAuthenticatedAs($account);
    $this->assertModelExists($account);
    $this->assertModelExists($athlete);
    $this->assertModelExists($registration);
    $this->assertModelExists($tournament);
});

it('blocks deleting an organizer with registered participants and preserves other accounts data', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante de outra conta']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $this->actingAs($owner);

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')
        ->assertHasErrors('deletion')->assertNoRedirect();

    $this->assertAuthenticatedAs($owner);
    $this->assertModelExists($owner);
    $this->assertModelExists($account);
    $this->assertModelExists($tournament);
    $this->assertModelExists($athlete);
    $this->assertModelExists($registration);
});

it('deletes an account with only unregistered athletes and empty tournaments before logging out', function () {
    $owner = User::factory()->create();
    $otherAccount = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $owner->athletes()->create(['name' => 'Participante sem inscrição']);
    $otherTournament = mvpTournament($otherAccount);
    $otherAthlete = $otherAccount->athletes()->create(['name' => 'Participante preservado']);
    $this->actingAs($owner);

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')
        ->assertHasNoErrors()->assertRedirect('/');

    $this->assertGuest();
    $this->assertModelMissing($owner);
    $this->assertModelMissing($tournament);
    $this->assertModelMissing($athlete);
    $this->assertModelExists($otherAccount);
    $this->assertModelExists($otherTournament);
    $this->assertModelExists($otherAthlete);
});

it('blocks deleting an account whose athlete has a legacy registration under another account', function () {
    $owner = User::factory()->create();
    $account = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $account->athletes()->create(['name' => 'Participante com vínculo legado']);
    $registration = $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $this->actingAs($account);

    Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')
        ->assertHasErrors('deletion')->assertNoRedirect();

    $this->assertAuthenticatedAs($account);
    $this->assertModelExists($athlete);
    $this->assertModelExists($registration);
});

it('sends the Fortify verification notification on registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Conta MVP', 'email' => 'conta-mvp@example.test',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'conta-mvp@example.test')->sole();
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeFalse();
});

it('rolls back account cleanup and preserves authentication if an unexpected foreign key blocks deletion', function () {
    $owner = User::factory()->create();
    $tournament = mvpTournament($owner);
    $athlete = $owner->athletes()->create(['name' => 'Participante preservado na falha']);
    Schema::create('account_deletion_guards', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->constrained();
    });
    DB::table('account_deletion_guards')->insert(['user_id' => $owner->id]);
    $this->actingAs($owner);

    expect(fn () => Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')->call('deleteUser'))->toThrow(QueryException::class);

    $this->assertAuthenticatedAs($owner);
    $this->assertModelExists($owner);
    $this->assertModelExists($tournament);
    $this->assertModelExists($athlete);
});
