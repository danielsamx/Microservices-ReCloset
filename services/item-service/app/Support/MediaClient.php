<?php
namespace App\Support;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Item Service NEVER touches the media volume directly. All media operations
 * go through the Media Storage Service HTTP API. Retry pattern guards against
 * transient failures.
 */
class MediaClient
{
    private string $base;
    private string $secret;

    public function __construct()
    {
        $this->base = rtrim(env('MEDIA_SERVICE_URL', 'http://media-service:8000'), '/');
        $this->secret = (string) env('INTERNAL_SERVICE_TOKEN');
    }

    /** Store one file, associated with an item. Returns media descriptor or null. */
    public function store(UploadedFile $file, int $itemId): ?array
    {
        for ($i = 1; $i <= 3; $i++) {
            try {
                $res = Http::timeout(20)
                    ->withHeaders(['X-Internal-Token' => $this->secret])
                    ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                    ->post($this->base . '/api/media', ['item_id' => $itemId]);
                if ($res->successful()) return $res->json('media');
                if ($res->status() === 422) {         // validation - do not retry
                    Log::warning('media.store_rejected', ['status' => 422, 'body' => $res->json()]);
                    return null;
                }
            } catch (\Throwable $e) {
                Log::error('media.store_error', ['attempt' => $i, 'error' => $e->getMessage()]);
            }
            usleep(250000 * $i);
        }
        return null;
    }

    /** Delete every file associated with an item. */
    public function deleteByItem(int $itemId): bool
    {
        for ($i = 1; $i <= 3; $i++) {
            try {
                $res = Http::timeout(15)
                    ->withHeaders(['X-Internal-Token' => $this->secret])
                    ->delete($this->base . '/api/media/by-item/' . $itemId);
                if ($res->successful()) return true;
            } catch (\Throwable $e) {
                Log::error('media.delete_error', ['attempt' => $i, 'error' => $e->getMessage()]);
            }
            usleep(250000 * $i);
        }
        return false;
    }
}
