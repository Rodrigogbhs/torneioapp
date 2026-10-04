<?php

use App\Models\User;

beforeEach(function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
});

it('lists public tournaments with links to their registration pages and no administration controls or Pix key', function () {
    $organizer = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa de Vôlei',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena Central',
        'registration_fee' => 25,
        'pix_key' => 'chave-secreta',
    ]);

    $this->get(route('tournaments.available'))
        ->assertSee('Copa de Vôlei')
        ->assertSee(route('tournaments.show', $tournament))
        ->assertSee('Inscrever-se')
        ->assertDontSee('Configurar Pix')
        ->assertDontSee('Excluir torneio')
        ->assertDontSee('chave-secreta');

    $this->get(route('tournaments.show', $tournament))
        ->assertSee('Copa de Vôlei')
        ->assertDontSee('chave-secreta');
});

it('keeps my tournaments scoped to the organizer and protects Pix management', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = $owner->tournaments()->create([
        'name' => 'Copa Particular',
        'sport' => 'Futsal',
        'event_date' => '2026-10-10',
        'location' => 'Ginásio',
        'registration_fee' => 20,
    ]);

    $this->actingAs($otherUser)->get(route('tournaments.index'))
        ->assertDontSee('Copa Particular');

    $this->get(route('tournaments.manage', $tournament))->assertNotFound();

    $this->actingAs($owner)->get(route('tournaments.index'))
        ->assertSee('Copa Particular')
        ->assertSee(route('tournaments.manage', $tournament))
        ->assertSee('Excluir torneio')
        ->assertDontSee('Configurar Pix');

    $this->get(route('tournaments.manage', $tournament))
        ->assertSee('Configurar Pix');
});

it('lets an account create a participant and select that participant on the tournament page', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa de Teste',
        'sport' => 'Futsal',
        'event_date' => '2026-10-10',
        'location' => 'Ginásio',
        'registration_fee' => 20,
    ]);

    $this->actingAs($account)->get(route('tournaments.show', $tournament))
        ->assertSee('Cadastrar participante')
        ->assertDontSee('name="athlete_id"', false);

    $this->get(route('athletes.create', ['tournament' => $tournament->id]))
        ->assertSee('Nome do participante');

    $this->post(route('athletes.store'), [
        'name' => 'Participante de Teste',
        'tournament_id' => $tournament->id,
    ])->assertRedirect(route('tournaments.show', $tournament));

    $this->assertDatabaseHas('athletes', [
        'user_id' => $account->id,
        'name' => 'Participante de Teste',
    ]);

    $this->get(route('tournaments.show', $tournament))
        ->assertSee('Participante de Teste')
        ->assertSee('name="athlete_id"', false);
});

it('shows an existing registration without duplicating the participant and displays the Pix key only while payment is pending', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $athlete = $account->athletes()->create(['name' => 'Participante de Teste']);
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Pix',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
        'pix_key' => 'chave-pix-teste',
    ]);
    $registration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($account)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ])->assertRedirect(route('registrations.show', $registration))
        ->assertSessionHas('success', 'Este participante já está inscrito neste torneio.');

    expect($account->registrations()->count())->toBe(1);

    $this->get(route('registrations.show', $registration))
        ->assertSee('Participante de Teste')
        ->assertSee('Copa Pix')
        ->assertSee('25,00')
        ->assertSee('Pagamento Pix')
        ->assertSee('Chave Pix: chave-pix-teste');

    $registration->payment_status = 'confirmed';
    $registration->save();

    $this->get(route('registrations.show', $registration))
        ->assertSee('Pagamento: confirmado')
        ->assertDontSee('chave-pix-teste')
        ->assertDontSee('Pagamento Pix');
});

it('limits new registrations to four owned participants per tournament without blocking existing registrations', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Limite',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);

    foreach (range(1, 4) as $number) {
        $athlete = $account->athletes()->create(['name' => 'Atleta '.$number]);
        $registration = $account->registrations()->create([
            'tournament_id' => $tournament->id,
            'athlete_id' => $athlete->id,
        ]);

        if ($number === 1) {
            $firstAthlete = $athlete;
            $firstRegistration = $registration;
        }
    }

    $fifthAthlete = $account->athletes()->create(['name' => 'Atleta 5']);

    $this->actingAs($account)->from(route('tournaments.show', $tournament))
        ->post(route('registrations.store'), [
            'tournament_id' => $tournament->id,
            'athlete_id' => $fifthAthlete->id,
        ])->assertRedirect(route('tournaments.show', $tournament))
        ->assertSessionHas('error');

    expect($account->registrations()->count())->toBe(4);

    $this->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $firstAthlete->id,
    ])->assertRedirect(route('registrations.show', $firstRegistration));

    $this->get(route('registrations.show', $firstRegistration))
        ->assertSee('Atleta 1');
});

it('explains when a pending registration has no Pix key', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $athlete = $account->athletes()->create(['name' => 'Atleta Sem Chave']);
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Sem Chave',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $registration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($account)->get(route('registrations.show', $registration))
        ->assertSee('Pagamento Pix')
        ->assertSee('O organizador ainda não cadastrou uma chave Pix');
});

it('rejects another accounts participant and keeps registrations private', function () {
    $organizer = User::factory()->create();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $athlete = $owner->athletes()->create(['name' => 'Atleta Privado']);
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Privada',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $registration = $owner->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($otherUser)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ])->assertNotFound();

    $this->get(route('registrations.show', $registration))->assertNotFound();
    expect($otherUser->registrations()->count())->toBe(0);
});
