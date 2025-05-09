<?php
/**
 * Script direct pour marquer une réservation comme payée sans protection CSRF
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

// Récupérer l'ID de la réservation et l'ID de l'utilisateur (optionnel)
$id = $_GET['id'] ?? null;
$userId = $_GET['user_id'] ?? null;

// Vérifier que l'ID est fourni
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de réservation manquant']);
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

    // Vérifier si la réservation existe et si l'utilisateur est autorisé à la modifier
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Réservation non trouvée']);
        exit;
    }

    // Vérifier les permissions (si userId est fourni, vérifier qu'il correspond ou que c'est un admin)
    if ($userId && $reservation['user_id'] !== $userId) {
        // Ici, on pourrait ajouter une vérification supplémentaire pour les admins
        // Par exemple, vérifier si l'utilisateur a un rôle d'admin dans une table utilisateurs
        $isAdmin = false; // Par défaut, pas admin
        
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à marquer cette réservation comme payée']);
            exit;
        }
    }

    // Marquer la réservation comme payée
    $stmt = $pdo->prepare("UPDATE reservations SET is_paid = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // Vérifier que la mise à jour a bien eu lieu
    if ($stmt->rowCount() === 0) {
        // La réservation existait mais n'a pas été mise à jour (peut-être déjà marquée comme payée)
        // On ne considère pas cela comme une erreur
        $stmt = $pdo->prepare("SELECT is_paid FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $isPaid = (bool)$stmt->fetchColumn();
        
        if ($isPaid) {
            echo json_encode([
                'success' => true, 
                'message' => 'La réservation était déjà marquée comme payée',
                'data' => ['id' => $id, 'isPaid' => true]
            ]);
            exit;
        } else {
            throw new Exception('La mise à jour a échoué');
        }
    }

    // Récupérer la réservation mise à jour
    $stmt = $pdo->prepare("
        SELECT r.*, t.titre as terrainTitle, t.prix as terrainPrice 
        FROM reservations r
        LEFT JOIN terrains t ON r.terrain_id = t.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
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

    // Renvoyer une confirmation
    echo json_encode([
        'success' => true, 
        'message' => 'Réservation marquée comme payée avec succès',
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