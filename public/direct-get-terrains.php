<?php
/**
 * Script direct pour récupérer tous les terrains sans protection CSRF
 * Ce script ne passe pas par le framework Laravel mais se connecte directement à la base de données
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier si c'est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Journaliser la demande
    file_put_contents(__DIR__ . '/get-terrains-log.txt', date('Y-m-d H:i:s') . " - Request received\n", FILE_APPEND);
    
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
    
    // Récupérer tous les terrains
    $stmt = $pdo->query("SELECT * FROM terrains ORDER BY id ASC");
    $terrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir les valeurs numériques
    foreach ($terrains as &$terrain) {
        $terrain['id'] = (int)$terrain['id'];
        $terrain['prix'] = (float)$terrain['prix'];
    }
    
    // Journaliser le résultat
    file_put_contents(__DIR__ . '/get-terrains-log.txt', date('Y-m-d H:i:s') . " - Found " . count($terrains) . " terrains\n", FILE_APPEND);
    
    // Renvoyer les terrains
    header('Content-Type: application/json');
    echo json_encode($terrains);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    file_put_contents(__DIR__ . '/get-terrains-errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des terrains: ' . $e->getMessage()
    ]);
} 