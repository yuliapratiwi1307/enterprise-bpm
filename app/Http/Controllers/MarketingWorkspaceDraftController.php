<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingWorkspaceDraftController extends Controller
{
    private function guard(Request $request): void
    {
        abort_unless(app()->environment('local') && config('database.default') === 'pgsql'
            && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
        abort_unless($request->user()?->roles()->where('code', 'MARKETING')->exists(), 403);
    }

    public function index(Request $request): JsonResponse
    {
        $this->guard($request);
        $drafts = ClientBrief::query()->where('marketing_user_id', $request->user()->id)
            ->where('status', 'DRAFT')->latest('id')->limit(100)
            ->get(['id', 'client_name', 'project_name', 'description', 'status']);

        return response()->json(['drafts' => $drafts]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->guard($request);
        abort_unless(Schema::hasColumns('client_briefs', ['project_name', 'client_name', 'description']), 503);
        $data = $request->validate([
            'client' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'needs' => ['required', 'string', 'max:10000'],
        ]);
        $brief = DB::transaction(function () use ($data, $request) {
            $brief = new ClientBrief;
            $brief->client_name = trim($data['client']);
            $brief->project_name = trim($data['name']);
            $brief->description = trim($data['needs']);
            $brief->status = 'DRAFT';
            $brief->marketing_user_id = $request->user()->id;
            $brief->save();
            $brief->events()->create([
                'event_type' => 'DRAFT_CREATED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => null,
                'after_data' => $brief->only(['id', 'status', 'marketing_user_id', 'client_name', 'project_name', 'description']),
                'occurred_at' => now(),
            ]);

            return $brief;
        });

        return response()->json(['draft' => [
            'id' => $brief->id, 'client_name' => $brief->client_name,
            'project_name' => $brief->project_name, 'description' => $brief->description,
            'status' => $brief->status,
        ]], 201);
    }

    public function update(Request $request, int $draft): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate([
            'client' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'needs' => ['required', 'string', 'max:10000'],
        ]);
        $brief = DB::transaction(function () use ($request, $draft, $data) {
            $brief = ClientBrief::query()->where('id', $draft)
                ->where('marketing_user_id', $request->user()->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($brief->status === 'DRAFT', 409, 'Hanya Draft yang dapat diperbarui.');
            $before = $brief->only(['client_name', 'project_name', 'description']);
            $brief->client_name = trim($data['client']);
            $brief->project_name = trim($data['name']);
            $brief->description = trim($data['needs']);
            $brief->save();
            $brief->events()->create([
                'event_type' => 'DRAFT_UPDATED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => $before,
                'after_data' => $brief->only(['client_name', 'project_name', 'description']),
                'occurred_at' => now(),
            ]);

            return $brief;
        });

        return response()->json(['draft' => [
            'id' => $brief->id,
            'client_name' => $brief->client_name,
            'project_name' => $brief->project_name,
            'description' => $brief->description,
            'status' => $brief->status,
        ]]);
    }
}
