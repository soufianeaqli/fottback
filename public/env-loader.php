<?php
/**
 * Fonction pour charger les variables d'environnement depuis le fichier .env
 * d'une manière plus tolérante que parse_ini_file()
 * 
 * @return array Les variables d'environnement chargées
 */
function loadEnvVars() {
    $envFile = __DIR__ . '/../.env';
    $env = [];
    
    if (!file_exists($envFile)) {
        return $env;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorer les commentaires
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Trouver les lignes qui contiennent une variable
        if (strpos($line, '=') !== false) {
            // Diviser la ligne au premier signe égal
            list($name, $value) = explode('=', $line, 2);
            
            // Nettoyer les valeurs
            $name = trim($name);
            $value = trim($value);
            
            // Supprimer les guillemets des valeurs si présents
            if (preg_match('/^"(.+)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.+)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            // Résoudre les références aux autres variables
            if (preg_match('/\${(.+)}/', $value, $matches)) {
                $varName = $matches[1];
                if (isset($env[$varName])) {
                    $value = str_replace('${' . $varName . '}', $env[$varName], $value);
                }
            }
            
            $env[$name] = $value;
        }
    }
    
    return $env;
} 