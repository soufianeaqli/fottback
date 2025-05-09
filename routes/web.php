<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\NoCSRFController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Routes spéciales complètement sans protection CSRF (doit être avant le middleware web)
Route::group(['prefix' => 'api', 'middleware' => ['api']], function () {
    Route::get('/no-csrf/delete-terrain/{id}', [NoCSRFController::class, 'deleteTerrain']);
    Route::post('/no-csrf/add-terrain', [NoCSRFController::class, 'addTerrain']);
    Route::post('/no-csrf/upload-image', [NoCSRFController::class, 'uploadImage']);
});

// Route pour servir les images des terrains
Route::get('/storage/terrains/{filename}', function ($filename) {
    $path = storage_path('app/public/terrains/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }

    // Définir le type MIME en fonction de l'extension
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $mime = $mime_types[strtolower($extension)] ?? 'application/octet-stream';
    
    return response()->file($path, [
        'Content-Type' => $mime
    ]);
});

require __DIR__.'/auth.php';
