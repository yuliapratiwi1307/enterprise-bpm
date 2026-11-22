<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use App\Services\BpmRoleAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Prod02ReferenceImageController extends Controller
{
    private const MAX_FILE_KB = 10240;

    private const MAX_ACTIVE = 10;

    private function owner(Request $request, ClientBrief $brief, BpmRoleAuthorization $roles, bool $draftOnly): void
    {
        $roles->require($request->user(), 'MARKETING');
        abort_unless((int) $brief->marketing_user_id === (int) $request->user()->id, 404);
        if ($draftOnly) {
            abort_unless($brief->status === 'DRAFT', 403);
        }
    }

    public function store(Request $request, ClientBrief $brief, BpmRoleAuthorization $roles): JsonResponse
    {
        $this->owner($request, $brief, $roles, true);
        $data = $request->validate([
            'reference_image' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.self::MAX_FILE_KB],
        ]);
        $existing = DB::table('client_brief_reference_images')
            ->where('client_brief_id', $brief->getKey())->whereNull('deleted_at')->count();
        if ($existing >= self::MAX_ACTIVE) {
            throw ValidationException::withMessages(['reference_image' => 'Maksimal 10 gambar referensi aktif per Draf.']);
        }
        $file = $data['reference_image'];
        $path = $file->store('production/client-briefs/'.$brief->getKey().'/reference-images', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['reference_image' => 'Gagal menyimpan gambar privat.']);
        }
        try {
            $id = DB::table('client_brief_reference_images')->insertGetId([
                'client_brief_id' => $brief->getKey(),
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json([
            'id' => $id,
            'name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
            'size' => $file->getSize(),
            'url' => route('prod02.reference-images.show', ['brief' => $brief, 'image' => $id]),
            'delete_url' => route('prod02.reference-images.destroy', ['brief' => $brief, 'image' => $id]),
        ], 201);
    }

    public function show(Request $request, ClientBrief $brief, int $image, BpmRoleAuthorization $roles): StreamedResponse
    {
        $this->owner($request, $brief, $roles, false);
        $row = DB::table('client_brief_reference_images')
            ->where('client_brief_id', $brief->getKey())->where('id', $image)
            ->whereNull('deleted_at')->first();
        abort_unless($row, 404);
        $prefix = 'production/client-briefs/'.$brief->getKey().'/reference-images/';
        abort_unless(str_starts_with($row->storage_path, $prefix)
            && ! str_contains($row->storage_path, '\\')
            && ! in_array('..', explode('/', $row->storage_path), true), 404);
        abort_unless(in_array($row->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_unless(Storage::disk('local')->exists($row->storage_path), 404);

        return Storage::disk('local')->response($row->storage_path, null, [
            'Content-Type' => $row->mime_type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, ClientBrief $brief, int $image, BpmRoleAuthorization $roles): JsonResponse
    {
        $this->owner($request, $brief, $roles, true);
        $count = DB::table('client_brief_reference_images')
            ->where('client_brief_id', $brief->getKey())->where('id', $image)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'deleted_by' => $request->user()->id, 'updated_at' => now()]);
        abort_unless($count === 1, 404);

        return response()->json(['message' => 'Gambar dihapus dari daftar aktif; berkas privat dipertahankan untuk retensi.']);
    }
}
