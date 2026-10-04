<?php

use App\Http\Controllers\AthleteController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::view('/tournaments/create', 'tournaments.create')
    ->middleware('auth')
    ->name('tournaments.create');

Route::get('/tournaments/available', [TournamentController::class, 'available'])
    ->name('tournaments.available');

Route::get('/athletes/create', [AthleteController::class, 'create'])
    ->middleware('auth')
    ->name('athletes.create');

Route::post('/athletes', [AthleteController::class, 'store'])
    ->middleware('auth')
    ->name('athletes.store');

Route::get('/tournaments/{tournament}/manage', [TournamentController::class, 'manage'])
    ->whereNumber('tournament')
    ->middleware('auth')
    ->name('tournaments.manage');

Route::patch('/tournaments/{tournament}/pix-key', [TournamentController::class, 'updatePixKey'])
    ->whereNumber('tournament')
    ->middleware('auth')
    ->name('tournaments.pix-key.update');

Route::post('/tournaments', [TournamentController::class, 'store'])
    ->middleware('auth')
    ->name('tournaments.store');

Route::get('/tournaments', [TournamentController::class, 'index'])
    ->middleware('auth')
    ->name('tournaments.index');

Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])
    ->whereNumber('tournament')
    ->name('tournaments.show');

Route::post('/registrations', [RegistrationController::class, 'store'])
    ->middleware('auth')
    ->name('registrations.store');

Route::get('/registrations', [RegistrationController::class, 'index'])
    ->middleware('auth')
    ->name('registrations.index');

Route::get('/registrations/{registration}', [RegistrationController::class, 'show'])
    ->whereNumber('registration')
    ->middleware('auth')
    ->name('registrations.show');

Route::delete(
    '/tournaments/{tournament}/registrations/{registration}',
    [RegistrationController::class, 'destroy']
)
    ->whereNumber(['tournament', 'registration'])
    ->middleware('auth')
    ->name('tournaments.registrations.destroy');

Route::patch(
    '/tournaments/{tournament}/registrations/{registration}/confirm-payment',
    [RegistrationController::class, 'confirmPayment']
)
    ->whereNumber(['tournament', 'registration'])
    ->middleware('auth')
    ->name('tournaments.registrations.confirm-payment');

Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])
    ->whereNumber('tournament')
    ->middleware('auth')
    ->name('tournaments.destroy');

require __DIR__.'/settings.php';
