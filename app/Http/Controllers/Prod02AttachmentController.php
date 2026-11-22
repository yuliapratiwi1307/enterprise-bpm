<?php

namespace App\Http\Controllers;

use App\Exceptions\AttachmentUploadFailed;
use App\Models\ClientBrief;
use App\Models\ClientBriefAttachment;
use App\Rules\ClientBriefAttachmentFile;
use App\Services\BpmRoleAuthorization;
use App\Services\ClientBriefAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Prod02AttachmentController extends Controller
{
    private const MAXIMUM_ATTACHMENT_SIZE_KILOBYTES = 204_800;

    public function store(
        Request $request,
        ClientBrief $brief,
        BpmRoleAuthorization $roles,
        ClientBriefAttachmentService $attachments,
    ): RedirectResponse {
        $this->authorizeDraftOwner($request, $brief, $roles);
        $this->ensureSchemaIsReady($attachments);
        $this->rejectOversizedRequest($request);

        $maximumFileSizeKilobytes = $attachments->maximumFileSizeKilobytes();

        if ($maximumFileSizeKilobytes < 1) {
            throw ValidationException::withMessages([
                'attachment' => 'Upload tidak aktif karena batas upload PHP pada server tidak mengizinkan file.',
            ]);
        }

        $file = $request->file('attachment');

        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw ValidationException::withMessages([
                'attachment' => $this->uploadErrorMessage($file, $maximumFileSizeKilobytes),
            ]);
        }

        $data = $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:'.self::MAXIMUM_ATTACHMENT_SIZE_KILOBYTES,
                new ClientBriefAttachmentFile,
            ],
            'category' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'attachment.max' => 'Ukuran lampiran tidak boleh melebihi 200 MiB per file.',
        ]);

        try {
            $attachments->upload($brief, $request->user(), $data['attachment'], [
                'category' => $data['category'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (AttachmentUploadFailed $exception) {
            return back()
                ->withInput()
                ->withErrors(['attachment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('prod02.edit', ['brief' => $brief, 'step' => 4])
            ->with('message', 'Lampiran berhasil diunggah.');
    }

    public function download(
        Request $request,
        ClientBrief $brief,
        ClientBriefAttachment $attachment,
        BpmRoleAuthorization $roles,
        ClientBriefAttachmentService $attachments,
    ): StreamedResponse {
        $this->authorizeMarketingOwner($request, $brief, $roles);
        $this->ensureSchemaIsReady($attachments);

        abort_unless($attachment->client_brief_id === $brief->id, 404);

        $activeAttachment = $brief->attachments()
            ->whereKey($attachment->getKey())
            ->firstOrFail();
        $this->assertStoredPathIsScoped($activeAttachment, $brief);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($activeAttachment->storage_path), 404);

        $downloadName = str_replace('\\', '/', $activeAttachment->original_name);
        $downloadName = basename($downloadName);
        $downloadName = preg_replace('/[\x00-\x1F\x7F]/u', '', $downloadName) ?? '';
        abort_if($downloadName === '', 404);

        return $disk->download($activeAttachment->storage_path, $downloadName);
    }

    public function destroy(
        Request $request,
        ClientBrief $brief,
        ClientBriefAttachment $attachment,
        BpmRoleAuthorization $roles,
        ClientBriefAttachmentService $attachments,
    ): RedirectResponse {
        $this->authorizeDraftOwner($request, $brief, $roles);
        $this->ensureSchemaIsReady($attachments);
        abort_unless($attachment->client_brief_id === $brief->id, 404);

        $attachments->softDelete($brief, $attachment, $request->user());

        return redirect()
            ->route('prod02.edit', ['brief' => $brief, 'step' => 4])
            ->with('message', 'Lampiran dihapus dari daftar aktif. File privat dipertahankan untuk retensi.');
    }

    private function authorizeDraftOwner(
        Request $request,
        ClientBrief $brief,
        BpmRoleAuthorization $roles,
    ): void {
        $this->authorizeMarketingOwner($request, $brief, $roles);
        abort_unless($brief->status === 'DRAFT', 403);
    }

    private function authorizeMarketingOwner(
        Request $request,
        ClientBrief $brief,
        BpmRoleAuthorization $roles,
    ): void {
        $user = $request->user();
        $roles->require($user, 'MARKETING');
        abort_unless($brief->marketing_user_id === $user->id, 404);
    }

    private function ensureSchemaIsReady(ClientBriefAttachmentService $attachments): void
    {
        abort_unless(
            $attachments->schemaIsReady(),
            503,
            'Lampiran belum aktif karena migration metadata atau retensi belum tersedia.',
        );
    }

    private function rejectOversizedRequest(Request $request): void
    {
        $postLimit = (int) ini_parse_quantity((string) ini_get('post_max_size'));
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);

        if ($postLimit > 0 && $contentLength > $postLimit) {
            throw ValidationException::withMessages([
                'attachment' => 'Ukuran request melampaui batas post_max_size PHP yang aktif. Pilih file yang lebih kecil; batas bisnis aplikasi adalah 200 MiB per file.',
            ]);
        }
    }

    private function uploadErrorMessage(UploadedFile $file, int $effectiveMaximumFileSizeKilobytes): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File melampaui batas efektif PHP saat ini ('.number_format($effectiveMaximumFileSizeKilobytes / 1024, 1, ',', '.').' MiB per file). Pilih file yang lebih kecil; batas bisnis aplikasi adalah 200 MiB per file.',
            UPLOAD_ERR_PARTIAL => 'Upload terputus sebelum file selesai diterima.',
            UPLOAD_ERR_NO_FILE => 'Pilih file yang akan diunggah.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Server gagal menerima file. Silakan hubungi administrator.',
            default => 'File gagal diunggah. Periksa file lalu coba kembali.',
        };
    }

    private function assertStoredPathIsScoped(
        ClientBriefAttachment $attachment,
        ClientBrief $brief,
    ): void {
        $expectedPrefix = "production/client-briefs/{$brief->getKey()}/attachments/";
        $path = $attachment->storage_path;

        abort_unless(
            str_starts_with($path, $expectedPrefix)
                && ! str_contains($path, '\\')
                && ! str_contains($path, "\0")
                && ! in_array('..', explode('/', $path), true),
            404,
        );
    }
}
