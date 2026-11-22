<?php

namespace App\Http\Controllers;

use App\Support\SjrBriefHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** SJR V5.2 multi-item API. Does not change the legacy SJR store/workflow. */
class SjrMultiItemController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless(config('database.default') === 'pgsql' && DB::connection()->getDatabaseName() === 'enterprise_bpm_browser', 403, 'Hanya untuk database pengujian SJR.');
        abort_unless($request->user() && $request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless(Schema::hasColumn('sjr_jobs', 'brief_details_html'), 503, 'Migrasi Rich Text V5.4.2 belum tersedia.');
        abort_unless(Schema::hasTable('sjr_jobs') && Schema::hasTable('sjr_job_items') && Schema::hasTable('sjr_item_previews') && Schema::hasTable('sjr_attachments'), 503, 'Migrasi V5.2 belum tersedia.');
    }

    public function store(Request $request)
    {
        $this->guard($request);
        $input = $request->validate([
            'client' => 'required|string|max:200', 'title' => 'required|string|max:250',
            'pic' => 'nullable|string|max:200', 'deadline' => 'required|date|after_or_equal:today',
            'details' => 'required|string|max:10000', 'details_html' => 'nullable|string|max:50000', 'details_html_b64' => 'nullable|string|max:120000', 'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:100',
            'items.*.name' => 'required|string|max:250',
            'items.*.dimensions' => 'required|string|max:150',
            'items.*.unit' => ['required', 'string', 'max:20'],
            'items.*.quantity' => 'required|integer|min:1|max:1000000',
            'items.*.designer' => 'required|in:yes,no',
            'items.*.designer_brief' => 'nullable|string|max:10000',
            'items.*.designer_deadline' => 'nullable|date',
            'items.*.previews' => 'nullable|array|max:10',
            'items.*.previews.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:204800|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip,ai,psd,eps',
            'attachment_notes' => 'nullable|array|max:5',
            'attachment_notes.*' => 'nullable|string|max:1000',
        ]);
        foreach ($input['items'] as $index => $item) {
            if ($item['designer'] === 'yes' && (trim($item['designer_brief'] ?? '') === '' || empty($item['designer_deadline']))) {
                throw ValidationException::withMessages(["items.$index.designer_brief" => 'Brief dan deadline Designer wajib untuk item yang memerlukan Designer.']);
            }
        }
        // Capture the actual editor HTML before the transaction, not the legacy plaintext field.
        // The base64 companion preserves UTF-8 and helps detect stale/mismatched browser payloads.
        $rawHtml = $input['details_html'] ?? null;
        if (filled($input['details_html_b64'] ?? null)) {
            $decoded = base64_decode($input['details_html_b64'], true);
            if ($decoded === false || ! mb_check_encoding($decoded, 'UTF-8') || strlen($decoded) > 50000) {
                throw ValidationException::withMessages(['details_html' => 'Format HTML tidak valid.']);
            }
            $rawHtml = $decoded;
        }
        $hasHtml = is_string($rawHtml) && trim($rawHtml) !== '';
        $sourceBriefHtml = $hasHtml ? $rawHtml : '<p>'.str_replace("\n", '<br>', e($input['details'])).'</p>';
        $cleanBriefHtml = SjrBriefHtml::clean($sourceBriefHtml);
        // Fail visibly rather than silently claiming rich formatting survived if it was removed.
        $expectedTags = ['b|strong', 'em|i', 'u', 'ul|ol|li', 'a', 'font|span'];
        foreach ($expectedTags as $tags) {
            if (preg_match('~<('.$tags.')\b~i', $sourceBriefHtml) && ! preg_match('~<('.($tags === 'font|span' ? 'span' : $tags).')\b~i', $cleanBriefHtml)) {
                throw ValidationException::withMessages(['details_html' => 'Format Rich Text berubah saat sanitasi.']);
            }
        }
        if (trim(strip_tags($cleanBriefHtml)) === '') {
            throw ValidationException::withMessages(['details_html' => 'HTML brief kosong.']);
        }
        $written = [];
        try {
            $job = DB::transaction(function()use($input, $request, &$written, $cleanBriefHtml) {
                $anyDesigner = collect($input['items'])->contains(fn($i) => $i['designer'] === 'yes');
                $totalQty = array_sum(array_map(fn($i) => (int) $i['quantity'], $input['items']));
                $id = DB::table('sjr_jobs')->insertGetId([
                    'job_number' => (string) Str::uuid(), 'marketing_user_id' => $request->user()->id,
                    'client_name' => $input['client'], 'job_title' => $input['title'],
                    'client_pic' => $input['pic'] ?? null, 'target_date' => $input['deadline'],
                    'brief_details' => $input['details'], 'brief_details_html' => $cleanBriefHtml, 'quantity' => (string) $totalQty,
                    'notes' => $input['notes'] ?? null, 'requires_designer' => $anyDesigner,
                    'designer_brief' => null, 'designer_deadline' => null,
                    // Intentionally NOT forwarded into the legacy Designer/Production flow.
                    'status' => 'MULTI_ITEM_PENDING', 'created_at' => now(), 'updated_at' => now(),
                ]);
                $code = 'SJR-'.now()->format('Y').'-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
                DB::table('sjr_jobs')->where('id', $id)->update(['job_number' => $code]);
                foreach ($input['items'] as $idx => $item) {
                    $needsDesign = $item['designer'] === 'yes';
                    $itemId = DB::table('sjr_job_items')->insertGetId([
                        'sjr_job_id' => $id, 'item_index' => $idx + 1, 'item_name' => $item['name'],
                        'dimensions' => $item['dimensions'], 'dimension_unit' => $item['unit'],
                        'quantity' => $item['quantity'], 'requires_designer' => $needsDesign,
                        'designer_brief' => $needsDesign ? $item['designer_brief'] : null,
                        'designer_deadline' => $needsDesign ? $item['designer_deadline'] : null,
                        'design_state' => $needsDesign ? 'PENDING' : 'NOT_REQUIRED',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    foreach ($request->file("items.$idx.previews", []) as $sort => $file) {
                        $path = $file->storeAs("sjr/item-previews/$id/$itemId", Str::uuid().'.'.strtolower($file->extension()), 'local');
                        if (!$path) {
                            throw new \RuntimeException('Gagal menyimpan preview item.');
                        }
                        $written[] = $path;
                        DB::table('sjr_item_previews')->insert([
                            'sjr_job_item_id' => $itemId, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 250),
                            'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                            'byte_size' => $file->getSize(), 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
                foreach ($request->file('attachments', []) as $index => $file) {
                    $path = $file->storeAs("sjr/attachments/$id", Str::uuid().'.'.strtolower($file->getClientOriginalExtension()), 'local');
                    if (!$path) {
                        throw new \RuntimeException('Gagal menyimpan lampiran.');
                    }
                    $written[] = $path;
                    DB::table('sjr_attachments')->insert([
                        'sjr_job_id' => $id, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 250),
                        'storage_path' => $path, 'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'byte_size' => $file->getSize(), 'attachment_note' => $input['attachment_notes'][$index] ?? null,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                return ['id' => $id, 'code' => $code];
            });
        } catch (\Throwable $e) {
            foreach ($written as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        return response()->json(['message' => 'Project multi-item tersimpan; belum dikirim ke Designer/Produksi.', 'job' => $job, 'state' => 'MULTI_ITEM_PENDING'], 201);
    }

    public function show(Request $request, int $job)
    {
        $this->guard($request);
        $record = DB::table('sjr_jobs')->where('id', $job)->where('marketing_user_id', $request->user()->id)->first();
        abort_unless($record, 404);
        $items = DB::table('sjr_job_items')->where('sjr_job_id', $record->id)->orderBy('item_index')->get();
        $ids = $items->pluck('id')->all();
        $previews = DB::table('sjr_item_previews')->whereIn('sjr_job_item_id', $ids)->orderBy('sort_order')->get(['id', 'sjr_job_item_id', 'original_name', 'byte_size']);

        return response()->json(['job' => $record, 'items' => $items->map(fn($i) => array_merge((array) $i, ['previews' => $previews->where('sjr_job_item_id', $i->id)->values()])), 'attachments' => DB::table('sjr_attachments')->where('sjr_job_id', $job)->get(['id', 'original_name', 'byte_size', 'attachment_note'])]);
    }

    public function preview(Request $request, int $job, int $preview)
    {
        $this->guard($request);
        $record = DB::table('sjr_item_previews as p')
            ->join('sjr_job_items as i', 'i.id', '=', 'p.sjr_job_item_id')
            ->join('sjr_jobs as j', 'j.id', '=', 'i.sjr_job_id')
            ->where('j.id', $job)->where('j.marketing_user_id', $request->user()->id)
            ->where('p.id', $preview)->select('p.*')->first();
        abort_unless($record && Storage::disk('local')->exists($record->storage_path), 404);

        return response()->file(Storage::disk('local')->path($record->storage_path), ['Content-Type' => $record->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
