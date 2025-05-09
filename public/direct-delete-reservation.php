<?php
/**
 * Script direct pour supprimer une réservation sans protection CSRF
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

// Vérifier que l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de réservation invalide']);
    exit;
}

$reservationId = (int)$_GET['id'];
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$isAdmin = isset($_GET['is_admin']) && $_GET['is_admin'] === 'true';

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

    // Vérifier que la réservation existe
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit;
    }

    // Si l'utilisateur n'est pas admin, vérifier qu'il est bien le propriétaire de la réservation
    if (!$isAdmin && $userId && $reservation['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à supprimer cette réservation']);
        exit;
    }

    // Journaliser la suppression
    error_log("Suppression de la réservation ID: $reservationId par " . ($isAdmin ? "admin" : "utilisateur: $userId"));

    // Supprimer la réservation
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);

    if ($stmt->rowCount() > 0) {
        error_log("Réservation ID: $reservationId supprimée avec succès");
        echo json_encode([
            'success' => true, 
            'message' => 'Réservation supprimée avec succès',
            'data' => [
                'id' => $reservationId,
                'deleted' => true
            ]
        ]);
    } else {
        error_log("Échec de la suppression de la réservation ID: $reservationId");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Échec de la suppression de la réservation']);
    }

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la suppression de la réservation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de la suppression de la réservation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 