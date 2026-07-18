<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Support\AuthClient;

/**
 * Verifies the incoming Sanctum bearer token by introspecting it against the
 * Auth Service. Uses the CircuitBreaker + Retry client (see App\Support\AuthClient).
 * On success it injects the authenticated user id/name/email into the request.
 */
class AuthenticateService
{
    public function __construct(private AuthClient $auth) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
        }
        $user = $this->auth->verify($token);
        if (!$user) {
            return response()->json(['message' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
        }
        $request->attributes->set('auth_user', $user);
        return $next($request);
    }
}
