<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
class MetricsController extends Controller
{
    public function metrics()
    {
        $c = Cache::store('file');
        $svc = env('APP_NAME', 'recloset');
        $req = (int) $c->get('m_requests_total', 0);
        $err = (int) $c->get('m_errors_total', 0);
        $sum = (float) $c->get('m_latency_sum_ms', 0);
        $cnt = (int) $c->get('m_latency_count', 0);
        $avg = $cnt > 0 ? $sum / $cnt : 0;
        $up = 1;
        try { \DB::connection()->getPdo(); } catch (\Throwable $e) { $up = 0; }

        $body  = "# HELP recloset_up Service availability (1=up)\n";
        $body .= "# TYPE recloset_up gauge\n";
        $body .= "recloset_up{service=\"$svc\"} $up\n";
        $body .= "# HELP recloset_http_requests_total Total HTTP requests handled\n";
        $body .= "# TYPE recloset_http_requests_total counter\n";
        $body .= "recloset_http_requests_total{service=\"$svc\"} $req\n";
        $body .= "# HELP recloset_http_errors_total Total 5xx responses\n";
        $body .= "# TYPE recloset_http_errors_total counter\n";
        $body .= "recloset_http_errors_total{service=\"$svc\"} $err\n";
        $body .= "# HELP recloset_http_response_ms_avg Average response time (ms)\n";
        $body .= "# TYPE recloset_http_response_ms_avg gauge\n";
        $body .= "recloset_http_response_ms_avg{service=\"$svc\"} " . round($avg, 2) . "\n";
        return response($body, 200)->header('Content-Type', 'text/plain; version=0.0.4');
    }
}
