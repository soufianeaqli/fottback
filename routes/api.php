<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TerrainController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\NoCSRFController;

// Routes spéciales sans protection CSRF
Route::get('/no-csrf/delete-terrain/{id}', [NoCSRFController::class, 'deleteTerrain']);
Route::post('/no-csrf/add-terrain', [NoCSRFController::class, 'addTerrain']);
Route::post('/no-csrf/upload-image', [NoCSRFController::class, 'uploadImage']);

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
});
