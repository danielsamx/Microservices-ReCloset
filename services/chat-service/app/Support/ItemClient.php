<?php
namespace App\Support;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class ItemClient
{
    private string $base; private string $secret;
    public function __construct()
    {
        $this->base = rtrim(env('ITEM_SERVICE_URL', 'http://item-service:8000'), '/');
        $this->secret = (string) env('INTERNAL_SERVICE_TOKEN');
    }
    public function find(int $itemId): ?array
    {
        for ($i = 1; $i <= 3; $i++) {
            try {
                $res = Http::timeout(4)->withHeaders(['X-Internal-Token' => $this->secret])
                    ->get($this->base . '/api/items/internal/' . $itemId);
                if ($res->status() === 404) return null;
                if ($res->successful()) return $res->json('item');
            } catch (\Throwable $e) {
                Log::error('item.lookup_error', ['attempt'=>$i,'error'=>$e->getMessage()]);
            }
            usleep(200000 * $i);
        }
        return null;
    }
}
