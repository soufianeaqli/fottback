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
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Données JSON invalides']);
        exit;
    }

    // Vérifier que l'ID est fourni
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID du tournoi manquant']);
        exit;
    }

    $tournoiId = $data['id'];

    // Connexion à la base de données
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], $options);

    // Vérifier que le tournoi existe
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi non trouvé']);
        exit;
    }

    // Préparer les données à mettre à jour
    $updates = [];
    $params = [];

    // Mapper les champs du frontend vers les noms de colonnes de la base de données
    $fieldMappings = [
        'name' => 'name',
        'date' => 'date',
        'maxTeams' => 'max_teams',
        'prizePool' => 'prize_pool',
        'description' => 'description',
        'format' => 'format',
        'entryFee' => 'entry_fee'
    ];

    foreach ($fieldMappings as $frontendField => $dbField) {
        if (isset($data[$frontendField])) {
            $updates[] = "$dbField = ?";
            $params[] = $data[$frontendField];
        }
    }

    // Ajouter la date de mise à jour
    $updates[] = "updated_at = NOW()";

    // Ajouter l'ID à la fin des paramètres
    $params[] = $tournoiId;

    // Mettre à jour le tournoi
    $sql = "UPDATE tournois SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Récupérer le tournoi mis à jour
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    $tournoi = $stmt->fetch();

    // Récupérer les équipes du tournoi
    $stmtTeams = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ?");
    $stmtTeams->execute([$tournoiId]);
    $teams = $stmtTeams->fetchAll();
    $tournoi['teams'] = $teams;
    $tournoi['registeredTeams'] = count($teams);

    // Convertir les noms de champs pour correspondre au frontend
    $tournoi['maxTeams'] = $tournoi['max_teams'];
    $tournoi['prizePool'] = $tournoi['prize_pool'];
    $tournoi['entryFee'] = $tournoi['entry_fee'];

    // Renvoyer le tournoi mis à jour
    echo json_encode([
        'success' => true,
        'message' => 'Tournoi mis à jour avec succès',
        'data' => $tournoi
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la mise à jour du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 