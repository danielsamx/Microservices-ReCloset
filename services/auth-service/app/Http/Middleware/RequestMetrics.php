<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Collects lightweight in-process metrics (request counts, error counts,
 * response-time buckets) exposed at /metrics in Prometheus text format.
 */
class RequestMetrics
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (microtime(true) - $start) * 1000;

        $store = Cache::store('file');
        $store->increment('m_requests_total', 1);
        if ($response->getStatusCode() >= 500) {
            $store->increment('m_errors_total', 1);
        }
        // rolling sum + count for average latency
        $sum = (float) $store->get('m_latency_sum_ms', 0) + $ms;
        $store->forever('m_latency_sum_ms', $sum);
        $store->increment('m_latency_count', 1);

        $response->headers->set('X-Response-Time-ms', round($ms, 2));
        return $response;
    }
}
