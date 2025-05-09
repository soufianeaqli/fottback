<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gestion des CORS pour les requêtes depuis le frontend React
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS immédiatement
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Vérifier que la méthode de requête est GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$userId = $_GET['user_id'] ?? null;
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit;
}

// Inclure la fonction de chargement des variables d'environnement
require_once __DIR__ . '/env-loader.php';

// Charger les variables d'environnement
$env = loadEnvVars();
if (empty($env) || empty($env['DB_HOST']) || empty($env['DB_DATABASE']) || empty($env['DB_USERNAME'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de charger les variables d\'environnement']);
    exit;
}

try {
    // Connexion à la base de données
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], $options);

    // Récupérer les réservations de l'utilisateur avec le nom du terrain
    $stmt = $pdo->prepare("
        SELECT r.*, t.titre as terrainTitle, t.prix as terrainPrice 
        FROM reservations r
        LEFT JOIN terrains t ON r.terrain_id = t.id
        WHERE r.user_id = ?
        ORDER BY r.date DESC, r.time_slot ASC
    ");
    $stmt->execute([$userId]);
    $reservations = $stmt->fetchAll();

    // Transformer les résultats pour le format attendu par le frontend
    $formattedReservations = [];
    foreach ($reservations as $reservation) {
        $formattedReservations[] = [
            'id' => $reservation['id'],
            'terrainId' => $reservation['terrain_id'],
            'terrainTitle' => $reservation['terrainTitle'],
            'terrainPrice' => $reservation['prix'] ?? $reservation['terrainPrice'] ?? 0,
            'userId' => $reservation['user_id'],
            'name' => $reservation['name'],
            'email' => $reservation['email'],
            'date' => $reservation['date'],
            'timeSlot' => $reservation['time_slot'],
            'accepted' => (bool) $reservation['accepted'],
            'rejected' => (bool) ($reservation['rejected'] ?? false),
            'isPaid' => (bool) $reservation['is_paid'],
            'createdAt' => $reservation['created_at']
        ];
    }

    // Renvoyer les réservations
    echo json_encode($formattedReservations);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 