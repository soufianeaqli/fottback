<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Connecte un utilisateur.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
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

            $username = $request->input('username');
            $password = $request->input('password');

            // Rechercher l'utilisateur par nom d'utilisateur (insensible à la casse)
            $user = AppUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou mot de passe incorrect'
                ], 401);
            }

            // Vérifier le mot de passe
            if (!$user->checkPassword($password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nom d\'utilisateur ou mot de passe incorrect'
                ], 401);
            }

            // Préparer les données utilisateur à retourner (sans le mot de passe)
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la connexion:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Inscrit un nouvel utilisateur.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'phone' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation échouée',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Créer l'utilisateur
            $user = AppUser::create([
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => $request->input('password'), // Le hash est fait automatiquement par le modèle
                'phone' => $request->input('phone'),
                'role' => 'user', // Par défaut, tous les nouveaux utilisateurs sont des utilisateurs normaux
            ]);

            // Préparer les données utilisateur à retourner (sans le mot de passe)
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'user' => $userData
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'inscription:', [
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
     * Vérifie si un nom d'utilisateur est disponible.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUsername(Request $request)
    {
        try {
            $username = $request->input('username');

            if (!$username) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nom d\'utilisateur non fourni'
                ], 400);
            }

            $exists = AppUser::whereRaw('LOWER(username) = ?', [strtolower($username)])->exists();

            return response()->json([
                'success' => true,
                'available' => !$exists
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du nom d\'utilisateur:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du nom d\'utilisateur: ' . $e->getMessage()
            ], 500);
        }
    }
}
