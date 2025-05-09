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

// Vérifier que l'ID est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du tournoi manquant']);
    exit;
}

$tournoiId = $_GET['id'];

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

    // Récupérer le tournoi
    $stmt = $pdo->prepare("SELECT * FROM tournois WHERE id = ?");
    $stmt->execute([$tournoiId]);
    $tournoi = $stmt->fetch();

    if (!$tournoi) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tournoi non trouvé']);
        exit;
    }

    // Récupérer les équipes du tournoi
    $stmtTeams = $pdo->prepare("SELECT * FROM tournoi_teams WHERE tournoi_id = ?");
    $stmtTeams->execute([$tournoiId]);
    $teams = $stmtTeams->fetchAll();
    $tournoi['teams'] = $teams;
    $tournoi['registeredTeams'] = count($teams);

    // Renvoyer le tournoi
    echo json_encode([
        'success' => true,
        'message' => 'Tournoi récupéré avec succès',
        'data' => $tournoi
    ]);

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du tournoi: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 