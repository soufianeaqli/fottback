<?php
/**
 * Script direct pour ajouter une réservation sans protection CSRF
 * Ce script ne passe pas par le framework Laravel mais se connecte directement à la base de données
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Répondre immédiatement aux requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer et décoder les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Données JSON invalides');
    }
    
    // Valider les données obligatoires
    if (empty($data['terrain_id']) || empty($data['date']) || empty($data['time_slot'])) {
        throw new Exception('Données manquantes: terrain_id, date et time_slot sont requis');
    }
    
    // Journaliser les données reçues
    file_put_contents(__DIR__ . '/add-reservation-log.txt', date('Y-m-d H:i:s') . " - Data received: " . $json . "\n", FILE_APPEND);
    
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
    
    // Vérifier si le créneau est déjà réservé
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM reservations 
        WHERE terrain_id = ? AND date = ? AND time_slot = ?
    ");
    $checkStmt->execute([
        $data['terrain_id'],
        $data['date'],
        $data['time_slot']
    ]);
    
    $count = $checkStmt->fetchColumn();
    
    if ($count > 0) {
        throw new Exception('Ce créneau est déjà réservé pour ce terrain');
    }
    
    // Récupérer le prix du terrain
    $terrainStmt = $pdo->prepare("SELECT prix FROM terrains WHERE id = ?");
    $terrainStmt->execute([$data['terrain_id']]);
    $prix = $terrainStmt->fetchColumn();
    
    if (!$prix) {
        throw new Exception('Terrain non trouvé');
    }
    
    // Préparer les données pour l'insertion
    $terrainId = (int)$data['terrain_id'];
    $userId = isset($data['user_id']) ? $data['user_id'] : 'guest';
    $name = isset($data['name']) ? htmlspecialchars(trim($data['name'])) : null;
    $email = isset($data['email']) ? htmlspecialchars(trim($data['email'])) : null;
    $date = $data['date'];
    $timeSlot = $data['time_slot'];
    $price = isset($data['price']) ? (float)$data['price'] : $prix;
    $isPaid = isset($data['is_paid']) ? (bool)$data['is_paid'] : false;
    $accepted = isset($data['accepted']) ? (bool)$data['accepted'] : false;
    $rejected = isset($data['rejected']) ? (bool)$data['rejected'] : false;
    $dateCreation = date('Y-m-d H:i:s');
    
    // Insérer la réservation dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO reservations (
            terrain_id, user_id, name, email, date, time_slot, price, 
            is_paid, accepted, rejected, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $terrainId, $userId, $name, $email, $date, $timeSlot, $price,
        $isPaid ? 1 : 0, $accepted ? 1 : 0, $rejected ? 1 : 0, $dateCreation, $dateCreation
    ]);
    
    if (!$success) {
        throw new Exception("Échec de l'ajout de la réservation dans la base de données");
    }
    
    // Récupérer l'ID de la réservation nouvellement insérée
    $reservationId = $pdo->lastInsertId();
    
    // Journaliser l'ajout
    file_put_contents(__DIR__ . '/add-reservation-log.txt', date('Y-m-d H:i:s') . " - Added reservation ID: $reservationId\n", FILE_APPEND);
    
    // Récupérer le titre du terrain
    $terrainTitleStmt = $pdo->prepare("SELECT titre FROM terrains WHERE id = ?");
    $terrainTitleStmt->execute([$terrainId]);
    $terrainTitle = $terrainTitleStmt->fetchColumn();
    
    // Créer l'objet réservation à renvoyer
    $reservation = [
        'id' => (string)$reservationId,
        'terrain_id' => $terrainId,
        'terrainId' => $terrainId,
        'terrainTitle' => $terrainTitle,
        'terrainPrice' => $prix,
        'user_id' => $userId,
        'userId' => $userId,
        'name' => $name,
        'email' => $email,
        'date' => $date,
        'time_slot' => $timeSlot,
        'timeSlot' => $timeSlot,
        'price' => $price,
        'is_paid' => $isPaid,
        'accepted' => $accepted,
        'rejected' => $rejected,
        'created_at' => $dateCreation,
        'updated_at' => $dateCreation
    ];
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Réservation ajoutée avec succès',
        'data' => $reservation
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    file_put_contents(__DIR__ . '/add-reservation-errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de la réservation: ' . $e->getMessage()
    ]);
} 