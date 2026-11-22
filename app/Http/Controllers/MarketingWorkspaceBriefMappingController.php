<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use Illuminate\Http\Request;

/** Read-only preview. Never creates or updates MarketingBrief. */
class MarketingWorkspaceBriefMappingController extends Controller
{
    public function show(Request $request, ClientBrief $draft)
    {
        abort_unless(app()->environment('local') && config('database.default') === 'pgsql'
            && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
        abort_unless($request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless((int) $draft->marketing_user_id === (int) $request->user()->id, 403);

        $event = $draft->events()->where('event_type', 'WORKSPACE_BRIEF_PREPARATION_SAVED')
            ->orderByDesc('id')->first();
        $preparation = $event?->after_data;
        if (is_string($preparation)) {
            $preparation = json_decode($preparation, true);
        }
        if (! is_array($preparation)) {
            $preparation = [];
        }

        // Deliberately do not infer missing fields from free-text project descriptions.
        $components = [
            ['label' => 'Jenis pekerjaan', 'value' => null, 'origin' => 'Perlu diisi pada PROD-03'],
            ['label' => 'Konsep pekerjaan', 'value' => $preparation['concept'] ?? null, 'origin' => 'Persiapan Brief — konsep'],
            ['label' => 'Visualisasi', 'value' => null, 'origin' => 'Perlu dikonfirmasi pada PROD-03'],
            ['label' => 'Spesifikasi pekerjaan', 'value' => $preparation['specification'] ?? null, 'origin' => 'Persiapan Brief — informasi tambahan'],
            ['label' => 'Durasi', 'value' => $preparation['duration'] ?? null, 'origin' => 'Persiapan Brief — estimasi durasi'],
            ['label' => 'Kuantitas', 'value' => null, 'origin' => 'Perlu diisi pada PROD-03'],
        ];
        $components = array_map(function ($component) {
            $component['value'] = is_scalar($component['value']) && trim((string) $component['value']) !== ''
                ? trim((string) $component['value']) : null;

            return $component;
        }, $components);

        return response()->view('marketing.brief-mapping', compact('draft', 'preparation', 'components'))
            ->header('Cache-Control', 'private, no-store');
    }
}
