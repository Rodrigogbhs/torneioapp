<?php

use App\Models\User;

it('asks for an explicit participant choice instead of defaulting to an existing registration', function () {
    $account = User::factory()->create();
    $tournament = $account->tournaments()->create([
        'name' => 'Copa de Escolha',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $ingrid = $account->athletes()->create(['name' => 'Ingrid']);
    $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $ingrid->id,
    ]);
    $account->athletes()->create(['name' => 'Rodrigo']);

    $page = $this->actingAs($account)->get(route('tournaments.show', $tournament));
    $document = new DOMDocument;
    $document->loadHTML($page->getContent());
    $fields = new DOMXPath($document);
    $selectedOption = $fields->query('//select[@name="athlete_id"]/option[@selected]')->item(0)
        ?? $fields->query('//select[@name="athlete_id"]/option')->item(0);

    expect($selectedOption->getAttribute('value'))->toBe('');
    $page->assertSee('Selecione um participante');
    $this->assertDatabaseCount('registrations', 1);
});

it('returns a newly created participant to the tournament selected for explicit registration', function () {
    $owner = User::factory()->create();
    $tournament = $owner->tournaments()->create([
        'name' => 'Copa de Participantes',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $ingrid = $owner->athletes()->create(['name' => 'Ingrid']);
    $owner->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $ingrid->id,
    ]);
    $unregistered = $owner->athletes()->create(['name' => 'Sem inscrição']);

    $this->actingAs($owner)->get(route('tournaments.show', $tournament))
        ->assertSee(route('athletes.create', ['tournament' => $tournament->id]));

    $createPage = $this->get(route('athletes.create', ['tournament' => $tournament->id]));
    $createPage->assertSee('Nome do participante');
    $createDocument = new DOMDocument;
    $createDocument->loadHTML($createPage->getContent());
    $createFields = new DOMXPath($createDocument);
    expect($createFields->evaluate('string(//input[@name="tournament_id"]/@value)'))
        ->toBe((string) $tournament->id);

    $this->post(route('athletes.store'), [
        'name' => 'Rodrigo',
        'tournament_id' => $tournament->id,
    ])->assertRedirect(route('tournaments.show', $tournament));

    $secondAthlete = $owner->athletes()->where('name', 'Rodrigo')->sole();
    $this->assertDatabaseCount('registrations', 1);
    $this->assertDatabaseMissing('registrations', [
        'tournament_id' => $tournament->id,
        'athlete_id' => $secondAthlete->id,
    ]);

    $tournamentPage = $this->get(route('tournaments.show', $tournament));
    $document = new DOMDocument;
    $document->loadHTML($tournamentPage->getContent());
    $fields = new DOMXPath($document);
    $selectedAthleteId = $fields->evaluate('string(//select[@name="athlete_id"]/option[@selected]/@value)');

    expect($selectedAthleteId)->toBe((string) $secondAthlete->id);
    $tournamentPage->assertSee('Participante cadastrado com sucesso!')
        ->assertSee('Para concluir a inscrição neste torneio, clique em "Inscrever-se".');

    $this->get(route('tournaments.manage', $tournament))
        ->assertOk()
        ->assertSee('Ingrid')
        ->assertDontSee('Rodrigo')
        ->assertDontSee($unregistered->name);

    $this->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $selectedAthleteId,
    ])->assertRedirect(route('registrations.show', $owner->registrations()->where('athlete_id', $secondAthlete->id)->sole()));

    $registration = $owner->registrations()->where('athlete_id', $secondAthlete->id)->sole();
    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id,
        'tournament_id' => $tournament->id,
        'athlete_id' => $secondAthlete->id,
        'user_id' => $owner->id,
        'payment_status' => 'pending',
    ]);
    $this->assertDatabaseCount('registrations', 2);
    $this->get(route('registrations.show', $registration))
        ->assertSee('Minha inscrição')
        ->assertSee('Rodrigo');

    $this->get(route('tournaments.manage', $tournament))
        ->assertSee('Ingrid')
        ->assertSee('Rodrigo')
        ->assertSee('Pagamento aguardando confirmação')
        ->assertSee('Remover inscrição')
        ->assertDontSee($unregistered->name);
});

it('removes only the registration and lets the same participant register again', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa de Reinscrição',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $ingrid = $account->athletes()->create(['name' => 'Ingrid']);
    $firstRegistration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $ingrid->id,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Rodrigo']);
    $registration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($organizer)->delete(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertRedirect(route('tournaments.manage', $tournament))
        ->assertSessionHas('success', 'Inscrição removida com sucesso!');

    $this->assertModelMissing($registration);
    $this->assertModelExists($athlete);
    $this->assertModelExists($firstRegistration);
    $this->get(route('tournaments.manage', $tournament))
        ->assertSee('Ingrid')
        ->assertDontSee('Rodrigo');

    $response = $this->actingAs($account)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $newRegistration = $account->registrations()->where('athlete_id', $athlete->id)->sole();
    $response->assertRedirect(route('registrations.show', $newRegistration));
    $this->assertDatabaseCount('registrations', 2);
    $this->assertDatabaseHas('registrations', [
        'id' => $newRegistration->id,
        'athlete_id' => $athlete->id,
        'user_id' => $account->id,
        'tournament_id' => $tournament->id,
    ]);
    $this->actingAs($organizer)->get(route('tournaments.manage', $tournament))
        ->assertSee('Ingrid')
        ->assertSee('Rodrigo');
});

it('refuses registration removal by an account that does not own the tournament', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Protegida',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($account)->delete(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertNotFound();

    $this->assertModelExists($registration);
    $this->assertModelExists($athlete);
});

it('keeps management and registration removal scoped to the selected tournament', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa Selecionada',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $otherTournament = $organizer->tournaments()->create([
        'name' => 'Outra Copa',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-11',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Participante de Outro Torneio']);
    $registration = $account->registrations()->create([
        'tournament_id' => $otherTournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($organizer)->get(route('tournaments.manage', $tournament))
        ->assertSee('Nenhum participante inscrito neste torneio.')
        ->assertDontSee($athlete->name);

    $this->delete(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertNotFound();

    $this->assertModelExists($registration);
    $this->assertModelExists($athlete);
});

it('blocks tournament deletion until its last registration is removed and preserves the participant', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa com Inscrição',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs($organizer)->delete(route('tournaments.destroy', $tournament))
        ->assertRedirect(route('tournaments.index'))
        ->assertSessionHas('error', 'Este torneio possui inscrições e não pode ser excluído.');

    $this->assertModelExists($tournament);
    $this->assertModelExists($registration);

    $this->delete(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertRedirect(route('tournaments.manage', $tournament));

    $this->delete(route('tournaments.destroy', $tournament))
        ->assertRedirect(route('tournaments.index'))
        ->assertSessionHas('success', 'Torneio excluído com sucesso!');

    $this->assertModelMissing($tournament);
    $this->assertModelExists($athlete);
});

it('creates a standalone participant without registering them or requiring a tournament', function () {
    $account = User::factory()->create();

    $this->actingAs($account)->post(route('athletes.store'), ['name' => 'Participante Avulso'])
        ->assertRedirect(route('tournaments.available'))
        ->assertSessionHas('success', 'Participante cadastrado com sucesso!');

    $this->assertDatabaseHas('athletes', [
        'name' => 'Participante Avulso',
        'user_id' => $account->id,
    ]);
    $this->assertDatabaseCount('registrations', 0);
});

it('preserves the selected participant when registration validation fails', function () {
    $account = User::factory()->create();
    $tournament = $account->tournaments()->create([
        'name' => 'Copa de Validação',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $account->athletes()->create(['name' => 'Ingrid']);
    $selectedAthlete = $account->athletes()->create(['name' => 'Rodrigo']);

    $this->actingAs($account)->from(route('tournaments.show', $tournament))
        ->post(route('registrations.store'), [
            'tournament_id' => 0,
            'athlete_id' => $selectedAthlete->id,
        ])->assertRedirect(route('tournaments.show', $tournament))
        ->assertSessionHasErrors('tournament_id');

    $page = $this->get(route('tournaments.show', $tournament));
    $document = new DOMDocument;
    $document->loadHTML($page->getContent());
    $fields = new DOMXPath($document);

    expect($fields->evaluate('string(//select[@name="athlete_id"]/option[@selected]/@value)'))
        ->toBe((string) $selectedAthlete->id);
    $this->assertDatabaseCount('registrations', 0);
});

it('assigns a new registration to the authenticated account despite a submitted user id', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Copa da Conta',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Participante']);

    $response = $this->actingAs($account)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
        'user_id' => $organizer->id,
    ]);

    $registration = $account->registrations()->sole();
    $response->assertRedirect(route('registrations.show', $registration));
    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id,
        'user_id' => $account->id,
        'athlete_id' => $athlete->id,
        'tournament_id' => $tournament->id,
    ]);
    $this->assertDatabaseCount('registrations', 1);
});

it('rejects missing or invalid tournament and participant identifiers without creating a registration', function (array $input, array $errors) {
    $account = User::factory()->create();
    $tournament = $account->tournaments()->create([
        'name' => 'Copa de Validação',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena',
        'registration_fee' => 25,
    ]);
    $athlete = $account->athletes()->create(['name' => 'Participante']);

    $this->actingAs($account)->post(route('registrations.store'), array_merge([
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
    ], $input))->assertSessionHasErrors($errors);

    $this->assertDatabaseCount('registrations', 0);
})->with([
    'missing tournament' => [['tournament_id' => null], ['tournament_id']],
    'missing participant' => [['athlete_id' => null], ['athlete_id']],
    'noninteger tournament' => [['tournament_id' => 'invalid'], ['tournament_id']],
    'noninteger participant' => [['athlete_id' => 'invalid'], ['athlete_id']],
    'nonexistent tournament' => [['tournament_id' => 0], ['tournament_id']],
    'nonexistent participant' => [['athlete_id' => 0], ['athlete_id']],
]);
