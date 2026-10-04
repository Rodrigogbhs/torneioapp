<?php

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    $this->travelTo(new DateTimeImmutable('2026-10-04 12:00:00'));
});

/** @return array<string, mixed> */
function businessAuditTournamentData(): array
{
    return [
        'name' => 'Copa da Auditoria',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Arena da Auditoria',
        'registration_fee' => 25,
    ];
}

/** @param array<string, mixed> $attributes */
function businessAuditTournament(User $owner, array $attributes = []): Tournament
{
    return $owner->tournaments()->create(array_merge(businessAuditTournamentData(), $attributes));
}

it('requires login for every protected business endpoint', function (string $method, string $name, array $parameters) {
    $this->{$method}(route($name, $parameters))->assertRedirect(route('login'));

    $this->assertDatabaseCount('tournaments', 0);
    $this->assertDatabaseCount('athletes', 0);
    $this->assertDatabaseCount('registrations', 0);
})->with([
    'dashboard' => ['get', 'dashboard', []],
    'my tournaments' => ['get', 'tournaments.index', []],
    'tournament creation form' => ['get', 'tournaments.create', []],
    'tournament creation' => ['post', 'tournaments.store', []],
    'participant creation form' => ['get', 'athletes.create', []],
    'participant creation' => ['post', 'athletes.store', []],
    'registration creation' => ['post', 'registrations.store', []],
    'registration details' => ['get', 'registrations.show', [999]],
    'my registrations' => ['get', 'registrations.index', []],
    'tournament management' => ['get', 'tournaments.manage', [999]],
    'Pix update' => ['patch', 'tournaments.pix-key.update', [999]],
    'registration removal' => ['delete', 'tournaments.registrations.destroy', [999, 999]],
    'payment confirmation' => ['patch', 'tournaments.registrations.confirm-payment', [999, 999]],
    'tournament deletion' => ['delete', 'tournaments.destroy', [999]],
]);

it('links the authenticated dashboard to working tournament pages', function () {
    $account = User::factory()->create();

    $this->actingAs($account)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Criar torneio')
        ->assertSee('Torneios disponíveis')
        ->assertSee('Meus torneios')
        ->assertSee(route('tournaments.create'))
        ->assertSee(route('tournaments.available'))
        ->assertSee(route('tournaments.index'));

    $this->get(route('tournaments.create'))->assertOk()->assertSee('Criar torneio');
    $this->get(route('tournaments.available'))->assertOk()->assertSee('Nenhum torneio disponível');
    $this->get(route('tournaments.index'))->assertOk()->assertSee('Você ainda não criou nenhum torneio.');
});

it('creates a tournament for the authenticated owner and ignores protected fields', function (int $fee) {
    $account = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($account)->post(route('tournaments.store'), array_merge(businessAuditTournamentData(), [
        'registration_fee' => $fee,
        'user_id' => $otherUser->id,
        'pix_key' => 'pix-ficticio-nao-aceito-no-cadastro',
    ]))->assertRedirect(route('tournaments.index'))
        ->assertSessionHas('success', 'Torneio criado com sucesso!');

    $this->assertDatabaseHas('tournaments', [
        'name' => 'Copa da Auditoria',
        'user_id' => $account->id,
        'registration_fee' => $fee,
        'pix_key' => null,
    ]);
    $this->assertDatabaseCount('tournaments', 1);
})->with(['free tournament' => 0, 'paid tournament' => 25]);

it('validates tournament fields without saving invalid tournaments', function (array $overrides, array $errors) {
    $account = User::factory()->create();

    $this->actingAs($account)->from(route('tournaments.create'))
        ->post(route('tournaments.store'), array_merge(businessAuditTournamentData(), $overrides))
        ->assertRedirect(route('tournaments.create'))
        ->assertSessionHasErrors($errors);

    $this->assertDatabaseCount('tournaments', 0);
})->with([
    'all required fields' => [array_fill_keys(['name', 'sport', 'event_date', 'location', 'registration_fee'], null), ['name', 'sport', 'event_date', 'location', 'registration_fee']],
    'long name' => [['name' => str_repeat('x', 256)], ['name']],
    'nonstring name' => [['name' => ['invalid']], ['name']],
    'long sport' => [['sport' => str_repeat('x', 256)], ['sport']],
    'nonstring sport' => [['sport' => ['invalid']], ['sport']],
    'invalid date' => [['event_date' => 'invalid'], ['event_date']],
    'long location' => [['location' => str_repeat('x', 256)], ['location']],
    'nonstring location' => [['location' => ['invalid']], ['location']],
    'negative fee' => [['registration_fee' => -1], ['registration_fee']],
    'nonnumeric fee' => [['registration_fee' => 'invalid'], ['registration_fee']],
    'fee beyond database precision' => [['registration_fee' => 100000000], ['registration_fee']],
]);

it('shows tournament validation errors while preserving the submitted fields', function () {
    $account = User::factory()->create();
    $input = array_merge(businessAuditTournamentData(), ['registration_fee' => -1]);

    $this->actingAs($account)->from(route('tournaments.create'))
        ->post(route('tournaments.store'), $input)
        ->assertSessionHasErrors('registration_fee');

    $page = $this->withCookie(session()->getName(), session()->getId())
        ->get(route('tournaments.create'));
    $page->assertOk()->assertViewHas('errors', fn ($bag) => $bag->any());
    $page->assertSee(__('validation.min.numeric', ['attribute' => 'registration fee', 'min' => 0]));
    $document = new DOMDocument;
    $document->loadHTML($page->getContent());
    $fields = new DOMXPath($document);

    foreach (['name', 'event_date', 'location', 'registration_fee'] as $field) {
        expect($fields->evaluate('string(//input[@name="'.$field.'"]/@value)'))->toBe((string) $input[$field]);
    }

    expect($fields->evaluate('string(//select[@name="sport"]/option[@selected]/@value)'))->toBe('Vôlei');
});

it('keeps public tournament pages free of administration and private participant names', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = businessAuditTournament($organizer, ['pix_key' => 'pix-ficticio-da-auditoria']);
    $ownAthlete = $account->athletes()->create(['name' => 'Participante da Conta']);
    $otherAthlete = $organizer->athletes()->create(['name' => 'Participante Privado']);

    $this->get(route('tournaments.available'))
        ->assertOk()
        ->assertSee(route('tournaments.show', $tournament))
        ->assertDontSee('Configurar Pix')
        ->assertDontSee('Excluir torneio')
        ->assertDontSee('pix-ficticio-da-auditoria');

    $this->get(route('tournaments.show', $tournament))
        ->assertOk()
        ->assertSee('Entre para se inscrever')
        ->assertSee(route('login'))
        ->assertDontSee($ownAthlete->name)
        ->assertDontSee($otherAthlete->name)
        ->assertDontSee('pix-ficticio-da-auditoria');

    $page = $this->actingAs($account)->get(route('tournaments.show', $tournament));
    $page->assertOk()->assertSee($ownAthlete->name)
        ->assertDontSee($otherAthlete->name)
        ->assertDontSee('Configurar Pix')
        ->assertDontSee('Excluir torneio')
        ->assertDontSee('pix-ficticio-da-auditoria');

    $document = new DOMDocument;
    $document->loadHTML($page->getContent());
    $fields = new DOMXPath($document);
    expect($fields->evaluate('string(//form/@action)'))->toBe(route('registrations.store'));
    expect($fields->evaluate('string(//input[@name="tournament_id"]/@value)'))->toBe((string) $tournament->id);
    expect($fields->evaluate('count(//select[@name="athlete_id"]/option[@value="'.$ownAthlete->id.'"])'))->toBe(1.0);
});

it('shows only owned tournaments with their public and administration links', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = businessAuditTournament($owner, ['pix_key' => 'pix-ficticio-da-auditoria']);
    $otherTournament = businessAuditTournament($otherUser, ['name' => 'Copa de Outra Conta']);

    $this->actingAs($owner)->get(route('tournaments.index'))
        ->assertOk()
        ->assertSee($tournament->name)
        ->assertDontSee($otherTournament->name)
        ->assertSee(route('tournaments.create'))
        ->assertSee(route('tournaments.show', $tournament))
        ->assertSee(route('tournaments.manage', $tournament))
        ->assertSee(route('tournaments.destroy', $tournament))
        ->assertDontSee('pix-ficticio-da-auditoria')
        ->assertDontSee('Configurar Pix');
});

it('creates a participant under the authenticated account even if an owner is submitted', function () {
    $account = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = businessAuditTournament($otherUser);

    $this->actingAs($account)->post(route('athletes.store'), [
        'name' => 'Participante da Auditoria',
        'user_id' => $otherUser->id,
        'tournament_id' => $tournament->id,
    ])->assertRedirect(route('tournaments.show', $tournament));

    $this->assertDatabaseHas('athletes', ['name' => 'Participante da Auditoria', 'user_id' => $account->id]);
    $this->assertDatabaseCount('athletes', 1);
    $this->assertDatabaseCount('registrations', 0);
});

it('validates participant names and tournament context before creating a participant', function (array $input, array $errors) {
    $account = User::factory()->create();

    $this->actingAs($account)->from(route('athletes.create'))->post(route('athletes.store'), $input)
        ->assertRedirect(route('athletes.create'))
        ->assertSessionHasErrors($errors);

    $this->assertDatabaseCount('athletes', 0);
})->with([
    'missing name' => [[], ['name']],
    'blank name' => [['name' => '   '], ['name']],
    'nonstring name' => [['name' => ['invalid']], ['name']],
    'long name' => [['name' => str_repeat('x', 256)], ['name']],
    'missing tournament' => [['name' => 'Participante', 'tournament_id' => 0], ['tournament_id']],
    'noninteger tournament' => [['name' => 'Participante', 'tournament_id' => 'invalid'], ['tournament_id']],
]);

it('rejects nonnumeric route identifiers with a 404 instead of a server error', function (string $method, string $name, array $parameters) {
    $account = User::factory()->create();
    $tournament = businessAuditTournament($account);

    $this->actingAs($account)->{$method}(route($name, $parameters))->assertNotFound();

    $this->assertModelExists($tournament);
})->with([
    'public tournament' => ['get', 'tournaments.show', ['invalid']],
    'management' => ['get', 'tournaments.manage', ['invalid']],
    'registration' => ['get', 'registrations.show', ['invalid']],
    'Pix update' => ['patch', 'tournaments.pix-key.update', ['invalid']],
    'tournament deletion' => ['delete', 'tournaments.destroy', ['invalid']],
    'registration deletion tournament' => ['delete', 'tournaments.registrations.destroy', ['invalid', 1]],
    'registration deletion identifier' => ['delete', 'tournaments.registrations.destroy', [1, 'invalid']],
    'payment confirmation tournament' => ['patch', 'tournaments.registrations.confirm-payment', ['invalid', 1]],
    'payment confirmation identifier' => ['patch', 'tournaments.registrations.confirm-payment', [1, 'invalid']],
]);

it('ignores submitted registration ownership and payment attributes', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = businessAuditTournament($organizer);
    $athlete = $account->athletes()->create(['name' => 'Participante']);

    $response = $this->actingAs($account)->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $athlete->id,
        'user_id' => $organizer->id,
        'payment_status' => 'confirmed',
        'payment_confirmed_at' => '2026-09-30 12:00:00',
    ]);

    $registration = $account->registrations()->sole();
    $response->assertRedirect(route('registrations.show', $registration));
    $this->assertDatabaseHas('registrations', [
        'id' => $registration->id,
        'user_id' => $account->id,
        'athlete_id' => $athlete->id,
        'tournament_id' => $tournament->id,
        'payment_status' => 'pending',
        'payment_confirmed_at' => null,
    ]);
});

it('keeps the database uniqueness constraint on tournament and athlete', function () {
    $account = User::factory()->create();
    $tournament = businessAuditTournament($account);
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $attributes = ['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id];
    $account->registrations()->create($attributes);

    expect(fn () => $account->registrations()->create($attributes))
        ->toThrow(UniqueConstraintViolationException::class);

    $this->assertDatabaseCount('registrations', 1);
    $this->assertModelExists($athlete);
});

it('allows four registrations and scopes that limit to each account and tournament', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $otherAccount = User::factory()->create();
    $tournament = businessAuditTournament($organizer);
    $otherTournament = businessAuditTournament($organizer, ['name' => 'Outra Copa']);

    $this->actingAs($account);
    foreach (range(1, 4) as $number) {
        $athlete = $account->athletes()->create(['name' => 'Participante '.$number]);
        $this->post(route('registrations.store'), ['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id])
            ->assertSessionHas('success', 'Inscrição realizada com sucesso!');
    }

    $fifthAthlete = $account->athletes()->create(['name' => 'Participante 5']);
    $this->from(route('tournaments.show', $tournament))->post(route('registrations.store'), [
        'tournament_id' => $tournament->id,
        'athlete_id' => $fifthAthlete->id,
    ])->assertRedirect(route('tournaments.show', $tournament))
        ->assertSessionHas('error', 'Você já atingiu o limite de 4 participantes neste torneio.')
        ->assertSessionHasInput('athlete_id', $fifthAthlete->id);

    expect($tournament->registrations()->where('user_id', $account->id)->count())->toBe(4);
    $this->post(route('registrations.store'), ['tournament_id' => $otherTournament->id, 'athlete_id' => $fifthAthlete->id])
        ->assertSessionHas('success', 'Inscrição realizada com sucesso!');

    $otherAthlete = $otherAccount->athletes()->create(['name' => 'Outra Conta']);
    $this->actingAs($otherAccount)->post(route('registrations.store'), ['tournament_id' => $tournament->id, 'athlete_id' => $otherAthlete->id])
        ->assertSessionHas('success', 'Inscrição realizada com sucesso!');

    $this->assertDatabaseCount('registrations', 6);
});

it('refuses to delete another owners tournament without changing it', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $tournament = businessAuditTournament($owner);

    $this->actingAs($otherUser)->delete(route('tournaments.destroy', $tournament))->assertNotFound();

    $this->assertModelExists($tournament);
});

it('renders and removes a legacy registration without an athlete instead of failing management', function () {
    $owner = User::factory()->create();
    $tournament = businessAuditTournament($owner);
    $registration = $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => null]);

    $this->actingAs($owner)->get(route('tournaments.manage', $tournament))
        ->assertOk()
        ->assertSee('Participante não informado')
        ->assertSee('Remover inscrição');

    $this->delete(route('tournaments.registrations.destroy', [$tournament, $registration]))
        ->assertRedirect(route('tournaments.manage', $tournament));

    $this->assertModelMissing($registration);
    $this->assertDatabaseCount('athletes', 0);
});

it('encrypts Pix and reveals the test fixture only on the owners pending registration', function () {
    $organizer = User::factory()->create();
    $account = User::factory()->create();
    $tournament = businessAuditTournament($organizer);
    $testPix = 'pix-ficticio-da-auditoria';
    $athlete = $account->athletes()->create(['name' => 'Participante']);
    $registration = $account->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($organizer)->patch(route('tournaments.pix-key.update', $tournament), [
        'pix_key' => $testPix,
        'user_id' => $account->id,
        'registration_fee' => 999,
    ])->assertRedirect(route('tournaments.manage', $tournament))
        ->assertSessionHas('success', 'Dados Pix cadastrados com sucesso!');

    $tournament->refresh();
    expect($tournament->getRawOriginal('pix_key'))->not->toBe($testPix);
    expect($tournament->pix_key)->toBe($testPix);
    expect($tournament->toArray())->not->toHaveKey('pix_key');
    $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'user_id' => $organizer->id, 'registration_fee' => 25]);
    $this->get(route('tournaments.manage', $tournament))->assertOk()->assertDontSee($testPix);
    $this->get(route('tournaments.available'))->assertOk()->assertDontSee($testPix);
    $this->get(route('tournaments.show', $tournament))->assertOk()->assertDontSee($testPix);
    $this->get(route('registrations.show', $registration))->assertNotFound();

    $this->actingAs($account)->get(route('registrations.show', $registration))
        ->assertOk()
        ->assertSee('Participante')
        ->assertSee('Vôlei')
        ->assertSee('25,00')
        ->assertSee('Pagamento: aguardando confirmação')
        ->assertSee($testPix);

    $registration->payment_status = 'confirmed';
    $registration->save();
    $this->get(route('registrations.show', $registration))
        ->assertOk()
        ->assertSee('Pagamento: confirmado')
        ->assertDontSee($testPix);
});

it('does not flash a Pix key when its validation fails', function () {
    $owner = User::factory()->create();
    $tournament = businessAuditTournament($owner);

    $this->actingAs($owner)->from(route('tournaments.manage', $tournament))
        ->patch(route('tournaments.pix-key.update', $tournament), [
            'pix_key' => str_repeat('x', 256),
            'name' => 'Campo comum preservado',
        ])->assertRedirect(route('tournaments.manage', $tournament))
        ->assertSessionHasErrors('pix_key')
        ->assertSessionMissing('_old_input.pix_key')
        ->assertSessionHasInput('name', 'Campo comum preservado');

    expect($tournament->fresh()->getRawOriginal('pix_key'))->toBeNull();
});

it('validates Pix without replacing the saved test fixture', function (mixed $input) {
    $owner = User::factory()->create();
    $tournament = businessAuditTournament($owner, ['pix_key' => 'pix-ficticio-original']);

    $this->actingAs($owner)->from(route('tournaments.manage', $tournament))
        ->patch(route('tournaments.pix-key.update', $tournament), ['pix_key' => $input])
        ->assertSessionHasErrors('pix_key');

    expect($tournament->fresh()->pix_key)->toBe('pix-ficticio-original');
})->with(['missing' => [null], 'nonstring' => [['invalid']], 'too long' => [str_repeat('x', 256)]]);

it('includes a CSRF token in every rendered business form', function (string $name, bool $needsTournament) {
    $owner = User::factory()->create();
    $tournament = businessAuditTournament($owner);
    $athlete = $owner->athletes()->create(['name' => 'Participante']);
    $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $page = $this->actingAs($owner)->get(route($name, $needsTournament ? [$tournament] : []));
    $page->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($page->getContent());
    $fields = new DOMXPath($document);
    $forms = $fields->query('//form[@method="POST"]');

    expect($forms->length)->toBeGreaterThan(0);
    foreach ($forms as $form) {
        expect($fields->evaluate('string(.//input[@name="_token"]/@value)', $form))->not->toBe('');
    }
})->with([
    'tournament creation' => ['tournaments.create', false],
    'participant creation' => ['athletes.create', false],
    'registration creation' => ['tournaments.show', true],
    'Pix and registration removal' => ['tournaments.manage', true],
    'tournament deletion' => ['tournaments.index', false],
]);

it('rejects missing CSRF tokens on each business mutation even when authenticated', function (string $method, string $name, string $parameterType) {
    $owner = User::factory()->create();
    $tournament = businessAuditTournament($owner);
    $athlete = $owner->athletes()->create(['name' => 'Participante']);
    $registration = $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);
    $parameters = match ($parameterType) {
        'tournament' => [$tournament],
        'registration' => [$tournament, $registration],
        default => [],
    };
    $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });

    $this->actingAs($owner)->withSession(['_token' => 'csrf-ficticio-da-auditoria'])
        ->withHeader('Sec-Fetch-Site', 'cross-site')
        ->{$method}(route($name, $parameters), array_merge(businessAuditTournamentData(), [
            'tournament_id' => $tournament->id,
            'athlete_id' => $athlete->id,
            'pix_key' => 'pix-ficticio-da-auditoria',
        ]))->assertStatus(419);

    $this->assertModelExists($tournament);
    $this->assertModelExists($athlete);
    $this->assertModelExists($registration);
    $this->assertDatabaseCount('tournaments', 1);
    $this->assertDatabaseCount('athletes', 1);
    $this->assertDatabaseCount('registrations', 1);
    expect($tournament->fresh()->pix_key)->toBeNull();
})->with([
    'tournament creation' => ['post', 'tournaments.store', 'none'],
    'participant creation' => ['post', 'athletes.store', 'none'],
    'registration creation' => ['post', 'registrations.store', 'none'],
    'Pix update' => ['patch', 'tournaments.pix-key.update', 'tournament'],
    'tournament deletion' => ['delete', 'tournaments.destroy', 'tournament'],
    'registration deletion' => ['delete', 'tournaments.registrations.destroy', 'registration'],
    'payment confirmation' => ['patch', 'tournaments.registrations.confirm-payment', 'registration'],
]);

it('accepts a valid CSRF token without disabling request forgery protection', function () {
    $account = User::factory()->create();
    $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
    {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    });

    $this->actingAs($account)->withSession(['_token' => 'csrf-ficticio-da-auditoria'])
        ->withHeader('Sec-Fetch-Site', 'cross-site')->post(route('athletes.store'), [
            '_token' => 'csrf-ficticio-da-auditoria',
            'name' => 'Participante com Token',
        ])->assertRedirect(route('tournaments.available'));

    $this->assertDatabaseHas('athletes', ['user_id' => $account->id, 'name' => 'Participante com Token']);
});

it('escapes tournament and participant names on all business listings and details', function () {
    $owner = User::factory()->create();
    $tournamentName = '<script>alert("auditoria")</script>';
    $athleteName = '<img src=x onerror=alert("auditoria")>';
    $tournament = businessAuditTournament($owner, ['name' => $tournamentName]);
    $athlete = $owner->athletes()->create(['name' => $athleteName]);
    $registration = $owner->registrations()->create(['tournament_id' => $tournament->id, 'athlete_id' => $athlete->id]);

    $this->actingAs($owner);
    foreach (['tournaments.available', 'tournaments.index'] as $name) {
        $this->get(route($name))->assertOk()->assertSee($tournamentName)->assertDontSee($tournamentName, false);
    }
    foreach (['tournaments.show', 'tournaments.manage'] as $name) {
        $this->get(route($name, $tournament))
            ->assertOk()
            ->assertSee($tournamentName)->assertDontSee($tournamentName, false)
            ->assertSee($athleteName)->assertDontSee($athleteName, false);
    }
    $this->get(route('registrations.show', $registration))
        ->assertOk()
        ->assertSee($tournamentName)->assertDontSee($tournamentName, false)
        ->assertSee($athleteName)->assertDontSee($athleteName, false);

    $this->get(route('registrations.index'))
        ->assertSee($tournamentName)->assertDontSee($tournamentName, false)
        ->assertSee($athleteName)->assertDontSee($athleteName, false);
});

it('can build a user with the two factor factory state without an invalid return', function () {
    $user = User::factory()->withTwoFactor()->make();

    expect($user->two_factor_secret)->toBeString();
    expect($user->two_factor_recovery_codes)->toBeString();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
    expect(json_decode(decrypt($user->two_factor_recovery_codes), true))->toHaveCount(8);
});
