<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Métricas ligeras en proceso (conteos y latencia) expuestas en /metrics.
 * Toda la parte de caché va protegida: si la caché falla, la petición sigue igual.
 */
class RequestMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (microtime(true) - $start) * 1000;

        try {
            $store = Cache::store('file');
            $store->increment('m_requests_total', 1);
            if (method_exists($response, 'getStatusCode') && $response->getStatusCode() >= 500) {
                $store->increment('m_errors_total', 1);
            }
            $sum = (float) $store->get('m_latency_sum_ms', 0) + $ms;
            $store->forever('m_latency_sum_ms', $sum);
            $store->increment('m_latency_count', 1);
        } catch (\Throwable $e) {
            // Nunca dejar que las métricas afecten al request.
        }

        if (method_exists($response, 'headers')) {
            $response->headers->set('X-Response-Time-ms', round($ms, 2));
        }
        return $response;
    }
}
