<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ClientBriefAttachmentFile implements ValidationRule
{
    private const MIME_TYPES = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'docx' => [
            'application/zip',
            'application/x-zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'xlsx' => [
            'application/zip',
            'application/x-zip',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'pptx' => [
            'application/zip',
            'application/x-zip',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ],
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('File gagal diunggah atau melebihi batas runtime PHP.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mimeType = $value->getMimeType();

        if (
            ! isset(self::MIME_TYPES[$extension])
            || ! is_string($mimeType)
            || ! in_array($mimeType, self::MIME_TYPES[$extension], true)
            || ! $this->hasExpectedSignature($value, $extension)
        ) {
            $fail('Jenis file tidak didukung atau ekstensi tidak sesuai dengan konten file.');

            return;
        }

        if (in_array($extension, ['docx', 'xlsx', 'pptx'], true) && ! $this->isValidOfficeDocument($value, $extension)) {
            $fail('Dokumen Office tidak valid atau mengandung konten macro yang tidak diizinkan.');
        }
    }

    private function hasExpectedSignature(UploadedFile $file, string $extension): bool
    {
        $header = file_get_contents($file->getRealPath(), false, null, 0, 1024);

        if (! is_string($header)) {
            return false;
        }

        return match ($extension) {
            'pdf' => str_starts_with($header, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($header, "\xFF\xD8\xFF"),
            'png' => str_starts_with($header, "\x89PNG\r\n\x1A\n"),
            'webp' => strlen($header) >= 12
                && substr($header, 0, 4) === 'RIFF'
                && substr($header, 8, 4) === 'WEBP',
            'docx', 'xlsx', 'pptx' => str_starts_with($header, "PK\x03\x04"),
        };
    }

    private function isValidOfficeDocument(UploadedFile $file, string $extension): bool
    {
        $archive = new ZipArchive;

        if ($archive->open($file->getRealPath()) !== true) {
            return false;
        }

        try {
            $contentTypesEntry = $archive->statName('[Content_Types].xml');

            if (
                $archive->numFiles > 10_000
                || ! is_array($contentTypesEntry)
                || ($contentTypesEntry['size'] ?? PHP_INT_MAX) > 1_048_576
            ) {
                return false;
            }

            $contentTypes = $archive->getFromName('[Content_Types].xml');
            $requiredDocument = match ($extension) {
                'docx' => 'word/document.xml',
                'xlsx' => 'xl/workbook.xml',
                'pptx' => 'ppt/presentation.xml',
            };
            $requiredContentType = match ($extension) {
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml',
            };

            if (
                ! is_string($contentTypes)
                || str_contains(strtolower($contentTypes), 'macroenabled')
                || ! str_contains($contentTypes, $requiredContentType)
                || $archive->locateName($requiredDocument) === false
            ) {
                return false;
            }

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = $archive->getNameIndex($index);

                if (is_string($name) && str_ends_with(strtolower($name), 'vbaproject.bin')) {
                    return false;
                }
            }

            return true;
        } finally {
            $archive->close();
        }
    }
}
