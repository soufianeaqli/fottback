<?php
/**
 * Script direct pour l'upload d'images sans protection CSRF
 * Ce script ne passe pas par le framework Laravel
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoriser les requêtes CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
    // Vérifier si un fichier a été envoyé
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Aucune image n\'a été envoyée ou une erreur s\'est produite');
    }

    $file = $_FILES['image'];
    
    // Vérifier le type de fichier
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non autorisé. Utilisez JPG ou PNG.');
    }
    
    // Vérifier la taille du fichier (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('L\'image est trop grande. Taille maximum: 5MB');
    }
    
    // Créer un nom de fichier unique
    $timestamp = time();
    $random = substr(md5(mt_rand()), 0, 10);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $timestamp . '_' . $random . '.' . $extension;
    
    // Créer le dossier de destination s'il n'existe pas
    $uploadDir = __DIR__ . '/storage/images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Déplacer le fichier
    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Erreur lors du déplacement du fichier téléchargé');
    }
    
    // Générer l'URL publique
    $url = '/storage/images/' . $filename;
    
    // Enregistrer l'upload dans un fichier de log
    file_put_contents(__DIR__ . '/upload-log.txt', date('Y-m-d H:i:s') . " - Uploaded: $filename\n", FILE_APPEND);
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'url' => $url,
        'message' => 'Image téléchargée avec succès'
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    file_put_contents(__DIR__ . '/upload-errors.txt', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()
    ]);
} 