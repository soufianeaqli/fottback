<?php
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
    $jsonInput = file_get_contents('php://input');
    error_log("Données JSON brutes reçues: " . $jsonInput);
    
    $data = json_decode($jsonInput, true);
    if (!$data) {
        // Si JSON n'est pas reçu, essayer de récupérer les données POST
        $data = $_POST;
        error_log("Données POST reçues: " . print_r($data, true));
    } else {
        error_log("Données JSON décodées: " . print_r($data, true));
    }

    // Vérifier les données requises
    $requiredFields = ['terrain_id', 'date', 'time_slot', 'name', 'email'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Le champ '$field' est requis"]);
            exit;
        }
    }

    // Vérifier que la date est dans le futur
    $reservationDate = new DateTime($data['date']);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    if ($reservationDate < $today) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La date de réservation doit être dans le futur']);
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

    // Obtenir la structure de la table
    $stmt = $pdo->prepare("DESCRIBE reservations");
    $stmt->execute();
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifier si la colonne 'prix' existe
    $hasPrix = false;
    foreach ($tableStructure as $column) {
        if ($column['Field'] === 'prix') {
            $hasPrix = true;
            break;
        }
    }
    
    error_log("Structure de la table: " . print_r($tableStructure, true));
    error_log("La colonne 'prix' existe: " . ($hasPrix ? "Oui" : "Non"));

    // Vérifier si le terrain existe
    $stmt = $pdo->prepare("SELECT id FROM terrains WHERE id = ?");
    $stmt->execute([$data['terrain_id']]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Terrain non trouvé']);
        exit;
    }

    // Vérifier la disponibilité (éviter les doublons)
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE terrain_id = ? AND date = ? AND time_slot = ?");
    $stmt->execute([
        $data['terrain_id'],
        $data['date'],
        $data['time_slot']
    ]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ce créneau est déjà réservé']);
        exit;
    }

    // Préparer les données pour l'insertion
    $user_id = !empty($data['user_id']) ? $data['user_id'] : 'guest';
    
    // Obtenir le prix - accepter 'price' ou 'prix'
    $prix = 0;
    if (isset($data['prix'])) {
        $prix = is_numeric($data['prix']) ? $data['prix'] : 0;
        error_log("Prix défini dans 'prix': $prix");
    } elseif (isset($data['price'])) {
        $prix = is_numeric($data['price']) ? $data['price'] : 0;
        error_log("Prix défini dans 'price': $prix");
    }
    
    $accepted = isset($data['accepted']) ? $data['accepted'] : false;
    $is_paid = isset($data['is_paid']) ? $data['is_paid'] : false;

    error_log("Données préparées pour l'insertion: user_id=$user_id, prix=$prix, accepted=" . 
              ($accepted ? "true" : "false") . ", is_paid=" . ($is_paid ? "true" : "false"));

    // Vérifier si la colonne s'appelle price ou prix
    $prixColumnName = null;
    $hasCreatedAt = false;
    $hasUpdatedAt = false;
    
    foreach ($tableStructure as $column) {
        error_log("Colonne trouvée: " . $column['Field'] . " de type " . $column['Type']);
        
        if ($column['Field'] === 'prix') {
            $prixColumnName = 'prix';
        } elseif ($column['Field'] === 'price') {
            $prixColumnName = 'price';
        } elseif ($column['Field'] === 'created_at') {
            $hasCreatedAt = true;
        } elseif ($column['Field'] === 'updated_at') {
            $hasUpdatedAt = true;
        }
    }
    
    error_log("Nom de la colonne prix: " . ($prixColumnName ?? "non trouvé"));

    // Construire la requête SQL dynamiquement en fonction des colonnes disponibles
    $fields = ['terrain_id', 'user_id', 'name', 'email', 'date', 'time_slot'];
    $values = [$data['terrain_id'], $user_id, $data['name'], $data['email'], $data['date'], $data['time_slot']];
    $placeholders = ['?', '?', '?', '?', '?', '?'];
    
    // Ajouter le prix avec le bon nom de colonne
    if ($prixColumnName) {
        $fields[] = $prixColumnName;
        $values[] = $prix;
        $placeholders[] = '?';
    }
    
    // Ajouter accepted et is_paid
    $fields[] = 'accepted';
    $values[] = $accepted ? 1 : 0;
    $placeholders[] = '?';
    
    $fields[] = 'is_paid';
    $values[] = $is_paid ? 1 : 0;
    $placeholders[] = '?';
    
    // Ajouter created_at et updated_at si nécessaire
    if ($hasCreatedAt) {
        $fields[] = 'created_at';
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
    }
    
    if ($hasUpdatedAt) {
        $fields[] = 'updated_at';
        $values[] = date('Y-m-d H:i:s');
        $placeholders[] = '?';
    }
    
    $query = "INSERT INTO reservations (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    error_log("Requête SQL: $query");
    error_log("Valeurs: " . implode(', ', $values));
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($values);
    $id = $pdo->lastInsertId();
    error_log("Réservation insérée avec ID: $id");

    // Récupérer la réservation créée
    $stmt = $pdo->prepare("
        SELECT r.*, t.titre as terrainTitle
        FROM reservations r
        LEFT JOIN terrains t ON r.terrain_id = t.id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();
    error_log("Réservation récupérée: " . print_r($reservation, true));

    // Formater la réponse pour la compatibilité avec le frontend
    $response = [
        'id' => $reservation['id'],
        'terrainId' => $reservation['terrain_id'],
        'terrainTitle' => $reservation['terrainTitle'],
        'userId' => $reservation['user_id'],
        'name' => $reservation['name'],
        'email' => $reservation['email'],
        'date' => $reservation['date'],
        'timeSlot' => $reservation['time_slot'],
        'terrainPrice' => $reservation['prix'],
        'accepted' => (bool) $reservation['accepted'],
        'isPaid' => (bool) $reservation['is_paid'],
        'created_at' => $reservation['created_at']
    ];
    error_log("Réponse formatée: " . print_r($response, true));

    // Renvoyer la réponse
    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Réservation créée avec succès', 'data' => $response]);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    error_log("Trace PDO: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 