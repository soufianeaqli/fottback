<?php

/**
 * Script direct pour gérer les messages de contact sans CSRF
 */

// Définir les en-têtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN');
header('Content-Type: application/json');

// Si c'est une requête OPTIONS (preflight), retourner juste les en-têtes
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Inclure le bootstrap de Laravel pour avoir accès à l'application
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Déterminer l'action à exécuter en fonction du paramètre 'action'
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'add':
        // Créer un message de contact
        $controller = new App\Http\Controllers\NoCSRFController();
        $response = $controller->addContact($request);
        echo $response->getContent();
        break;

    case 'get':
        // Récupérer tous les messages de contact
        $controller = new App\Http\Controllers\NoCSRFController();
        $response = $controller->getContacts();
        echo $response->getContent();
        break;

    case 'mark-read':
        // Marquer un message comme lu
        $id = $_REQUEST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID non fourni']);
            break;
        }
        
        $controller = new App\Http\Controllers\NoCSRFController();
        $response = $controller->markContactAsRead($id);
        echo $response->getContent();
        break;

    case 'delete':
        // Supprimer un message
        $id = $_REQUEST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID non fourni']);
            break;
        }
        
        $controller = new App\Http\Controllers\NoCSRFController();
        $response = $controller->deleteContact($id);
        echo $response->getContent();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}

$kernel->terminate($request, $response); 