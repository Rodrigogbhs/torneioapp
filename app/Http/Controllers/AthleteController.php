<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AthleteController extends Controller
{
    public function create(Request $request): View
    {
        $tournament = $request->filled('tournament')
            ? Tournament::findOrFail($request->integer('tournament'))
            : null;

        return view('athletes.create', compact('tournament'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
        ]);

        $athlete = $request->user()->athletes()->create(['name' => $validated['name']]);

        if (isset($validated['tournament_id'])) {
            return redirect()
                ->route('tournaments.show', $validated['tournament_id'])
                ->with('selected_athlete_id', $athlete->getKey())
                ->with('success', 'Participante cadastrado com sucesso! Para concluir a inscrição neste torneio, clique em "Inscrever-se".');
        }

        return redirect()
            ->route('tournaments.available')
            ->with('success', 'Participante cadastrado com sucesso!');
    }
}
