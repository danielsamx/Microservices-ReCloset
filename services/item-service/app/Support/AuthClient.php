<?php
namespace App\Support;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client used by non-auth services to introspect Sanctum tokens against the
 * Auth Service. Implements the Retry pattern (transient failures) and the
 * Circuit Breaker pattern (stops hammering a failing dependency).
 */
class AuthClient
{
    private string $base;
    private int $failThreshold = 5;   // open circuit after N consecutive failures
    private int $openSeconds  = 30;   // stay open this long

    public function __construct()
    {
        $this->base = rtrim(env('AUTH_SERVICE_URL', 'http://auth-service:8000'), '/');
    }

    public function verify(string $token): ?array
    {
        // Short cache to avoid an introspection round-trip on every request.
        $cacheKey = 'tokintro_' . sha1($token);
        if ($hit = Cache::store('file')->get($cacheKey)) {
            return $hit;
        }
        if ($this->circuitOpen()) {
            Log::warning('auth.circuit_open', ['dependency' => 'auth-service']);
            return null;
        }

        $attempts = 0;
        $lastError = null;
        while ($attempts < 3) {
            $attempts++;
            try {
                $res = Http::timeout(3)
                    ->withToken($token)
                    ->acceptJson()
                    ->get($this->base . '/api/auth/verify');

                if ($res->status() === 401) {          // definitive: invalid token
                    $this->recordSuccess();
                    return null;
                }
                if ($res->successful()) {
                    $this->recordSuccess();
                    $user = $res->json('user');
                    Cache::store('file')->put($cacheKey, $user, now()->addSeconds(30));
                    return $user;
                }
                $lastError = 'status_' . $res->status();
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
            usleep(200000 * $attempts); // 0.2s, 0.4s backoff
        }

        $this->recordFailure();
        Log::error('auth.introspect_failed', ['error' => $lastError, 'attempts' => $attempts]);
        return null;
    }

    private function circuitOpen(): bool
    {
        return (bool) Cache::store('file')->get('cb_auth_open', false);
    }
    private function recordFailure(): void
    {
        $c = Cache::store('file');
        $fails = (int) $c->get('cb_auth_fails', 0) + 1;
        $c->put('cb_auth_fails', $fails, now()->addMinutes(5));
        if ($fails >= $this->failThreshold) {
            $c->put('cb_auth_open', true, now()->addSeconds($this->openSeconds));
            $c->put('cb_auth_fails', 0, now()->addMinutes(5));
        }
    }
    private function recordSuccess(): void
    {
        $c = Cache::store('file');
        $c->forget('cb_auth_fails');
        $c->forget('cb_auth_open');
    }
}
