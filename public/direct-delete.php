<?php
/**
 * Script direct pour la suppression de terrains sans protection CSRF
 * Ce script ne passe pas par le framework Laravel mais se connecte directement à la base de données
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier si c'est une requête GET avec un ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

$id = intval($_GET['id']);
if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    // Charger le fichier .env pour obtenir les informations de connexion à la base de données
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($name, $value) = explode('=', $line, 2);
                $_ENV[$name] = $value;
            }
        }
    }

    // Connexion à la base de données
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_DATABASE'] ?? 'laravel';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Journaliser l'action
    file_put_contents(__DIR__ . '/delete-log.txt', date('Y-m-d H:i:s') . " - Attempting to delete terrain ID: $id\n", FILE_APPEND);

    // Récupérer le chemin de l'image avant de supprimer le terrain
    $stmt = $pdo->prepare("SELECT image FROM terrains WHERE id = ?");
    $stmt->execute([$id]);
    $terrain = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$terrain) {
        throw new Exception("Terrain avec ID $id non trouvé");
    }
    
    // Supprimer le terrain de la base de données
    $stmt = $pdo->prepare("DELETE FROM terrains WHERE id = ?");
    $success = $stmt->execute([$id]);
    
    if (!$success) {
        throw new Exception("Échec de la suppression du terrain avec ID $id");
    }
    
    // Journaliser la suppression
    file_put_contents(__DIR__ . '/delete-log.txt', date('Y-m-d H:i:s') . " - Deleted terrain ID: $id\n", FILE_APPEND);
    
    // Supprimer l'image associée si elle existe
    if (!empty($terrain['image']) && strpos($terrain['image'], '/storage/images/') !== false) {
        $imagePath = __DIR__ . $terrain['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
            file_put_contents(__DIR__ . '/delete-log.txt', date('Y-m-d H:i:s') . " - Deleted image: {$terrain['image']}\n", FILE_APPEND);
        }
    }
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Terrain supprimé avec succès'
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    file_put_contents(__DIR__ . '/delete-errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression du terrain: ' . $e->getMessage()
    ]);
} 