<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TerrainController extends Controller
{
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

    public function index()
    {
        try {
            $terrains = Terrain::all()->map(function ($terrain) {
                if ($terrain->image) {
                    // Si c'est une URL externe, la laisser telle quelle
                    if (filter_var($terrain->image, FILTER_VALIDATE_URL) && 
                        !str_contains($terrain->image, '127.0.0.1:8000') && 
                        !str_contains($terrain->image, 'localhost:8000')) {
                        return $terrain;
                    }
                    
                    // Sinon, c'est une image locale ou une URL à normaliser
                    $terrain->image = $this->formatImageUrl($terrain->image);
                }
                return $terrain;
            });
            return response()->json($terrains);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des terrains:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur lors de la récupération des terrains'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Données reçues:', $request->all());

            $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'required|string',
                'prix' => 'required|numeric',
                'image' => 'nullable|string'
            ]);

            Log::info('Validation passée');

            $data = $request->all();
            if (isset($data['image'])) {
                $data['image'] = $this->formatImageUrl($data['image']);
            }

            $terrain = Terrain::create($data);
            Log::info('Terrain créé:', ['terrain' => $terrain]);

            return response()->json($terrain, 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du terrain:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Log::info('Tentative de suppression du terrain ID: ' . $id);
            
            $terrain = Terrain::findOrFail($id);
            Log::info('Terrain trouvé', ['terrain' => $terrain]);
            
            // Supprimer l'image si elle existe et n'est pas une URL externe
            if ($terrain->image && !filter_var($terrain->image, FILTER_VALIDATE_URL)) {
                $imagePath = 'public' . str_replace('/storage', '', $terrain->image);
                if (Storage::exists($imagePath)) {
                    Storage::delete($imagePath);
                    Log::info('Image supprimée: ' . $imagePath);
                } else {
                    Log::info('Image non trouvée: ' . $imagePath);
                }
            }
            
            $terrain->delete();
            Log::info('Terrain supprimé avec succès');
            
            return response()->json(['message' => 'Terrain supprimé avec succès'], 200);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du terrain:', [
                'terrain_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

