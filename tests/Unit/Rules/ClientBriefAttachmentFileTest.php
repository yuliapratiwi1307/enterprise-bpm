<?php

namespace Tests\Unit\Rules;

use App\Rules\ClientBriefAttachmentFile;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ClientBriefAttachmentFileTest extends TestCase
{
    public function test_accepts_a_png_with_matching_detected_mime(): void
    {
        $file = UploadedFile::fake()->image('brief.png');

        self::assertSame([], $this->validationErrors($file));
    }

    public function test_rejects_an_unsupported_extension_and_mime_mismatch(): void
    {
        $svg = UploadedFile::fake()->createWithContent('brief.svg', '<svg></svg>');
        self::assertNotSame([], $this->validationErrors($svg));

        $htmlNamedPdf = UploadedFile::fake()->createWithContent('brief.pdf', '<html>not a PDF</html>');
        self::assertNotSame([], $this->validationErrors($htmlNamedPdf));
    }

    public function test_accepts_a_valid_non_macro_office_package(): void
    {
        $file = $this->officeDocument('brief.docx');

        self::assertSame([], $this->validationErrors($file));
    }

    public function test_rejects_macro_enabled_office_packages(): void
    {
        $file = $this->officeDocument('brief.docx', true);

        self::assertNotSame([], $this->validationErrors($file));
    }

    /**
     * @return list<string>
     */
    private function validationErrors(UploadedFile $file): array
    {
        $errors = [];

        try {
            (new ClientBriefAttachmentFile)->validate(
                'attachment',
                $file,
                static function (string $message) use (&$errors): void {
                    $errors[] = $message;
                },
            );
        } finally {
            if (is_file($file->getPathname())) {
                unlink($file->getPathname());
            }
        }

        return $errors;
    }

    private function officeDocument(string $filename, bool $withMacro = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'prod02-office-unit-');
        $archive = new ZipArchive;

        if ($path === false || $archive->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create an isolated Office test document.');
        }

        try {
            $contentTypes = '<Types><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
                .($withMacro ? '<Override PartName="/word/vbaProject.bin" ContentType="application/vnd.ms-office.vbaProject"/>' : '')
                .'</Types>';
            $archive->addFromString('[Content_Types].xml', $contentTypes);
            $archive->addFromString('word/document.xml', '<document/>');

            if ($withMacro) {
                $archive->addFromString('word/vbaProject.bin', 'macro');
            }

            $archive->close();
            $contents = file_get_contents($path);

            if (! is_string($contents)) {
                throw new \RuntimeException('Could not read the isolated Office test document.');
            }

            return UploadedFile::fake()->createWithContent($filename, $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
