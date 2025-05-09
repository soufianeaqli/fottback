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

// Vérifier que les paramètres nécessaires sont fournis
if (!isset($_GET['tournoi_id']) || !isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants: tournoi_id et user_id sont requis']);
    exit;
}

$tournoiId = $_GET['tournoi_id'];
$userId = $_GET['user_id'];

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

    // Vérifier que le tournoi existe
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    $tournoi = $stmt->fetch();
    
    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi non trouvé']);
        exit;
    }

    // Trouver l'équipe de l'utilisateur dans ce tournoi
    $stmt = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ? AND user_id = ?");
    $stmt->execute([$tournoiId, $userId]);
    $team = $stmt->fetch();
    
    if (!$team) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vous n\'êtes pas inscrit à ce tournoi']);
        exit;
    }

    // Supprimer l'équipe
    $stmt = $pdo->prepare("DELETE FROM tournoi_teams WHERE id = ?");
    $stmt->execute([$team['id']]);
    
    // Récupérer le tournoi mis à jour avec toutes ses équipes restantes
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    $tournoi = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ?");
    $stmt->execute([$tournoiId]);
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
        'message' => 'Désinscription réussie',
        'data' => [
            'tournoi' => $formattedTournoi,
            'teamId' => $team['id'],
            'unregistered' => true
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la désinscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de la désinscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 