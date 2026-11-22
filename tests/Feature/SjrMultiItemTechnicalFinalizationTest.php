<?php

namespace Tests\Feature;

use App\Models\BpmRole;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SjrMultiItemTechnicalFinalizationTest extends TestCase
{
    use DatabaseTransactions;

    private function userWithRole(string $code): User
    {
        $user = User::query()->create([
            'name' => 'SJR Finalization Test',
            'email' => Str::uuid().'@sjr-finalization.test',
            'password' => 'test-password',
        ]);
        $role = BpmRole::query()->firstOrCreate(['code' => $code], ['name' => str_replace('_', ' ', $code)]);
        $user->roles()->attach($role);
        return $user;
    }

    /** @return array{job_number:string,handoff_id:int,item_ids:array<int,int>} */
    private function fixture(int $count = 2, bool $clear = true): array
    {
        $now = now();
        $jobNumber = 'SJR-TEST-'.Str::upper(Str::random(16));
        $userId = $this->userWithRole('MARKETING')->id;
        $jobId = DB::table('sjr_jobs')->insertGetId([
            'job_number' => $jobNumber, 'marketing_user_id' => $userId,
            'client_name' => 'Client Pengujian', 'job_title' => 'Finalisasi Multi Item',
            'target_date' => today()->addDays(30)->toDateString(),
            'brief_details' => 'Brief uji otomatis', 'quantity' => '2',
            'requires_designer' => false, 'status' => 'IN_PRODUCTION',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $ids = []; $snapshot = [];
        for ($i = 1; $i <= $count; $i++) {
            $id = DB::table('sjr_job_items')->insertGetId([
                'sjr_job_id' => $jobId, 'item_index' => $i,
                'item_name' => 'Item '.$i, 'dimensions' => '100 x 50',
                'dimension_unit' => 'cm', 'quantity' => 1,
                'requires_designer' => false, 'design_state' => 'NOT_REQUIRED',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $ids[] = $id;
            $snapshot[] = ['item_id' => $id, 'index' => $i, 'item_name' => 'Item '.$i];
        }
        $handoffId = DB::table('sjr_multi_item_handoffs')->insertGetId([
            'sjr_job_id' => $jobId, 'sent_by' => $userId,
            'item_snapshot_json' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'sent_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sjr_multi_item_brief_reviews')->insert([
            'handoff_id' => $handoffId, 'decision' => $clear ? 'CLEAR' : 'NEED_CLARIFICATION',
            'reviewed_by' => $userId, 'reviewed_at' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ($ids as $id) {
            DB::table('sjr_multi_item_technical_drafts')->insert([
                'handoff_id' => $handoffId, 'sjr_job_item_id' => $id,
                'materials_json' => json_encode([[
                    'name' => 'Material uji', 'spec' => 'Spesifikasi lengkap',
                    'quantity' => 2, 'unit' => 'lembar',
                ]], JSON_THROW_ON_ERROR),
                'processes_json' => json_encode([[
                    'name' => 'Potong', 'unit' => 'pcs', 'quantity' => 2, 'unit_price' => 5000,
                ]], JSON_THROW_ON_ERROR),
                'instructions' => 'Instruksi produksi lengkap',
                'leadtime_days' => 5, 'leadtime_note' => 'Lima hari kerja',
                'saved_by' => $userId, 'saved_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        return ['job_number' => $jobNumber, 'handoff_id' => $handoffId, 'item_ids' => $ids];
    }

    private function finalizeUrl(string $jobNumber): string
    {
        return route('sjr.production.multi.technical.finalize', ['jobNumber' => $jobNumber]);
    }

    public function test_complete_multi_item_project_finalizes_with_full_snapshot_without_starting_hpp(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->userWithRole('ASST_MANAGER_PRODUKSI'))
            ->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])
            ->assertRedirect();
        $row = DB::table('sjr_multi_item_technical_finalizations')->where('handoff_id', $f['handoff_id'])->first();
        $this->assertNotNull($row);
        $this->assertSame('READY_FOR_HPP', $row->state);
        $this->assertSame(2, (int) $row->item_count);
        $items = json_decode($row->technical_snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(2, $items);
        $this->assertSame($f['item_ids'], array_column($items, 'item_id'));
        $this->assertSame(5, $items[0]['leadtime_days']);
        $this->assertSame('Lima hari kerja', $items[0]['leadtime_note']);
    }

    public function test_incomplete_item_rejects_finalization_atomically(): void
    {
        $f = $this->fixture();
        DB::table('sjr_multi_item_technical_drafts')
            ->where('sjr_job_item_id', $f['item_ids'][1])->update(['leadtime_days' => null]);
        $this->actingAs($this->userWithRole('ASST_MANAGER_PRODUKSI'))
            ->from('/sjr/production/multi-item/'.$f['job_number'])
            ->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])
            ->assertSessionHasErrors('finalize');
        $this->assertSame(0, DB::table('sjr_multi_item_technical_finalizations')
            ->where('handoff_id', $f['handoff_id'])->count());
    }

    public function test_repeat_finalization_is_rejected(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->userWithRole('ASST_MANAGER_PRODUKSI'));
        $this->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])->assertRedirect();
        $this->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])->assertSessionHasErrors('finalize');
        $this->assertSame(1, DB::table('sjr_multi_item_technical_finalizations')
            ->where('handoff_id', $f['handoff_id'])->count());
    }

    public function test_saved_draft_cannot_be_changed_after_finalization(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->userWithRole('ASST_MANAGER_PRODUKSI'));
        $this->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])->assertRedirect();
        $before = DB::table('sjr_multi_item_technical_drafts')
            ->where('sjr_job_item_id', $f['item_ids'][0])->first();
        $this->post(route('sjr.production.multi.technical.save', [
            'jobNumber' => $f['job_number'], 'itemId' => $f['item_ids'][0],
        ]), [
            'materials' => [], 'processes' => [], 'instructions' => 'Tidak boleh berubah',
        ])->assertSessionHasErrors('materials');
        $after = DB::table('sjr_multi_item_technical_drafts')
            ->where('sjr_job_item_id', $f['item_ids'][0])->first();
        $this->assertSame($before->materials_json, $after->materials_json);
        $this->assertSame($before->processes_json, $after->processes_json);
        $this->assertSame($before->instructions, $after->instructions);
        $this->assertSame($before->saved_at, $after->saved_at);
    }

    public function test_unauthorized_user_cannot_finalize(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->userWithRole('MARKETING'))
            ->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])
            ->assertForbidden();
        $this->assertSame(0, DB::table('sjr_multi_item_technical_finalizations')
            ->where('handoff_id', $f['handoff_id'])->count());
    }


    public function test_new_material_request_is_created_after_technical_finalization(): void
    {
        $f = $this->fixture();

        $material = [
            'name' => 'Plat Aluminium 2 mm',
            'spec' => 'Aluminium, tebal 2 mm, ukuran 120 x 240 cm',
            'quantity' => 5,
            'unit' => 'lembar',
            'source_type' => 'new_material_request',
            'request_reason' => 'Material belum tersedia di MM-05.',
        ];

        DB::table('sjr_multi_item_technical_drafts')
            ->where('handoff_id', $f['handoff_id'])
            ->where('sjr_job_item_id', $f['item_ids'][0])
            ->update([
                'materials_json' => json_encode([$material], JSON_THROW_ON_ERROR),
            ]);

        $this->actingAs($this->userWithRole('ASST_MANAGER_PRODUKSI'))
            ->post($this->finalizeUrl($f['job_number']), ['confirm' => '1'])
            ->assertRedirect();

        $requests = DB::table('sjr_material_requests')
            ->where('handoff_id', $f['handoff_id'])
            ->get();

        $this->assertCount(1, $requests);

        $request = $requests->first();

        $this->assertSame($f['item_ids'][0], (int) $request->sjr_job_item_id);
        $this->assertSame('Plat Aluminium 2 mm', $request->material_name);
        $this->assertSame('SUBMITTED', $request->status);
        $this->assertNotEmpty($request->request_no);

        $payload = json_decode(
            $request->material_payload_json,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('new_material_request', $payload['source_type']);
        $this->assertSame(
            'Material belum tersedia di MM-05.',
            $payload['request_reason']
        );
        $this->assertEquals(5, $payload['quantity']);
    }

}
