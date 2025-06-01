<?php

namespace App\Http\Controllers;

use App\Models\Terrain;
use App\Models\Tournament;
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

    /**
     * Supprime un tournoi sans vérification CSRF.
     */
    public function deleteTournament($id)
    {
        Log::info('NoCSRFController - Tentative de suppression du tournoi ID: ' . $id);
        
        try {
            $tournament = Tournament::findOrFail($id);
            Log::info('NoCSRFController - Tournoi trouvé', ['tournament' => $tournament->toArray()]);
            
            // Suppression du tournoi
            $tournament->delete();
            Log::info('NoCSRFController - Tournoi supprimé avec succès');
            
            return response()->json([
                'success' => true,
                'message' => 'Tournoi supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la suppression du tournoi:', [
                'tournament_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du tournoi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajoute un tournoi sans vérification CSRF (via GET).
     */
    public function addTournament(Request $request)
    {
        Log::info('NoCSRFController - Tentative d\'ajout d\'un tournoi', ['data' => $request->all()]);
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            Log::info('NoCSRFController - Données JSON décodées', ['data' => $data]);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'name' => 'required|string|max:255',
                'date' => 'required|date',
                'max_teams' => 'required|integer',
                'prize_pool' => 'required|string',
                'description' => 'required|string',
                'format' => 'required|string',
                'entry_fee' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            Log::info('NoCSRFController - Validation des données réussie');
            
            // Création du tournoi
            $tournament = Tournament::create([
                'name' => $data['name'],
                'date' => $data['date'],
                'max_teams' => $data['max_teams'],
                'registered_teams' => 0,
                'prize_pool' => $data['prize_pool'],
                'description' => $data['description'],
                'format' => $data['format'],
                'entry_fee' => $data['entry_fee'],
                'teams' => [],
            ]);
            
            Log::info('NoCSRFController - Tournoi créé avec succès', ['tournament' => $tournament->toArray()]);
            
            return response()->json($tournament, 201);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'ajout du tournoi:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout du tournoi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifie un tournoi sans vérification CSRF (via GET).
     */
    public function updateTournament(Request $request, $id)
    {
        Log::info('NoCSRFController - Tentative de modification du tournoi ID: ' . $id, ['data' => $request->all()]);
        
        try {
            $tournament = Tournament::findOrFail($id);
            Log::info('NoCSRFController - Tournoi trouvé', ['tournament' => $tournament->toArray()]);
            
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            Log::info('NoCSRFController - Données JSON décodées', ['data' => $data]);
            
            // Mise à jour du tournoi
            $tournament->update($data);
            Log::info('NoCSRFController - Tournoi modifié avec succès', ['tournament' => $tournament->toArray()]);
            
            return response()->json($tournament);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la modification du tournoi:', [
                'tournament_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du tournoi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inscrit une équipe à un tournoi sans vérification CSRF (via GET).
     */
    public function registerTeamTournament(Request $request, $id)
    {
        Log::info('NoCSRFController - Tentative d\'inscription à un tournoi ID: ' . $id, ['data' => $request->all()]);
        
        try {
            $tournament = Tournament::findOrFail($id);
            Log::info('NoCSRFController - Tournoi trouvé', ['tournament' => $tournament->toArray()]);
            
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            Log::info('NoCSRFController - Données JSON décodées', ['data' => $data]);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'team_name' => 'required|string',
                'captain_name' => 'required|string',
                'phone_number' => 'required|string',
                'email' => 'required|email',
                'user_id' => 'nullable|integer'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérifier si le tournoi est complet
            if ($tournament->registered_teams >= $tournament->max_teams) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le tournoi est complet'
                ], 422);
            }
            
            // Vérifier si l'équipe est déjà inscrite
            $teams = $tournament->teams ?? [];
            
            // Vérifier si l'utilisateur est déjà inscrit (par email ou par user_id)
            $emailExists = collect($teams)->contains('email', $data['email']);
            $userIdExists = isset($data['user_id']) && collect($teams)->contains('user_id', $data['user_id']);
            
            if ($emailExists || $userIdExists) {
                Log::info('NoCSRFController - Utilisateur déjà inscrit', [
                    'emailExists' => $emailExists,
                    'userIdExists' => $userIdExists,
                    'email' => $data['email'],
                    'user_id' => $data['user_id'] ?? null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Vous êtes déjà inscrit à ce tournoi'
                ], 422);
            }
            
            // Ajouter l'équipe
            $teams[] = [
                'id' => time(),
                'name' => $data['team_name'],
                'captain' => $data['captain_name'],
                'phone' => $data['phone_number'],
                'email' => $data['email'],
                'user_id' => $data['user_id'] ?? null,
                'registration_date' => now()->toDateString(),
            ];
            
            $tournament->teams = $teams;
            $tournament->registered_teams = count($teams);
            $tournament->save();
            
            Log::info('NoCSRFController - Équipe inscrite avec succès', [
                'tournament' => $tournament->toArray(),
                'team' => end($teams)
            ]);
            
            return response()->json($tournament);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'inscription à un tournoi:', [
                'tournament_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Désinscrit une équipe d'un tournoi sans vérification CSRF (via GET).
     */
    public function unregisterTeamTournament(Request $request, $id)
    {
        Log::info('NoCSRFController - Tentative de désinscription d\'un tournoi ID: ' . $id, ['data' => $request->all()]);
        
        try {
            $tournament = Tournament::findOrFail($id);
            Log::info('NoCSRFController - Tournoi trouvé', ['tournament' => $tournament->toArray()]);
            
            // Récupérer l'ID de l'équipe et/ou l'ID utilisateur
            $teamId = $request->query('team_id');
            $userId = $request->query('user_id');
            $email = $request->query('email');
            $captain = $request->query('captain'); // Ajouter le support pour le nom du capitaine
            
            // Log des données reçues
            Log::info('NoCSRFController - Données de désinscription reçues', [
                'teamId' => $teamId,
                'userId' => $userId,
                'email' => $email,
                'captain' => $captain
            ]);
            
            if (!$teamId && !$userId && !$email && !$captain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiant d\'équipe ou d\'utilisateur manquant'
                ], 400);
            }
            
            // Vérifier si l'équipe existe
            $teams = $tournament->teams ?? [];
            
            // Chercher l'équipe par ID, userId, email ou nom du capitaine
            $team = null;
            if ($teamId) {
                $team = collect($teams)->firstWhere('id', $teamId);
            } else if ($userId) {
                $team = collect($teams)->firstWhere('user_id', $userId);
            } else if ($email) {
                $team = collect($teams)->firstWhere('email', $email);
            } else if ($captain) {
                $team = collect($teams)->firstWhere('captain', $captain);
            }
            
            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Équipe non trouvée'
                ], 404);
            }
            
            Log::info('NoCSRFController - Équipe trouvée pour désinscription', [
                'team' => $team
            ]);
            
            // Filtrer les équipes pour retirer l'équipe trouvée
            $filteredTeams = array_filter($teams, function($t) use ($team) {
                return $t['id'] != $team['id'];
            });
            
            $tournament->teams = array_values($filteredTeams);
            $tournament->registered_teams = count($filteredTeams);
            $tournament->save();
            
            Log::info('NoCSRFController - Équipe désinscrite avec succès', [
                'tournament' => $tournament->toArray()
            ]);
            
            return response()->json($tournament);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la désinscription d\'un tournoi:', [
                'tournament_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désinscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistre un nouveau message de contact sans vérification CSRF (via GET).
     */
    public function addContact(Request $request)
    {
        Log::info('NoCSRFController - Tentative d\'ajout d\'un message de contact', ['data' => $request->all()]);
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            Log::info('NoCSRFController - Données JSON décodées', ['data' => $data]);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            Log::info('NoCSRFController - Validation des données réussie');
            
            // Création du message de contact
            $contact = \App\Models\Contact::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'read' => false
            ]);
            
            Log::info('NoCSRFController - Message de contact créé avec succès', ['contact' => $contact->toArray()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $contact
            ], 201);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'ajout du message de contact:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère tous les messages de contact sans vérification CSRF (via GET).
     */
    public function getContacts()
    {
        Log::info('NoCSRFController - Tentative de récupération des messages de contact');
        
        try {
            $contacts = \App\Models\Contact::orderBy('created_at', 'desc')->get();
            
            Log::info('NoCSRFController - Messages de contact récupérés avec succès', ['count' => $contacts->count()]);
            
            return response()->json([
                'success' => true,
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la récupération des messages de contact:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marque un message de contact comme lu sans vérification CSRF (via GET).
     */
    public function markContactAsRead($id)
    {
        Log::info('NoCSRFController - Tentative de marquage d\'un message de contact comme lu', ['id' => $id]);
        
        try {
            $contact = \App\Models\Contact::findOrFail($id);
            $contact->read = true;
            $contact->save();
            
            Log::info('NoCSRFController - Message de contact marqué comme lu avec succès', ['contact' => $contact->toArray()]);
            
            return response()->json([
                'success' => true,
                'message' => 'Message marqué comme lu',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors du marquage du message de contact comme lu:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime un message de contact sans vérification CSRF (via GET).
     */
    public function deleteContact($id)
    {
        Log::info('NoCSRFController - Tentative de suppression d\'un message de contact', ['id' => $id]);
        
        try {
            $contact = \App\Models\Contact::findOrFail($id);
            $contact->delete();
            
            Log::info('NoCSRFController - Message de contact supprimé avec succès');
            
            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la suppression du message de contact:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connecte un utilisateur sans vérification CSRF (via GET).
     */
    public function login(Request $request)
    {
        // Log sans informations sensibles
        Log::info('NoCSRFController - Tentative de connexion');
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            // Log sécurisé sans mot de passe
            $safeData = $data;
            if (isset($safeData['password'])) {
                $safeData['password'] = '********';
            }
            Log::info('NoCSRFController - Données JSON décodées', ['username' => $safeData['username'] ?? 'non fourni']);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérifier si c'est un utilisateur par défaut avec mot de passe par défaut
            $isDefaultUser = in_array(strtolower($data['username']), ['admin', 'soufiane', 'user']);
            $isDefaultPassword = $data['password'] === $data['username'] . '123';
            
            // Rechercher l'utilisateur par nom d'utilisateur (insensible à la casse)
            $user = \App\Models\AppUser::whereRaw('LOWER(username) = ?', [strtolower($data['username'])])->first();
            
            if (!$user) {
                // Si l'utilisateur n'existe pas et c'est un utilisateur par défaut, on le crée
                if ($isDefaultUser) {
                    Log::info('NoCSRFController - Création d\'un utilisateur par défaut', ['username' => $data['username']]);
                    
                    $role = strtolower($data['username']) === 'admin' ? 'admin' : 'user';
                    $displayName = strtolower($data['username']) === 'admin' ? 'Admin User' : 
                                  (strtolower($data['username']) === 'soufiane' ? 'Soufiane' : 'Regular User');
                    
                    // Utiliser le mot de passe fourni ou le mot de passe par défaut
                    $password = $isDefaultPassword ? $data['password'] : $data['username'] . '123';
                    
                    $user = \App\Models\AppUser::create([
                        'name' => $displayName,
                        'username' => $data['username'],
                        'password' => $password,
                        'email' => $data['username'] . '@example.com',
                        'phone' => '0600000000',
                        'role' => $role
                    ]);
                    
                    // Préparer les données utilisateur à retourner
                    $userData = [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'role' => $user->role,
                    ];
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Connexion réussie (utilisateur par défaut créé)',
                        'user' => $userData
                    ]);
                }
                
                // Message d'erreur générique pour ne pas révéler l'existence ou non d'un utilisateur
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }
            
            // Vérifier le mot de passe
            if (!$user->checkPassword($data['password'])) {
                // Message d'erreur générique pour ne pas révéler la raison exacte de l'échec
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }
            
            // Préparer les données utilisateur à retourner
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ];
            
            Log::info('NoCSRFController - Connexion réussie', ['user_id' => $user->id, 'username' => $user->username]);
            
            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la connexion:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion'
            ], 500);
        }
    }
    
    /**
     * Inscrit un nouvel utilisateur sans vérification CSRF (via GET).
     */
    public function register(Request $request)
    {
        // Log sans informations sensibles
        Log::info('NoCSRFController - Tentative d\'inscription');
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            // Log sécurisé sans mot de passe
            $safeData = $data;
            if (isset($safeData['password'])) {
                $safeData['password'] = '********';
            }
            Log::info('NoCSRFController - Données JSON décodées', [
                'username' => $safeData['username'] ?? 'non fourni',
                'email' => $safeData['email'] ?? 'non fourni'
            ]);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'name' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'phone' => 'required|string',
            ]);
            
            if ($validator->fails()) {
                // Vérifier explicitement si les erreurs sont liées à l'unicité
                $errors = $validator->errors()->toArray();
                if (isset($errors['username']) && strpos(implode(' ', $errors['username']), 'déjà') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce nom d\'utilisateur est déjà utilisé',
                        'errors' => ['username' => ['Ce nom d\'utilisateur est déjà utilisé']]
                    ], 422);
                }
                
                if (isset($errors['email']) && strpos(implode(' ', $errors['email']), 'déjà') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet email est déjà utilisé',
                        'errors' => ['email' => ['Cet email est déjà utilisé']]
                    ], 422);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Vérification supplémentaire explicite pour l'unicité
            $usernameExists = \App\Models\AppUser::whereRaw('LOWER(username) = ?', [strtolower($data['username'])])->exists();
            if ($usernameExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce nom d\'utilisateur est déjà utilisé',
                    'errors' => ['username' => ['Ce nom d\'utilisateur est déjà utilisé']]
                ], 422);
            }
            
            $emailExists = \App\Models\AppUser::whereRaw('LOWER(email) = ?', [strtolower($data['email'])])->exists();
            if ($emailExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet email est déjà utilisé',
                    'errors' => ['email' => ['Cet email est déjà utilisé']]
                ], 422);
            }
            
            // Créer l'utilisateur
            $user = \App\Models\AppUser::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'],
                'role' => 'user',
            ]);
            
            // Préparer les données utilisateur à retourner
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ];
            
            Log::info('NoCSRFController - Inscription réussie', ['user_id' => $user->id, 'username' => $user->username]);
            
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'user' => $userData
            ], 201);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de l\'inscription:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Vérifie la disponibilité d'un nom d'utilisateur sans vérification CSRF (via GET).
     */
    public function checkUsername(Request $request)
    {
        $username = $request->query('username');
        
        Log::info('NoCSRFController - Vérification de la disponibilité du nom d\'utilisateur', ['username' => $username]);
        
        try {
            if (!$username) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nom d\'utilisateur non fourni'
                ], 400);
            }
            
            $exists = \App\Models\AppUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->exists();
            
            return response()->json([
                'success' => true,
                'available' => !$exists
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la vérification de la disponibilité du nom d\'utilisateur:', [
                'username' => $username,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du nom d\'utilisateur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Met à jour les informations d'un utilisateur sans vérification CSRF (via GET).
     */
    public function updateUserProfile(Request $request)
    {
        // Log sans informations sensibles
        Log::info('NoCSRFController - Tentative de mise à jour du profil utilisateur');
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            // Log sécurisé sans mot de passe
            $safeData = $data;
            if (isset($safeData['current_password'])) {
                $safeData['current_password'] = '********';
            }
            if (isset($safeData['new_password'])) {
                $safeData['new_password'] = '********';
            }
            Log::info('NoCSRFController - Données JSON décodées', [
                'user_id' => $safeData['id'] ?? 'non fourni',
                'username' => $safeData['username'] ?? 'non fourni'
            ]);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'id' => 'required|integer|exists:users,id',
                'username' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'current_password' => 'sometimes|string',
                'new_password' => 'sometimes|string|min:6',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Trouver l'utilisateur
            $user = \App\Models\AppUser::find($data['id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }
            
            // Vérifier si le nom d'utilisateur est déjà pris (par un autre utilisateur)
            if ($user->username !== $data['username']) {
                $usernameExists = \App\Models\AppUser::where('username', $data['username'])
                    ->where('id', '!=', $user->id)
                    ->exists();
                    
                if ($usernameExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce nom d\'utilisateur est déjà utilisé'
                    ], 422);
                }
            }
            
            // Vérifier si l'email est déjà pris (par un autre utilisateur)
            if ($user->email !== $data['email']) {
                $emailExists = \App\Models\AppUser::where('email', $data['email'])
                    ->where('id', '!=', $user->id)
                    ->exists();
                    
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cet email est déjà utilisé'
                    ], 422);
                }
            }
            
            // Si un changement de mot de passe est demandé
            if (isset($data['current_password']) && isset($data['new_password']) && !empty($data['current_password']) && !empty($data['new_password'])) {
                // Vérifier le mot de passe actuel
                if (!$user->checkPassword($data['current_password'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le mot de passe actuel est incorrect'
                    ], 422);
                }
                
                // Mettre à jour le mot de passe
                $user->password = $data['new_password'];
                Log::info('NoCSRFController - Mot de passe mis à jour', ['user_id' => $user->id]);
            }
            
            // Mettre à jour les informations de l'utilisateur
            $user->name = $data['name'];
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->phone = $data['phone'];
            $user->save();
            
            // Préparer les données utilisateur à retourner
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ];
            
            Log::info('NoCSRFController - Profil utilisateur mis à jour avec succès', ['user_id' => $user->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'user' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la mise à jour du profil utilisateur:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Met à jour le mot de passe d'un utilisateur sans vérification CSRF (via GET).
     */
    public function updateUserPassword(Request $request)
    {
        // Log sans informations sensibles
        Log::info('NoCSRFController - Tentative de mise à jour du mot de passe');
        
        try {
            // Récupérer les données JSON
            $json = $request->query('data');
            if (!$json) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée JSON fournie'
                ], 400);
            }
            
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], 400);
            }
            
            // Log sécurisé sans mot de passe
            Log::info('NoCSRFController - Tentative de mise à jour du mot de passe', ['user_id' => $data['id'] ?? 'non fourni']);
            
            // Validation des données
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'id' => 'required|integer|exists:users,id',
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Trouver l'utilisateur
            $user = \App\Models\AppUser::find($data['id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }
            
            // Vérifier le mot de passe actuel
            if (!$user->checkPassword($data['current_password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 422);
            }
            
            // Mettre à jour le mot de passe
            $user->password = $data['new_password'];
            $user->save();
            
            Log::info('NoCSRFController - Mot de passe mis à jour avec succès', ['user_id' => $user->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('NoCSRFController - Erreur lors de la mise à jour du mot de passe:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage()
            ], 500);
        }
    }
} 