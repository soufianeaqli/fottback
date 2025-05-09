<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        try {
            Log::info('Début de l\'upload d\'image');

            if (!$request->hasFile('image')) {
                Log::warning('Aucun fichier image reçu');
                return response()->json(['error' => 'Aucune image n\'a été envoyée'], 400);
            }

            $file = $request->file('image');
            Log::info('Fichier reçu', ['mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
            
            // Valider le type de fichier
            $validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file->getMimeType(), $validTypes)) {
                Log::warning('Type de fichier non valide', ['mime_type' => $file->getMimeType()]);
                return response()->json(['error' => 'Type de fichier non autorisé. Utilisez JPG ou PNG.'], 400);
            }

            // Vérifier la taille du fichier
            if ($file->getSize() > 5 * 1024 * 1024) {
                Log::warning('Fichier trop grand', ['size' => $file->getSize()]);
                return response()->json(['error' => 'L\'image est trop grande. Taille maximum: 5MB'], 400);
            }

            // Créer le dossier si nécessaire
            $uploadPath = public_path('storage/terrains');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
                Log::info('Dossier créé:', ['path' => $uploadPath]);
            }
            
            // Générer un nom unique pour l'image
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $fullPath = $uploadPath . '/' . $filename;
            
            // Déplacer le fichier
            $file->move($uploadPath, $filename);
            Log::info('Image déplacée vers:', ['path' => $fullPath]);
            
            // Vérifier si le fichier existe
            if (!file_exists($fullPath)) {
                Log::error('Le fichier n\'a pas été correctement sauvegardé');
                return response()->json(['error' => 'Erreur lors de la sauvegarde de l\'image'], 500);
            }
            
            // Définir les permissions
            chmod($fullPath, 0644);
            Log::info('Permissions définies sur le fichier');
            
            // Construire l'URL pour l'accès public
            $url = '/storage/terrains/' . $filename;
            Log::info('URL générée', ['url' => $url]);
            
            return response()->json(['url' => $url]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload de l\'image:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()], 500);
        }
    }
} 