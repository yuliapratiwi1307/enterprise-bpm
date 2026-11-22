<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use Illuminate\Http\Request;

/** Tahap 2G: only supplies suggested input values, never persists or creates PROD-03. */
class MarketingWorkspacePrefillController extends Controller
{
    public function show(Request $request, ClientBrief $draft)
    {
        abort_unless(app()->environment('local') && config('database.default') === 'pgsql'
            && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
        abort_unless($request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless((int) $draft->marketing_user_id === (int) $request->user()->id, 403);
        abort_unless($draft->status === 'RECORDED', 409);

        $event = $draft->events()->where('event_type', 'WORKSPACE_BRIEF_PREPARATION_SAVED')
            ->orderByDesc('id')->first();
        $values = $event?->after_data;
        if (is_string($values)) {
            $values = json_decode($values, true);
        }
        if (! is_array($values)) {
            $values = [];
        }
        $mapping = [
            'work_concept' => 'concept',
            'work_specification' => 'specification',
            'duration' => 'duration',
        ];
        $suggestions = [];
        foreach ($mapping as $component => $source) {
            $value = $values[$source] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $suggestions[$component] = trim($value);
            }
        }

        return response()->json([
            'source_draft_id' => $draft->id,
            'suggestions' => $suggestions,
            'designer_requested' => ! empty($values['designer']),
            'provisional_note' => ! empty($values['provisional']) ? (string) ($values['source'] ?? '') : null,
            'notice' => 'Data provisional belum dialokasikan per komponen. Konfirmasi manual sebelum menyimpan.',
        ])->header('Cache-Control', 'private, no-store');
    }
}
