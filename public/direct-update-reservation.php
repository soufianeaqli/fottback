<?php
/**
 * Script direct pour modifier une réservation sans protection CSRF
 * Ce script ne passe pas par le framework Laravel mais se connecte directement à la base de données
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Gestion des CORS pour les requêtes depuis le frontend React
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS immédiatement
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Vérifier que la méthode de requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
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
    // Récupérer les données JSON
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
        exit;
    }

    // Vérifier l'ID de réservation
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
        exit;
    }

    // Connexion à la base de données
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], $options);

    // Vérifier si la réservation existe
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$data['id']]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit;
    }

    // Vérifier si les nouveaux créneaux n'entrent pas en conflit (si date et time_slot sont modifiés)
    if (!empty($data['date']) && !empty($data['timeSlot']) && 
        ($data['date'] !== $reservation['date'] || $data['timeSlot'] !== $reservation['time_slot'])) {
        
        $stmt = $pdo->prepare("
            SELECT id FROM reservations 
            WHERE terrain_id = ? AND date = ? AND time_slot = ? AND id != ?
        ");
        $stmt->execute([
            $reservation['terrain_id'],
            $data['date'],
            $data['timeSlot'],
            $data['id']
        ]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà réservé']);
            exit;
        }
    }

    // Préparer les données pour la mise à jour
    $updates = [];
    $params = [];

    // Champs qui peuvent être mis à jour
    $allowedFields = [
        'name' => 'name',
        'email' => 'email',
        'date' => 'date',
        'timeSlot' => 'time_slot',
        'accepted' => 'accepted',
        'rejected' => 'rejected',
        'isPaid' => 'is_paid'
    ];

    foreach ($allowedFields as $requestField => $dbField) {
        if (isset($data[$requestField])) {
            $updates[] = "$dbField = ?";
            
            // Conversion pour les booléens
            if (in_array($dbField, ['accepted', 'rejected', 'is_paid'])) {
                $params[] = $data[$requestField] ? 1 : 0;
            } else {
                $params[] = $data[$requestField];
            }
        }
    }

    // Ajouter la mise à jour du timestamp
    $updates[] = "updated_at = NOW()";

    // Si aucune mise à jour n'est nécessaire
    if (empty($updates)) {
        echo json_encode([
            'success' => true,
            'message' => 'Aucune modification nécessaire',
            'data' => $reservation
        ]);
        exit;
    }

    // Ajouter l'ID à la fin des paramètres
    $params[] = $data['id'];

    // Mettre à jour la réservation
    $stmt = $pdo->prepare("
        UPDATE reservations 
        SET " . implode(', ', $updates) . "
        WHERE id = ?
    ");
    $stmt->execute($params);

    // Récupérer la réservation mise à jour
    $stmt = $pdo->prepare("
        SELECT r.*, t.titre as terrainTitle, t.prix as terrainPrice 
        FROM reservations r
        LEFT JOIN terrains t ON r.terrain_id = t.id
        WHERE r.id = ?
    ");
    $stmt->execute([$data['id']]);
    $updatedReservation = $stmt->fetch();

    // Formater la réponse pour la compatibilité avec le frontend
    $response = [
        'id' => $updatedReservation['id'],
        'terrainId' => $updatedReservation['terrain_id'],
        'terrainTitle' => $updatedReservation['terrainTitle'],
        'terrainPrice' => $updatedReservation['prix'] ?? $updatedReservation['terrainPrice'] ?? 0,
        'userId' => $updatedReservation['user_id'],
        'name' => $updatedReservation['name'],
        'email' => $updatedReservation['email'],
        'date' => $updatedReservation['date'],
        'timeSlot' => $updatedReservation['time_slot'],
        'accepted' => (bool) $updatedReservation['accepted'],
        'rejected' => (bool) ($updatedReservation['rejected'] ?? false),
        'isPaid' => (bool) $updatedReservation['is_paid'],
        'createdAt' => $updatedReservation['created_at']
    ];

    // Renvoyer la réponse
    echo json_encode([
        'success' => true, 
        'message' => 'Réservation mise à jour avec succès', 
        'data' => $response
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 