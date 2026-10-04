<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function available(): View
    {
        $tournaments = Tournament::whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->get();

        return view('tournaments.available', compact('tournaments'));
    }

    public function index(Request $request): View
    {
        $tournaments = $request->user()
            ->tournaments()
            ->withCount('registrations')
            ->latest()
            ->get();

        return view('tournaments.index', compact('tournaments'));
    }

    public function show(Request $request, int $tournament): View
    {
        $tournament = Tournament::findOrFail($tournament);

        $athletes = $request->user()
            ? $request->user()->athletes()->orderBy('name')->get()
            : collect();

        return view('tournaments.show', compact('tournament', 'athletes'));
    }

    public function manage(Request $request, int $tournament): View
    {
        $tournament = $request->user()
            ->tournaments()
            ->with(['registrations.athlete'])
            ->findOrFail($tournament);

        return view('tournaments.manage', compact('tournament'));
    }

    public function updatePixKey(Request $request, int $tournament): RedirectResponse
    {
        $tournament = $request->user()
            ->tournaments()
            ->findOrFail($tournament);

        $validated = $request->validate([
            'pix_key' => ['required', 'string', 'max:255'],
        ]);

        $tournament->update($validated);

        return redirect()
            ->route('tournaments.manage', $tournament)
            ->with('success', 'Dados Pix cadastrados com sucesso!');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sport' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['required', 'string', 'max:255'],
            'registration_fee' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ]);

        $request->user()
            ->tournaments()
            ->create($validated);

        return redirect()
            ->route('tournaments.index')
            ->with('success', 'Torneio criado com sucesso!');
    }

    public function destroy(Request $request, int $tournament): RedirectResponse
    {
        return DB::transaction(function () use ($request, $tournament): RedirectResponse {
            $account = $request->user()->newQuery()
                ->lockForUpdate()
                ->findOrFail($request->user()->id);

            $tournament = $account->tournaments()
                ->lockForUpdate()
                ->findOrFail($tournament);

            if ($tournament->registrations()->exists()) {
                return redirect()
                    ->route('tournaments.index')
                    ->with('error', 'Este torneio possui inscrições e não pode ser excluído.');
            }

            $tournament->delete();

            return redirect()
                ->route('tournaments.index')
                ->with('success', 'Torneio excluído com sucesso!');
        }, attempts: 3);
    }
}
