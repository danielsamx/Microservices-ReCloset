<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\MediaFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'image/gif' => 'gif', 'video/mp4' => 'mp4', 'video/webm' => 'webm',
    ];

    private function root(): string { return rtrim(env('MEDIA_STORAGE_ROOT', '/var/blobstore'), '/'); }
    private function internalOk(Request $r): bool
    {
        return hash_equals((string) env('INTERNAL_SERVICE_TOKEN'), (string) $r->header('X-Internal-Token'));
    }

    // Store a file on the persistent blob volume (called by Item Service only)
    public function store(Request $request)
    {
        if (!$this->internalOk($request)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);

        $maxKb = (int) (env('MEDIA_MAX_FILE_SIZE', 52428800) / 1024);
        $request->validate([
            'file' => ['required', 'file', "max:$maxKb", 'mimetypes:' . implode(',', array_keys(self::ALLOWED))],
            'item_id' => ['nullable', 'integer'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        if (!isset(self::ALLOWED[$mime])) {
            return response()->json(['message' => 'Ese tipo de archivo no está permitido. Usa JPG, PNG, WEBP, GIF, MP4 o WEBM.'], 422);
        }
        $ext = self::ALLOWED[$mime];                      // trust server-detected mime, not client ext

        $id = (string) Str::uuid();                        // unique id -> no collisions
        $shardDir = $this->root() . '/' . date('Y') . '/' . date('m') . '/' . substr($id, 0, 2);
        if (!is_dir($shardDir)) mkdir($shardDir, 0775, true);

        $relative = date('Y') . '/' . date('m') . '/' . substr($id, 0, 2) . '/' . $id . '.' . $ext;
        $absolute = $this->root() . '/' . $relative;

        $checksum = hash_file('sha256', $file->getRealPath());
        $file->move(dirname($absolute), basename($absolute));  // atomic move, no overwrite (uuid)

        $media = MediaFile::create([
            'id' => $id,
            'item_id' => $request->integer('item_id') ?: null,
            'original_name' => Str::limit(preg_replace('/[^\w.\- ]/', '_', $file->getClientOriginalName()), 180, ''),
            'mime' => $mime,
            'extension' => $ext,
            'size' => filesize($absolute),
            'path' => $relative,
            'checksum' => $checksum,
        ]);

        Log::info('media.stored', ['media_id' => $id, 'item_id' => $media->item_id, 'size' => $media->size]);
        return response()->json(['media' => [
            'id' => $media->id,
            'url' => '/api/media/' . $media->id . '/raw',
            'mime' => $media->mime,
            'size' => $media->size,
            'original_name' => $media->original_name,
        ]], 201);
    }

    // Public retrieval (browsers load images/videos through the gateway)
    public function raw(string $id)
    {
        $media = MediaFile::find($id);
        if (!$media) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        $absolute = $this->root() . '/' . $media->path;
        if (!is_file($absolute)) return response()->json(['message' => 'El archivo ya no está disponible.'], 404);

        return response()->file($absolute, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    // Metadata (internal)
    public function meta(Request $request, string $id)
    {
        if (!$this->internalOk($request)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        $media = MediaFile::find($id);
        if (!$media) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        return response()->json(['media' => $media]);
    }

    // Delete a single file (internal)
    public function destroy(Request $request, string $id)
    {
        if (!$this->internalOk($request)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        $media = MediaFile::find($id);
        if (!$media) return response()->json(['message' => 'No encontramos ese recurso.'], 404);
        $this->unlink($media);
        $media->delete();
        return response()->json(['message' => 'Eliminado correctamente.']);
    }

    // Delete every file for an item (internal) - used when a prenda is removed
    public function destroyByItem(Request $request, int $itemId)
    {
        if (!$this->internalOk($request)) return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        $files = MediaFile::where('item_id', $itemId)->get();
        foreach ($files as $m) { $this->unlink($m); $m->delete(); }
        Log::info('media.deleted_by_item', ['item_id' => $itemId, 'count' => $files->count()]);
        return response()->json(['message' => 'Eliminado correctamente.', 'count' => $files->count()]);
    }

    private function unlink(MediaFile $m): void
    {
        $absolute = $this->root() . '/' . $m->path;
        if (is_file($absolute)) @unlink($absolute);
    }
}
