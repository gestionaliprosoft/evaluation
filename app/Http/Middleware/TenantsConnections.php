<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

class TenantsConnections
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Retrieve the token from the Authorization header (Bearer xxxxx)
        $token = $request->bearerToken();

        if ($token) {
            // Search for the token in the Sanctum table (personal_access_tokens)
            $hashedToken = PersonalAccessToken::findToken($token);

            if ($hashedToken && $hashedToken->tokenable) {
                // Retrieve the user associated with the token
                $user = $hashedToken->tokenable;

                // Temporarily associate the user with the request for Sanctum
                auth()->setUser($user);
            }
        }

        $tenantService = app(TenantService::class);

        $tenantService->setTenant();

        return $next($request);
    }
}
