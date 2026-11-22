<?php

namespace Tests\Feature;

use App\Models\ClientBrief;
use App\Models\ClientBriefAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Prod02AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_owner_can_upload_private_file_and_records_attachment_audit(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);

        $response = $this->actingAs($user)->post(route('prod02.attachments.store', $brief), [
            'attachment' => UploadedFile::fake()->image('brief.png'),
            'category' => 'Reference',
            'notes' => 'Approved visual reference',
        ]);

        $response->assertRedirect(route('prod02.edit', ['brief' => $brief, 'step' => 4]));
        $attachment = ClientBriefAttachment::query()->sole();
        $this->assertSame('brief.png', $attachment->original_name);
        $this->assertSame('Reference', $attachment->category);
        $this->assertSame('Approved visual reference', $attachment->notes);
        $this->assertSame('image/png', $attachment->mime_type);
        $this->assertStringStartsWith("production/client-briefs/{$brief->id}/attachments/", $attachment->storage_path);
        $this->assertNotSame('brief.png', basename($attachment->storage_path));
        Storage::disk('local')->assertExists($attachment->storage_path);

        $event = $brief->events()->where('event_type', 'ATTACHMENT_UPLOADED')->sole();
        $this->assertSame($attachment->id, $event->after_data['attachment_id']);
        $this->assertSame('png', $event->after_data['extension']);
        $this->assertArrayNotHasKey('storage_path', $event->after_data);
        $this->assertDatabaseMissing('client_brief_events', [
            'client_brief_id' => $brief->id,
            'event_type' => 'DRAFT_UPDATED',
        ]);
        $this->assertSame('DRAFT', $brief->fresh()->status);
    }

    public function test_upload_rejects_unsupported_extensions_and_mismatched_mime(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);

        $this->actingAs($user)
            ->post(route('prod02.attachments.store', $brief), [
                'attachment' => UploadedFile::fake()->createWithContent('active.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            ])
            ->assertSessionHasErrors('attachment');

        $this->post(route('prod02.attachments.store', $brief), [
            'attachment' => UploadedFile::fake()->createWithContent('not-a-pdf.pdf', '<html>not a PDF</html>'),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('client_brief_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_upload_accepts_valid_office_package_and_rejects_macro_enabled_package(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);

        $this->actingAs($user)
            ->post(route('prod02.attachments.store', $brief), [
                'attachment' => $this->officeDocument('brief.docx'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('client_brief_attachments', [
            'client_brief_id' => $brief->id,
            'original_name' => 'brief.docx',
        ]);

        $this->post(route('prod02.attachments.store', $brief), [
            'attachment' => $this->officeDocument('macro.docx', true),
        ])->assertSessionHasErrors('attachment');

        $this->assertDatabaseCount('client_brief_attachments', 1);
    }

    public function test_non_marketing_and_non_owner_users_cannot_access_an_attachment(): void
    {
        Storage::fake('local');
        $owner = $this->createMarketingUser('owner@example.test');
        $otherMarketing = $this->createMarketingUser('other@example.test');
        $otherRoleUser = $this->createUser('other-role@example.test');
        $brief = $this->createBrief($owner);
        $attachment = $this->createAttachment($brief, $owner);

        $this->actingAs($otherMarketing)
            ->get(route('prod02.attachments.download', [$brief, $attachment]))
            ->assertNotFound();

        $this->actingAs($otherMarketing)
            ->delete(route('prod02.attachments.destroy', [$brief, $attachment]))
            ->assertNotFound();

        $this->actingAs($otherRoleUser)
            ->get(route('prod02.attachments.download', [$brief, $attachment]))
            ->assertForbidden();

        $this->assertNull($attachment->fresh()->deleted_at);
    }

    public function test_non_marketing_user_cannot_upload_or_soft_delete_an_attachment(): void
    {
        Storage::fake('local');
        $owner = $this->createMarketingUser();
        $otherRoleUser = $this->createUser('other-role@example.test');
        $brief = $this->createBrief($owner);
        $attachment = $this->createAttachment($brief, $owner);
        Storage::disk('local')->put($attachment->storage_path, 'private document');

        $this->actingAs($otherRoleUser)
            ->post(route('prod02.attachments.store', $brief), [
                'attachment' => UploadedFile::fake()->image('unauthorized.png'),
            ])
            ->assertForbidden();

        $this->delete(route('prod02.attachments.destroy', [$brief, $attachment]))
            ->assertForbidden();

        $this->assertDatabaseCount('client_brief_attachments', 1);
        $this->assertNull($attachment->fresh()->deleted_at);
        Storage::disk('local')->assertExists($attachment->storage_path);
        $this->assertSame([$attachment->storage_path], Storage::disk('local')->allFiles());
    }

    public function test_attachment_endpoints_require_authentication_and_attachment_brief_match(): void
    {
        Storage::fake('local');
        $owner = $this->createMarketingUser();
        $brief = $this->createBrief($owner);
        $otherBrief = $this->createBrief($owner);
        $attachment = $this->createAttachment($brief, $owner);

        $this->get(route('prod02.attachments.download', [$brief, $attachment]))
            ->assertRedirect(route('login'));
        $this->post(route('prod02.attachments.store', $brief))
            ->assertRedirect(route('login'));
        $this->delete(route('prod02.attachments.destroy', [$brief, $attachment]))
            ->assertRedirect(route('login'));

        $this->actingAs($owner)
            ->get(route('prod02.attachments.download', [$otherBrief, $attachment]))
            ->assertNotFound();
        $this->delete(route('prod02.attachments.destroy', [$otherBrief, $attachment]))
            ->assertNotFound();

        $this->assertNull($attachment->fresh()->deleted_at);
    }

    public function test_attachment_download_is_private_and_remains_available_after_submission(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user, 'RECORDED');
        $attachment = $this->createAttachment($brief, $user);
        Storage::disk('local')->put($attachment->storage_path, 'private document');

        $this->actingAs($user)
            ->get(route('prod02.attachments.download', [$brief, $attachment]))
            ->assertOk()
            ->assertDownload('brief.pdf');

        $this->post(route('prod02.attachments.store', $brief), [
            'attachment' => UploadedFile::fake()->image('another.png'),
        ])->assertForbidden();
        $this->delete(route('prod02.attachments.destroy', [$brief, $attachment]))
            ->assertForbidden();

        $this->assertDatabaseCount('client_brief_attachments', 1);
        $this->assertSame('RECORDED', $brief->fresh()->status);
    }

    public function test_soft_deleted_attachment_is_not_listed_or_downloadable_and_file_is_retained(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);
        $attachment = $this->createAttachment($brief, $user);
        Storage::disk('local')->put($attachment->storage_path, 'retained private file');

        $this->actingAs($user)
            ->delete(route('prod02.attachments.destroy', [$brief, $attachment]))
            ->assertRedirect(route('prod02.edit', ['brief' => $brief, 'step' => 4]));

        $deletedAttachment = ClientBriefAttachment::withTrashed()->findOrFail($attachment->id);
        $this->assertNotNull($deletedAttachment->deleted_at);
        $this->assertSame($user->id, $deletedAttachment->deleted_by);
        Storage::disk('local')->assertExists($attachment->storage_path);

        $this->get(route('prod02.attachments.download', [$brief, $attachment]))->assertNotFound();
        $this->get(route('prod02.edit', ['brief' => $brief, 'step' => 4]))
            ->assertOk()
            ->assertDontSee('brief.pdf');
        $this->assertDatabaseHas('client_brief_events', [
            'client_brief_id' => $brief->id,
            'event_type' => 'ATTACHMENT_DELETED',
            'actor_user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('client_brief_events', [
            'client_brief_id' => $brief->id,
            'event_type' => 'DRAFT_UPDATED',
        ]);
    }

    public function test_audit_failure_rolls_back_metadata_and_compensates_private_file(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);
        Schema::drop('client_brief_events');

        $response = $this->actingAs($user)->post(route('prod02.attachments.store', $brief), [
            'attachment' => UploadedFile::fake()->image('brief.png'),
        ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('client_brief_attachments', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_soft_delete_audit_failure_rolls_back_metadata_and_retains_private_file(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);
        $attachment = $this->createAttachment($brief, $user);
        Storage::disk('local')->put($attachment->storage_path, 'retained private file');

        DB::connection()->beforeExecuting(static function (string $query): void {
            if (str_starts_with(strtolower(ltrim($query)), 'insert into "client_brief_events"')) {
                throw new RuntimeException('Simulated attachment audit failure.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)
                ->delete(route('prod02.attachments.destroy', [$brief, $attachment]));

            self::fail('The simulated audit failure should interrupt soft-delete.');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated attachment audit failure.', $exception->getMessage());
        }

        $unchangedAttachment = ClientBriefAttachment::withTrashed()->findOrFail($attachment->id);
        $this->assertNull($unchangedAttachment->deleted_at);
        $this->assertNull($unchangedAttachment->deleted_by);
        $this->assertDatabaseMissing('client_brief_events', [
            'client_brief_id' => $brief->id,
            'event_type' => 'ATTACHMENT_DELETED',
        ]);
        Storage::disk('local')->assertExists($attachment->storage_path);
    }

    public function test_edit_page_lists_active_attachment_details(): void
    {
        Storage::fake('local');
        $user = $this->createMarketingUser();
        $brief = $this->createBrief($user);
        $attachment = $this->createAttachment($brief, $user);

        $this->actingAs($user)
            ->get(route('prod02.edit', ['brief' => $brief, 'step' => 4]))
            ->assertOk()
            ->assertSee('brief.pdf')
            ->assertSee('Reference')
            ->assertSee('Marketing Owner');

        $this->assertNull($attachment->deleted_at);
    }

    private function createMarketingUser(string $email = 'marketing@example.test'): User
    {
        $user = $this->createUser($email, 'Marketing Owner');
        $roleId = DB::table('bpm_roles')->where('code', 'MARKETING')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('bpm_roles')->insertGetId([
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('bpm_role_user')->insert([
            'user_id' => $user->id,
            'bpm_role_id' => $roleId,
        ]);

        return $user;
    }

    private function createUser(string $email, string $name = 'Test User'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('test-password'),
        ]);
    }

    private function createBrief(User $owner, string $status = 'DRAFT'): ClientBrief
    {
        $brief = new ClientBrief([
            'client_name' => 'Test Client',
            'communication_source' => 'Email',
            'requirement_type' => 'Reference',
            'description' => 'Test request',
        ]);
        $brief->marketing_user_id = $owner->id;
        $brief->status = $status;
        $brief->save();

        return $brief;
    }

    private function createAttachment(ClientBrief $brief, User $uploader): ClientBriefAttachment
    {
        return $brief->attachments()->create([
            'original_name' => 'brief.pdf',
            'storage_path' => "production/client-briefs/{$brief->id}/attachments/server-generated.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'uploaded_by' => $uploader->id,
            'category' => 'Reference',
            'notes' => 'Reference file',
        ]);
    }

    private function officeDocument(string $filename, bool $withMacro = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'prod02-office-');
        $archive = new \ZipArchive;

        if ($path === false || $archive->open($path, \ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create an isolated Office test document.');
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
                throw new RuntimeException('Could not read the isolated Office test document.');
            }

            return UploadedFile::fake()->createWithContent($filename, $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
