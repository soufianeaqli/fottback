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

    // Vérifier les champs requis
    $requiredFields = ['name', 'date', 'maxTeams', 'prizePool', 'description', 'format', 'entryFee'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Le champ '$field' est requis"]);
            exit;
        }
    }

    // Connexion à la base de données
    $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'], $options);

    // Insérer le tournoi
    $stmt = $pdo->prepare("
        INSERT INTO tournois (name, date, max_teams, prize_pool, description, format, entry_fee, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $data['name'],
        $data['date'],
        $data['maxTeams'],
        $data['prizePool'],
        $data['description'],
        $data['format'],
        $data['entryFee']
    ]);
    
    $tournoiId = $pdo->lastInsertId();
    
    // Récupérer le tournoi créé
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    $tournoi = $stmt->fetch();
    
    // Ajouter les équipes vides
    $tournoi['teams'] = [];
    $tournoi['registeredTeams'] = 0;
    
    // Convertir les noms de champs pour correspondre au frontend
    $tournoi['maxTeams'] = $tournoi['max_teams'];
    $tournoi['prizePool'] = $tournoi['prize_pool'];
    $tournoi['entryFee'] = $tournoi['entry_fee'];
    
    // Renvoyer le tournoi créé
    echo json_encode([
        'success' => true,
        'message' => 'Tournoi créé avec succès',
        'data' => $tournoi
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la création du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de la création du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 