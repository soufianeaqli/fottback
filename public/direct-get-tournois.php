<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS depuis le frontend React
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Répondre directement aux requêtes OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que la méthode HTTP est GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    // Connexion à la base de données
    require_once 'db_connect.php';
    $conn = getConnection();
    
    // Requête pour récupérer tous les tournois avec leurs équipes
    $query = "
        SELECT 
            t.*, 
            COUNT(e.id) as nombre_equipes
        FROM 
            tournois t
        LEFT JOIN 
            equipes e ON t.id = e.tournoi_id
        GROUP BY 
            t.id
        ORDER BY 
            t.date DESC, t.heure ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tournois = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque tournoi, récupérer les équipes inscrites
    foreach ($tournois as &$tournoi) {
        $equipeQuery = "
            SELECT 
                e.*
            FROM 
                equipes e
            WHERE 
                e.tournoi_id = ?
        ";
        
        $equipeStmt = $conn->prepare($equipeQuery);
        $equipeStmt->execute([$tournoi['id']]);
        $tournoi['equipes'] = $equipeStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Retourner les tournois au format JSON
    echo json_encode($tournois);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
    exit();
}
?> 