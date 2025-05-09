<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function index()
    {
        try {
            $reservations = Reservation::with('terrain')->get();
            return response()->json($reservations);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des réservations: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération des réservations'], 500);
        }
    }

    public function getUserReservations($username)
    {
        try {
            $reservations = Reservation::with('terrain')
                ->where('user_id', $username)
                ->get();

            return response()->json($reservations);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des réservations utilisateur: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la récupération des réservations'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'terrain_id' => 'required|exists:terrains,id',
                'user_id' => 'required',
                'name' => 'required|string',
                'email' => 'required|email',
                'date' => 'required|date',
                'time_slot' => 'required|string',
                'price' => 'required|numeric'
            ]);

            // Vérifier la disponibilité
            $existingReservation = Reservation::where('terrain_id', $validatedData['terrain_id'])
                ->where('date', $validatedData['date'])
                ->where('time_slot', $validatedData['time_slot'])
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'error' => 'Ce créneau horaire est déjà réservé'
                ], 422);
            }

            $reservation = Reservation::create($validatedData);
            return response()->json($reservation, 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la réservation: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la création de la réservation'], 500);
        }
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'terrain_id' => 'required|exists:terrains,id',
                'date' => 'required|date',
                'time_slot' => 'required|string'
            ]);

            $existingReservation = Reservation::where('terrain_id', $validatedData['terrain_id'])
                ->where('date', $validatedData['date'])
                ->where('time_slot', $validatedData['time_slot'])
                ->first();

            return response()->json([
                'available' => !$existingReservation
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de disponibilité: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la vérification de disponibilité'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'string',
                'email' => 'email',
                'date' => 'date',
                'time_slot' => 'string'
            ]);

            $reservation->update($validatedData);
            return response()->json($reservation);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la réservation: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la mise à jour de la réservation'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $reservation->delete();
            return response()->json(['message' => 'Réservation supprimée avec succès']);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la réservation: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la suppression de la réservation'], 500);
        }
    }
}
