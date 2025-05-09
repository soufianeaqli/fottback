<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/*',
        'sanctum/csrf-cookie',
        'api/no-csrf/*'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->isReading($request) ||
            $this->runningUnitTests() ||
            $this->shouldPassThrough($request) ||
            $this->tokensMatch($request)) {

            return $this->addCookieToResponse($request, $next($request));
        }

        // Si la requête échoue, renvoyer une réponse JSON avec le nouveau token
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => 'CSRF token mismatch',
                'status' => 419
            ], 419);
        }

        throw new TokenMismatchException('CSRF token mismatch');
    }

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $this->getTokenFromSession();

        return is_string($token) &&
               is_string($sessionToken) &&
               hash_equals($sessionToken, $token);
    }

    /**
     * Get the CSRF token from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        return $request->header('X-XSRF-TOKEN') ??
               $request->input('_token') ??
               $request->cookie('XSRF-TOKEN');
    }

    /**
     * Get the CSRF token from the session.
     *
     * @return string|null
     */
    protected function getTokenFromSession()
    {
        return session()->token();
    }
} 