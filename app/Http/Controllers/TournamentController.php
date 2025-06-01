<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TournamentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tournaments = Tournament::all();
        return response()->json($tournaments);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Vérifier si l'utilisateur est admin
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'max_teams' => 'required|integer',
            'prize_pool' => 'required|string',
            'description' => 'required|string',
            'format' => 'required|string',
            'entry_fee' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tournament = Tournament::create([
            'name' => $request->name,
            'date' => $request->date,
            'max_teams' => $request->max_teams,
            'registered_teams' => 0,
            'prize_pool' => $request->prize_pool,
            'description' => $request->description,
            'format' => $request->format,
            'entry_fee' => $request->entry_fee,
            'teams' => [],
        ]);

        return response()->json($tournament, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tournament = Tournament::findOrFail($id);
        return response()->json($tournament);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Vérifier si l'utilisateur est admin
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $tournament = Tournament::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'date' => 'date',
            'max_teams' => 'integer',
            'prize_pool' => 'string',
            'description' => 'string',
            'format' => 'string',
            'entry_fee' => 'string',
            'teams' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tournament->update($request->all());
        return response()->json($tournament);
    }

    /**
     * Register a team for a tournament.
     */
    public function registerTeam(Request $request, string $id)
    {
        if (!auth()->user()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $tournament = Tournament::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'team_name' => 'required|string',
            'captain_name' => 'required|string',
            'phone_number' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier si le tournoi est complet
        if ($tournament->registered_teams >= $tournament->max_teams) {
            return response()->json(['message' => 'Le tournoi est complet'], 422);
        }

        // Vérifier si l'équipe est déjà inscrite
        $teams = $tournament->teams ?? [];
        if (collect($teams)->contains('email', $request->email)) {
            return response()->json(['message' => 'Vous êtes déjà inscrit à ce tournoi'], 422);
        }

        // Ajouter l'équipe
        $teams[] = [
            'id' => time(),
            'name' => $request->team_name,
            'captain' => $request->captain_name,
            'phone' => $request->phone_number,
            'email' => $request->email,
            'registration_date' => now()->toDateString(),
        ];

        $tournament->teams = $teams;
        $tournament->registered_teams = count($teams);
        $tournament->save();

        return response()->json($tournament);
    }

    /**
     * Remove a team from a tournament.
     */
    public function unregisterTeam(Request $request, string $id)
    {
        if (!auth()->user()) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $tournament = Tournament::findOrFail($id);
        $teamId = $request->team_id;

        $teams = $tournament->teams ?? [];
        
        // Vérifier si l'utilisateur est autorisé à désinscrire l'équipe
        $team = collect($teams)->firstWhere('id', $teamId);
        if (!$team || ($team['email'] !== auth()->user()->email && auth()->user()->role !== 'admin')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $filteredTeams = array_filter($teams, function($team) use ($teamId) {
            return $team['id'] != $teamId;
        });

        $tournament->teams = array_values($filteredTeams);
        $tournament->registered_teams = count($filteredTeams);
        $tournament->save();

        return response()->json($tournament);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Vérifier si l'utilisateur est admin
        if (!auth()->user() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $tournament = Tournament::findOrFail($id);
        $tournament->delete();

        return response()->json(null, 204);
    }
}
