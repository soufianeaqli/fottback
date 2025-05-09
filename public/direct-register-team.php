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
    $requiredFields = ['tournoi_id', 'teamName', 'captainName', 'phoneNumber', 'email', 'user_id'];
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

    // Vérifier que le tournoi existe
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$data['tournoi_id']]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi non trouvé']);
        exit;
    }

    // Vérifier que l'utilisateur n'est pas déjà inscrit à ce tournoi
    $stmt = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ? AND (user_id = ? OR email = ?)");
    $stmt->execute([$data['tournoi_id'], $data['user_id'], $data['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit à ce tournoi']);
        exit;
    }

    // Vérifier que le tournoi n'est pas complet
    $stmt = $pdo->prepare("SELECT COUNT(*) as team_count FROM tournoi_teams WHERE tournoi_id = ?");
    $stmt->execute([$data['tournoi_id']]);
    $teamCount = $stmt->fetch()['team_count'];
    
    if ($teamCount >= $tournoi['max_teams']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le tournoi est complet']);
        exit;
    }

    // Insérer l'équipe
    $stmt = $pdo->prepare("
        INSERT INTO tournoi_teams (tournoi_id, name, captain, email, phone, user_id, players, registration_date, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
    ");
    
    $stmt->execute([
        $data['tournoi_id'],
        $data['teamName'],
        $data['captainName'],
        $data['email'],
        $data['phoneNumber'],
        $data['user_id'],
        $data['players'] ?? 5
    ]);
    
    $teamId = $pdo->lastInsertId();
    
    // Récupérer l'équipe créée
    $stmt = $pdo->prepare("SELECT * FROM tournoi_teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();
    
    // Formater la réponse pour correspondre au frontend
    $formattedTeam = [
        'id' => $team['id'],
        'name' => $team['name'],
        'captain' => $team['captain'],
        'email' => $team['email'],
        'phoneNumber' => $team['phone'],
        'userId' => $team['user_id'],
        'players' => $team['players'],
        'registrationDate' => $team['registration_date']
    ];
    
    // Récupérer le tournoi mis à jour avec toutes ses équipes
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$data['tournoi_id']]);
    $tournoi = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ?");
    $stmt->execute([$data['tournoi_id']]);
    $teams = $stmt->fetchAll();
    
    // Formater le tournoi pour correspondre au frontend
    $formattedTournoi = [
        'id' => $tournoi['id'],
        'name' => $tournoi['name'],
        'date' => $tournoi['date'],
        'maxTeams' => $tournoi['max_teams'],
        'prizePool' => $tournoi['prize_pool'],
        'description' => $tournoi['description'],
        'format' => $tournoi['format'],
        'entryFee' => $tournoi['entry_fee'],
        'teams' => [],
        'registeredTeams' => count($teams)
    ];
    
    // Formater les équipes
    foreach ($teams as $t) {
        $formattedTournoi['teams'][] = [
            'id' => $t['id'],
            'name' => $t['name'],
            'captain' => $t['captain'],
            'email' => $t['email'],
            'phoneNumber' => $t['phone'],
            'userId' => $t['user_id'],
            'players' => $t['players'],
            'registrationDate' => $t['registration_date']
        ];
    }
    
    // Renvoyer la réponse
    echo json_encode([
        'success' => true,
        'message' => 'Équipe inscrite avec succès',
        'data' => [
            'team' => $formattedTeam,
            'tournoi' => $formattedTournoi
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de l'inscription de l'équipe: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de l'inscription de l'équipe: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 