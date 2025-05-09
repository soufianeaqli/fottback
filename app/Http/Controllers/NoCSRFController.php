<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NoCSRFController extends Controller
{
    /**
     * Supprime un terrain sans vérification CSRF.
     */
    public function deleteTerrain($id)
    {
        Log::info('NoCSRFController - Tentative de suppression du terrain ID: ' . $id);
        
        try {
            $terrain = Terrain::findOrFail($id);
            Log::info('NoCSRFController - Terrain trouvé', ['terrain' => $terrain->toArray()]);
            
            // Suppression de l'image associée si nécessaire
            if ($terrain->image && !filter_var($terrain->image, FILTER_VALIDATE_URL)) {
                $imagePath = 'public' . str_replace('/storage', '', $terrain->image);
                if (Storage::exists($imagePath)) {
                    Storage::delete($imagePath);
                    Log::info('NoCSRFController - Image supprimée: ' . $imagePath);
                }
            }
            
            // Suppression du terrain
            $terrain->delete();
            Log::info('NoCSRFController - Terrain supprimé avec succès');
            
            return response()->json([
                'success' => true,
                'message' => 'Terrain supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la suppression du terrain:', [
                'terrain_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du terrain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajoute un terrain sans vérification CSRF.
     */
    public function addTerrain(Request $request)
    {
        Log::info('NoCSRFController - Tentative d\'ajout d\'un terrain', ['data' => $request->all()]);
        
        try {
            // Validation des données
            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'required|string',
                'prix' => 'required|numeric',
                'image' => 'nullable|string'
            ]);
            
            Log::info('NoCSRFController - Validation des données réussie');
            
            // Formatage de l'URL de l'image si nécessaire
            $data = $request->all();
            if (isset($data['image'])) {
                $data['image'] = $this->formatImageUrl($data['image']);
            }
            
            // Création du terrain
            $terrain = Terrain::create($data);
            Log::info('NoCSRFController - Terrain créé avec succès', ['terrain' => $terrain->toArray()]);
            
            return response()->json($terrain, 201);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'ajout du terrain:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du terrain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload une image sans vérification CSRF.
     */
    public function uploadImage(Request $request)
    {
        Log::info('NoCSRFController - Tentative d\'upload d\'image');
        
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:5120' // 5MB max
            ]);
            
            Log::info('NoCSRFController - Validation de l\'image réussie');
            
            $image = $request->file('image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            // Stockage de l'image
            $path = $image->storeAs('public/images', $filename);
            Log::info('NoCSRFController - Image téléchargée avec succès', ['path' => $path]);
            
            // Génération de l'URL
            $url = '/storage/images/' . $filename;
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => 'Image téléchargée avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'upload de l\'image:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formater l'URL de l'image.
     */
    private function formatImageUrl($imageUrl)
    {
        if (!$imageUrl) return null;

        // Si c'est une URL externe (commence par http:// ou https://)
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }

        // Pour les images locales, s'assurer qu'elles commencent par /storage
        if (!str_starts_with($imageUrl, '/storage')) {
            $imageUrl = '/storage' . $imageUrl;
        }

        return $imageUrl;
    }
} 