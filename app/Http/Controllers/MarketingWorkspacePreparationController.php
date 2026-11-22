<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Local-only pre-brief notes linked to a PROD-02 DRAFT, not a PROD-03 MarketingBrief. */
class MarketingWorkspacePreparationController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless(app()->environment('local') && config('database.default') === 'pgsql'
            && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
        abort_unless($request->user()?->roles()->where('code', 'MARKETING')->exists(), 403);
        abort_unless(Schema::hasTable('client_brief_events') && Schema::hasColumns('client_brief_events',
            ['event_type', 'actor_user_id', 'actor_role', 'before_data', 'after_data', 'occurred_at']), 503);
    }

    private function ownedDraft(Request $request, int $draft): ClientBrief
    {
        $brief = ClientBrief::query()->whereKey($draft)->where('marketing_user_id', $request->user()->id)->firstOrFail();
        abort_unless($brief->status === 'DRAFT', 409, 'Persiapan hanya untuk Draft PROD-02.');

        return $brief;
    }

    private function latest(ClientBrief $brief): array
    {
        $event = $brief->events()->where('event_type', 'WORKSPACE_BRIEF_PREPARATION_SAVED')->orderByDesc('id')->first();
        $data = $event?->after_data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
    }

    public function show(Request $request, int $draft): JsonResponse
    {
        $this->guard($request);

        return response()->json(['preparation' => $this->latest($this->ownedDraft($request, $draft)), 'official_brief' => false]);
    }

    public function update(Request $request, int $draft): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate([
            'concept' => ['required', 'string', 'max:10000'],
            'designer' => ['required', 'boolean'],
            'provisional' => ['required', 'boolean'],
            'source' => ['nullable', 'string', 'max:10000', 'required_if:provisional,true'],
            'specification' => ['nullable', 'string', 'max:10000'],
            'duration' => ['nullable', 'string', 'max:255'],
        ]);
        $saved = DB::transaction(function () use ($request, $draft, $data) {
            $brief = ClientBrief::query()->whereKey($draft)->where('marketing_user_id', $request->user()->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($brief->status === 'DRAFT', 409, 'Draft tidak lagi dapat diubah.');
            $before = $this->latest($brief);
            $after = [
                'concept' => trim($data['concept']),
                'designer' => (bool) $data['designer'],
                'provisional' => (bool) $data['provisional'],
                'source' => ! empty($data['provisional']) ? trim($data['source'] ?? '') : '',
                'specification' => trim($data['specification'] ?? ''),
                'duration' => trim($data['duration'] ?? ''),
            ];
            $brief->events()->create([
                'event_type' => 'WORKSPACE_BRIEF_PREPARATION_SAVED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => $before ?: null,
                'after_data' => $after,
                'occurred_at' => now(),
            ]);

            return $after;
        });

        return response()->json(['preparation' => $saved, 'official_brief' => false]);
    }
}
