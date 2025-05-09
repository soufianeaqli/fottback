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

    // 1. Vérifier la structure de la table réservations
    $stmt = $pdo->prepare("DESCRIBE reservations");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Récupérer les données JSON
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

    // 3. Créer une réservation de test
    if ($data && is_array($data)) {
        $terrain_id = $data['terrain_id'] ?? 1;
        $user_id = $data['user_id'] ?? 'test_user';
        $name = $data['name'] ?? 'Test User';
        $email = $data['email'] ?? 'test@example.com';
        $date = $data['date'] ?? date('Y-m-d', strtotime('+1 day'));
        $time_slot = $data['time_slot'] ?? '10:00-11:00';
        
        // Vérifier si on utilise price ou prix
        $prix = null;
        if (isset($data['prix'])) {
            $prix = $data['prix'];
        } elseif (isset($data['price'])) {
            $prix = $data['price'];
        } else {
            $prix = 100; // Valeur par défaut
        }
        
        // Vérifier si la colonne s'appelle price ou prix
        $prixColumn = null;
        foreach ($columns as $column) {
            if ($column['Field'] === 'prix') {
                $prixColumn = 'prix';
                break;
            } elseif ($column['Field'] === 'price') {
                $prixColumn = 'price';
                break;
            }
        }

        // Préparer les champs de la requête en fonction des colonnes disponibles
        $fieldNames = [];
        $fieldValues = [];
        $fieldParams = [];
        
        $mappings = [
            'terrain_id' => $terrain_id,
            'user_id' => $user_id,
            'name' => $name,
            'email' => $email,
            'date' => $date,
            'time_slot' => $time_slot,
            'prix' => $prix,
            'price' => $prix,
            'is_paid' => 0,
            'accepted' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        foreach ($columns as $column) {
            $field = $column['Field'];
            if (isset($mappings[$field])) {
                $fieldNames[] = $field;
                $fieldParams[] = '?';
                $fieldValues[] = $mappings[$field];
            }
        }
        
        // Construire la requête SQL avec les champs disponibles
        $query = 'INSERT INTO reservations (' . implode(', ', $fieldNames) . ') VALUES (' . implode(', ', $fieldParams) . ')';
        
        // Exécuter la requête
        $stmt = $pdo->prepare($query);
        
        try {
            $stmt->execute($fieldValues);
            $id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Réservation de test créée avec succès',
                'reservation_id' => $id,
                'columns' => $columns,
                'data_sent' => [
                    'terrain_id' => $terrain_id,
                    'user_id' => $user_id,
                    'name' => $name,
                    'email' => $email,
                    'date' => $date,
                    'time_slot' => $time_slot,
                    $prixColumn => $prix
                ],
                'query' => $query,
                'field_values' => $fieldValues
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation de test',
                'error' => $e->getMessage(),
                'columns' => $columns,
                'data_sent' => [
                    'terrain_id' => $terrain_id,
                    'user_id' => $user_id,
                    'name' => $name,
                    'email' => $email,
                    'date' => $date,
                    'time_slot' => $time_slot,
                    $prixColumn => $prix
                ],
                'query' => $query,
                'field_values' => $fieldValues
            ]);
        }
    } else {
        // Si aucune donnée n'est envoyée, simplement afficher la structure de la table
        echo json_encode([
            'success' => true,
            'message' => 'Structure de la table reservations',
            'columns' => $columns
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} 