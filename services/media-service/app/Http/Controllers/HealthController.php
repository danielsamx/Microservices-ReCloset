<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
class HealthController extends Controller
{
    public function health()
    {
        $db = 'up';
        try { DB::connection()->getPdo(); } catch (\Throwable $e) { $db = 'down'; }
        $status = $db === 'up' ? 200 : 503;
        return response()->json([
            'status' => $db === 'up' ? 'ok' : 'degraded',
            'service' => env('APP_NAME', 'recloset'),
            'checks' => ['database' => $db],
            'time' => now()->toIso8601String(),
        ], $status);
    }
}
