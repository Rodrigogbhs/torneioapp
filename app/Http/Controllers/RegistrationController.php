<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $registrations = $request->user()->registrations()
            ->with(['tournament', 'athlete'])
            ->latest()
            ->get();

        return view('registrations.index', compact('registrations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tournament_id' => ['required', 'integer', 'exists:tournaments,id'],
            'athlete_id' => ['required', 'integer', 'exists:athletes,id'],
        ]);

        // O atleta precisa pertencer à conta que está fazendo a inscrição.
        $athlete = $request->user()
            ->athletes()
            ->findOrFail($request->integer('athlete_id'));

        return DB::transaction(function () use ($request, $validated, $athlete): RedirectResponse {
            // Bloqueia a conta para serializar inscrições simultâneas, mesmo sem inscrições anteriores.
            $account = $request->user()->newQuery()
                ->lockForUpdate()
                ->findOrFail($request->user()->id);

            $tournament = Tournament::lockForUpdate()
                ->findOrFail($request->integer('tournament_id'));

            $existingRegistration = $account
                ->registrations()
                ->where('tournament_id', $validated['tournament_id'])
                ->where('athlete_id', $athlete->id)
                ->first();

            if ($existingRegistration) {
                return redirect()
                    ->route('registrations.show', $existingRegistration->id)
                    ->with('success', 'Este participante já está inscrito neste torneio.');
            }

            // Limite de quatro inscrições por conta em cada torneio.
            $registrationCount = $account
                ->registrations()
                ->where('tournament_id', $validated['tournament_id'])
                ->count();

            if ($registrationCount >= 4) {
                return back()
                    ->withInput($request->only(['tournament_id', 'athlete_id']))
                    ->with('error', 'Você já atingiu o limite de 4 participantes neste torneio.');
            }

            $registration = $account
                ->registrations()
                ->firstOrCreate([
                    'tournament_id' => $validated['tournament_id'],
                    'athlete_id' => $athlete->id,
                ]);

            if ((float) $tournament->registration_fee === 0.0) {
                $registration->payment_status = 'confirmed';
                $registration->payment_confirmed_at = now();
                $registration->save();
            }

            return redirect()
                ->route('registrations.show', $registration->id)
                ->with('success', 'Inscrição realizada com sucesso!');
        }, attempts: 3);
    }

    public function show(Request $request, int $registration): View
    {
        $registration = $request->user()
            ->registrations()
            ->with(['tournament', 'athlete'])
            ->findOrFail($registration);

        return view('registrations.show', compact('registration'));
    }

    public function destroy(Request $request, int $tournament, int $registration): RedirectResponse
    {
        $tournament = $request->user()
            ->tournaments()
            ->findOrFail($tournament);

        $registration = $tournament
            ->registrations()
            ->findOrFail($registration);

        $registration->delete();

        return redirect()
            ->route('tournaments.manage', $tournament->id)
            ->with('success', 'Inscrição removida com sucesso!');
    }

    public function confirmPayment(Request $request, int $tournament, int $registration): RedirectResponse
    {
        $tournament = $request->user()->tournaments()->findOrFail($tournament);
        $registration = $tournament->registrations()->findOrFail($registration);

        abort_if($registration->user_id === $request->user()->id, 403, 'Você não pode confirmar o próprio pagamento.');

        $tournament->registrations()
            ->whereKey($registration->id)
            ->where('payment_status', 'pending')
            ->update([
                'payment_status' => 'confirmed',
                'payment_confirmed_at' => now(),
            ]);

        return redirect()
            ->route('tournaments.manage', $tournament)
            ->with('success', 'Pagamento confirmado com sucesso!');
    }
}
