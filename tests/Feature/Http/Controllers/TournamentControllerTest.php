<?php

use App\Models\User;

it('saves a tournament Pix key without receiver name or city', function () {
    $organizer = User::factory()->create();
    $tournament = $organizer->tournaments()->create([
        'name' => 'Torneio de verão',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Ginásio',
        'registration_fee' => 20,
    ]);

    $response = $this->actingAs($organizer)->patch(route('tournaments.pix-key.update', $tournament), [
        'pix_key' => 'chave-do-organizador',
    ]);

    $response->assertRedirect(route('tournaments.manage', $tournament));
    expect($tournament->fresh()->pix_key)->toBe('chave-do-organizador');
    expect($tournament->fresh()->pix_receiver_name)->toBeNull();
    expect($tournament->fresh()->pix_receiver_city)->toBeNull();
});

it('does not let another organizer change a tournament Pix key', function () {
    $owner = User::factory()->create();
    $otherOrganizer = User::factory()->create();
    $tournament = $owner->tournaments()->create([
        'name' => 'Torneio de verão',
        'sport' => 'Vôlei',
        'event_date' => '2026-10-10',
        'location' => 'Ginásio',
        'registration_fee' => 20,
        'pix_key' => 'chave-original',
    ]);

    $response = $this->actingAs($otherOrganizer)->patch(route('tournaments.pix-key.update', $tournament), [
        'pix_key' => 'chave-indevida',
    ]);

    $response->assertNotFound();
    expect($tournament->fresh()->pix_key)->toBe('chave-original');
});
