<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Enregistre un nouveau message de contact
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
            ]);
            
            $contact = Contact::create($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $contact
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du message de contact', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Récupère tous les messages de contact
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $contacts = Contact::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des messages de contact', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Marque un message comme lu
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->read = true;
            $contact->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Message marqué comme lu',
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du marquage du message comme lu', [
                'error' => $e->getMessage(),
                'contact_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage du message: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprime un message de contact
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Message supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du message de contact', [
                'error' => $e->getMessage(),
                'contact_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du message: ' . $e->getMessage()
            ], 500);
        }
    }
}
