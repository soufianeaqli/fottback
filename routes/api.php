<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TerrainController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\NoCSRFController;
use App\Http\Controllers\TournamentController;

// Routes spéciales sans protection CSRF
Route::prefix('no-csrf')->group(function () {
    // Routes pour les terrains
    Route::get('delete-terrain/{id}', [NoCSRFController::class, 'deleteTerrain']);
    Route::post('add-terrain', [NoCSRFController::class, 'addTerrain']);
    Route::post('upload-image', [NoCSRFController::class, 'uploadImage']);
    
    // Routes pour les tournois
    Route::get('delete-tournament/{id}', [NoCSRFController::class, 'deleteTournament']);
    Route::get('add-tournament', [NoCSRFController::class, 'addTournament']);
    Route::get('update-tournament/{id}', [NoCSRFController::class, 'updateTournament']);
    Route::get('register-team-tournament/{id}', [NoCSRFController::class, 'registerTeamTournament']);
    Route::get('unregister-team-tournament/{id}', [NoCSRFController::class, 'unregisterTeamTournament']);
    
    // Routes pour les contacts
    Route::get('add-contact', [NoCSRFController::class, 'addContact']);
    Route::get('get-contacts', [NoCSRFController::class, 'getContacts']);
    Route::get('mark-contact-as-read/{id}', [NoCSRFController::class, 'markContactAsRead']);
    Route::get('delete-contact/{id}', [NoCSRFController::class, 'deleteContact']);
    
    // Routes pour l'authentification
    Route::get('login', [NoCSRFController::class, 'login']);
    Route::get('register', [NoCSRFController::class, 'register']);
    Route::get('check-username', [NoCSRFController::class, 'checkUsername']);
});

Route::middleware(['api'])->group(function () {
    // Routes d'authentification
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Routes pour les terrains
    Route::get('/terrains', [TerrainController::class, 'index']);
    Route::get('/terrains/{terrain}', [TerrainController::class, 'show']);
    Route::post('/terrains', [TerrainController::class, 'store']);
    Route::post('/terrains/{terrain}', [TerrainController::class, 'update']);
    Route::delete('/terrains/{terrain}', [TerrainController::class, 'destroy']);

    // Route pour le téléchargement d'images
    Route::post('/upload-image', [ImageController::class, 'upload']);

    // Routes pour les réservations
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::get('/user/{username}', [ReservationController::class, 'getUserReservations']);
        Route::post('/', [ReservationController::class, 'store']);
        Route::post('/check-availability', [ReservationController::class, 'checkAvailability']);
        Route::put('/{id}', [ReservationController::class, 'update']);
        Route::delete('/{id}', [ReservationController::class, 'destroy']);
        Route::put('/{id}/pay', [ReservationController::class, 'markAsPaid']);
    });

    // Routes pour les tournois
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/tournaments', [TournamentController::class, 'store']);
        Route::put('/tournaments/{id}', [TournamentController::class, 'update']);
        Route::delete('/tournaments/{id}', [TournamentController::class, 'destroy']);
        Route::post('/tournaments/{id}/register', [TournamentController::class, 'registerTeam']);
        Route::post('/tournaments/{id}/unregister', [TournamentController::class, 'unregisterTeam']);
    });

    // Routes publiques pour les tournois
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/{id}', [TournamentController::class, 'show']);
});
