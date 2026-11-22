<?php

namespace App\Services;

use App\Exceptions\AttachmentUploadFailed;
use App\Models\ClientBrief;
use App\Models\ClientBriefAttachment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ClientBriefAttachmentService
{
    private const APPLICATION_MAX_BYTES = 209_715_200;

    private const REQUIRED_COLUMNS = [
        'id',
        'client_brief_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'created_at',
        'updated_at',
        'category',
        'notes',
        'deleted_at',
        'deleted_by',
    ];

    public function schemaIsReady(): bool
    {
        return Schema::hasColumns('client_brief_attachments', self::REQUIRED_COLUMNS);
    }

    public function maximumFileSizeKilobytes(): int
    {
        $uploadLimit = $this->iniLimitInBytes('upload_max_filesize');
        $postLimit = $this->iniLimitInBytes('post_max_size');

        if ($uploadLimit === 0) {
            return 0;
        }

        $maximumBytes = min(self::APPLICATION_MAX_BYTES, $uploadLimit);

        if ($postLimit > 0) {
            $maximumBytes = min($maximumBytes, max(0, $postLimit - 65_536));
        }

        return intdiv($maximumBytes, 1024);
    }

    /**
     * @param  array{category: ?string, notes: ?string}  $metadata
     */
    public function upload(
        ClientBrief $brief,
        User $actor,
        UploadedFile $file,
        array $metadata,
    ): ClientBriefAttachment {
        $this->assertPrivateDisk();

        $extension = strtolower($file->getClientOriginalExtension());
        $directory = "production/client-briefs/{$brief->getKey()}/attachments";
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = "{$directory}/{$filename}";
        $disk = Storage::disk('local');

        try {
            $storedPath = $file->storeAs($directory, $filename, 'local');

            if (! is_string($storedPath) || $storedPath !== $path) {
                throw new RuntimeException('Private attachment storage did not return the expected path.');
            }

            return DB::transaction(function () use ($brief, $actor, $file, $metadata, $path, $extension): ClientBriefAttachment {
                $lockedBrief = ClientBrief::query()->whereKey($brief->getKey())->lockForUpdate()->firstOrFail();

                if ($lockedBrief->marketing_user_id !== $actor->id || $lockedBrief->status !== 'DRAFT') {
                    throw new AuthorizationException;
                }

                $mimeType = $file->getMimeType();

                if (! is_string($mimeType)) {
                    throw new RuntimeException('The uploaded file content type could not be detected.');
                }

                $attachment = $lockedBrief->attachments()->create([
                    'original_name' => $this->safeOriginalName($file, $extension),
                    'storage_path' => $path,
                    'mime_type' => $mimeType,
                    'size_bytes' => $file->getSize(),
                    'uploaded_by' => $actor->id,
                    'category' => $metadata['category'],
                    'notes' => $metadata['notes'],
                ]);

                $lockedBrief->events()->create([
                    'event_type' => 'ATTACHMENT_UPLOADED',
                    'actor_user_id' => $actor->id,
                    'actor_role' => 'MARKETING',
                    'before_data' => null,
                    'after_data' => [
                        'attachment_id' => $attachment->id,
                        'size_bytes' => $attachment->size_bytes,
                        'extension' => $extension,
                    ],
                    'occurred_at' => now(),
                ]);

                return $attachment;
            });
        } catch (Throwable $exception) {
            $this->compensateFailedUpload($disk, $path, $brief, $actor, $exception);
        }
    }

    public function softDelete(ClientBrief $brief, ClientBriefAttachment $attachment, User $actor): void
    {
        DB::transaction(function () use ($brief, $attachment, $actor): void {
            $lockedBrief = ClientBrief::query()->whereKey($brief->getKey())->lockForUpdate()->firstOrFail();

            abort_unless(
                $lockedBrief->marketing_user_id === $actor->id && $lockedBrief->status === 'DRAFT',
                403,
            );

            $lockedAttachment = $lockedBrief->attachments()
                ->whereKey($attachment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAttachment->deleted_by = $actor->id;
            $lockedAttachment->save();
            $lockedAttachment->delete();

            $lockedBrief->events()->create([
                'event_type' => 'ATTACHMENT_DELETED',
                'actor_user_id' => $actor->id,
                'actor_role' => 'MARKETING',
                'before_data' => [
                    'attachment_id' => $lockedAttachment->id,
                    'size_bytes' => $lockedAttachment->size_bytes,
                ],
                'after_data' => [
                    'attachment_id' => $lockedAttachment->id,
                    'deleted_at' => $lockedAttachment->deleted_at?->toIso8601String(),
                ],
                'occurred_at' => now(),
            ]);
        });
    }

    private function assertPrivateDisk(): void
    {
        $root = config('filesystems.disks.local.root');
        $driver = config('filesystems.disks.local.driver');
        $visibility = config('filesystems.disks.local.visibility');
        $publicRoot = realpath(public_path());
        $resolvedRoot = is_string($root) ? realpath($root) : false;

        if ($resolvedRoot === false && is_string($root)) {
            $resolvedParent = realpath(dirname($root));
            $resolvedRoot = is_string($resolvedParent)
                ? $resolvedParent.DIRECTORY_SEPARATOR.basename($root)
                : false;
        }

        abort_if(
            ! is_string($resolvedRoot)
                || ! is_string($publicRoot)
                || $driver !== 'local'
                || $visibility === 'public'
                || $resolvedRoot === $publicRoot
                || str_starts_with($resolvedRoot, rtrim($publicRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),
            500,
            'Private attachment storage is not configured safely.',
        );
    }

    private function safeOriginalName(UploadedFile $file, string $extension): string
    {
        $clientName = str_replace('\\', '/', $file->getClientOriginalName());
        $name = basename($clientName);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = mb_substr(trim($name), 0, 255);

        return $name !== '' ? $name : "attachment.{$extension}";
    }

    private function iniLimitInBytes(string $name): int
    {
        $value = ini_get($name);

        if (! is_string($value) || $value === '') {
            return 0;
        }

        if (trim($value) === '-1') {
            return PHP_INT_MAX;
        }

        return max(0, (int) ini_parse_quantity($value));
    }

    private function compensateFailedUpload(
        FilesystemAdapter $disk,
        string $path,
        ClientBrief $brief,
        User $actor,
        Throwable $exception,
    ): never {
        try {
            $deleted = $disk->delete($path);

            if (! $deleted && $disk->exists($path)) {
                throw new RuntimeException('The private upload could not be removed after a failed transaction.');
            }
        } catch (Throwable $cleanupException) {
            Log::critical('Failed to compensate a PROD-02 attachment upload.', [
                'client_brief_id' => $brief->getKey(),
                'actor_user_id' => $actor->getKey(),
                'exception' => $exception::class,
                'cleanup_exception' => $cleanupException::class,
            ]);

            throw new AttachmentUploadFailed(
                'Upload gagal dan file privat memerlukan pemeriksaan operator.',
                $exception,
            );
        }

        if ($exception instanceof AuthorizationException || $exception instanceof ModelNotFoundException) {
            throw $exception;
        }

        report($exception);

        throw new AttachmentUploadFailed(
            'Upload gagal; metadata dan audit tidak tersimpan.',
            $exception,
        );
    }
}
