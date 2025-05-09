<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Désactiver la vérification CSRF pour les routes API
        if ($request->is('api/*')) {
            $request->attributes->set('middleware.disable_csrf', true);
        }

        // Gérer les requêtes OPTIONS pour CORS
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'http://localhost:3000')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        // Ajouter les en-têtes CORS pour toutes les réponses
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');

        return $response;
    }
} 