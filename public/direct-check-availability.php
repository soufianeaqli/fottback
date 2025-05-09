<?php
/**
 * Script direct pour vérifier la disponibilité d'un créneau pour un terrain sans protection CSRF
 * Ce script ne passe pas par le framework Laravel mais se connecte directement à la base de données
 */

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

// Récupérer les paramètres de requête
$terrain_id = $_GET['terrain_id'] ?? null;
$date = $_GET['date'] ?? null;
$time_slot = $_GET['time_slot'] ?? null;

// Vérifier les paramètres requis
if (!$terrain_id || !$date || !$time_slot) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Paramètres manquants. terrain_id, date et time_slot sont requis',
        'available' => false
    ]);
    exit;
}

// Inclure la fonction de chargement des variables d'environnement
require_once __DIR__ . '/env-loader.php';

// Charger les variables d'environnement
$env = loadEnvVars();
if (empty($env) || empty($env['DB_HOST']) || empty($env['DB_DATABASE']) || empty($env['DB_USERNAME'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Impossible de charger les variables d\'environnement', 'available' => false]);
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

    // Vérifier si le terrain existe
    $stmt = $pdo->prepare("SELECT id FROM terrains WHERE id = ?");
    $stmt->execute([$terrain_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Terrain non trouvé', 'available' => false]);
        exit;
    }

    // Vérifier la disponibilité
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE terrain_id = ? AND date = ? AND time_slot = ?");
    $stmt->execute([$terrain_id, $date, $time_slot]);
    $exists = $stmt->fetch();

    if ($exists) {
        // Le créneau est déjà réservé
        echo json_encode(['success' => true, 'available' => false, 'message' => 'Ce créneau est déjà réservé']);
    } else {
        // Le créneau est disponible
        echo json_encode(['success' => true, 'available' => true, 'message' => 'Ce créneau est disponible']);
    }

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage(), 'available' => false]);
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage(), 'available' => false]);
} 