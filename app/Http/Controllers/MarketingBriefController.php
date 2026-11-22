<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use App\Models\MarketingBrief;
use App\Services\BpmRoleAuthorization;
use App\Services\MarketingBriefReadiness;
use App\Services\ProductionWorkflowAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketingBriefController extends Controller
{
    public function index(Request $request, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'MARKETING');
        $ownerId = $request->user()->id;
        $statusValues = ['DRAFT', 'READY_FOR_SUBMISSION', 'READY_WITH_NOTES', 'SUBMITTED'];
        $statusFilter = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        if (! in_array($statusFilter, $statusValues, true)) {
            $statusFilter = '';
        }
        $search = mb_substr($search, 0, 120);

        $statusCounts = MarketingBrief::query()
            ->where('marketing_user_id', $ownerId)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status')->all();

        $briefQuery = MarketingBrief::query()
            ->where('marketing_user_id', $ownerId)
            ->with('clientBrief');
        if ($statusFilter !== '') {
            $briefQuery->where('status', $statusFilter);
        }
        if ($search !== '') {
            $briefQuery->where(function ($query) use ($search) {
                if (ctype_digit($search)) {
                    $query->whereKey((int) $search);
                }
                $query->orWhereHas('clientBrief', function ($clientQuery) use ($search) {
                    $clientQuery->where('request_number', 'like', '%'.$search.'%')
                        ->orWhere('client_name', 'like', '%'.$search.'%');
                });
            });
        }
        $briefs = $briefQuery->latest('id')->paginate(15)->withQueryString();

        $availableClientBriefs = ClientBrief::query()
            ->where('status', 'RECORDED')
            ->latest('id')
            ->get();

        return view('prod03.briefs.index', compact('briefs', 'availableClientBriefs', 'statusCounts', 'statusFilter', 'search'));
    }

    public function create(Request $request, ClientBrief $clientBrief, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'MARKETING');
        abort_unless($clientBrief->status === 'RECORDED', 404);

        return view('prod03.briefs.form', [
            'clientBrief' => $clientBrief,
            'brief' => new MarketingBrief,
            'componentDefinitions' => MarketingBriefReadiness::COMPONENTS,
        ]);
    }

    public function store(
        Request $request,
        ClientBrief $clientBrief,
        BpmRoleAuthorization $roles,
        ProductionWorkflowAudit $audit,
    ) {
        $roles->require($request->user(), 'MARKETING');

        $brief = DB::transaction(function () use ($request, $clientBrief, $audit) {
            $source = ClientBrief::query()->whereKey($clientBrief->id)->lockForUpdate()->firstOrFail();
            abort_unless($source->status === 'RECORDED', 404);

            $brief = MarketingBrief::query()->create([
                'client_brief_id' => $source->id,
                'marketing_user_id' => $request->user()->id,
                'status' => 'DRAFT',
            ]);

            if ($source->stage_3_started_at === null) {
                $before = ['stage_3_started_at' => null];
                $source->stage_3_started_at = now();
                $source->save();
                $source->events()->create([
                    'event_type' => 'STAGE_3_STARTED',
                    'actor_user_id' => $request->user()->id,
                    'actor_role' => 'MARKETING',
                    'before_data' => $before,
                    'after_data' => [
                        'stage_3_started_at' => $source->stage_3_started_at->toISOString(),
                        'marketing_brief_id' => $brief->id,
                    ],
                    'occurred_at' => now(),
                ]);
            }

            $audit->record(
                $brief,
                null,
                $request->user(),
                'MARKETING',
                'BRIEF_DRAFT_CREATED',
                null,
                ['client_brief_id' => $source->id, 'status' => 'DRAFT'],
            );

            return $brief;
        });

        return redirect()->route('prod03.briefs.edit', $brief)->with('message', 'Draft Brief Marketing berhasil dibuat.');
    }

    public function edit(Request $request, MarketingBrief $marketingBrief, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $marketingBrief);
        abort_unless(in_array($marketingBrief->status, ['DRAFT', 'READY_FOR_SUBMISSION', 'READY_WITH_NOTES'], true), 403);

        return view('prod03.briefs.form', [
            'clientBrief' => $marketingBrief->clientBrief,
            'brief' => $marketingBrief->load('components'),
            'componentDefinitions' => MarketingBriefReadiness::COMPONENTS,
        ]);
    }

    public function update(
        Request $request,
        MarketingBrief $marketingBrief,
        BpmRoleAuthorization $roles,
        ProductionWorkflowAudit $audit,
    ) {
        $roles->require($request->user(), 'MARKETING');
        $data = $request->validate($this->componentRules());

        DB::transaction(function () use ($request, $marketingBrief, $data, $audit) {
            $brief = MarketingBrief::query()->whereKey($marketingBrief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOwner($request, $brief);
            if ($brief->status === 'SUBMITTED') {
                throw ValidationException::withMessages(['brief' => 'Brief yang sudah dikirim tidak dapat diedit pada PROD-03.']);
            }

            $before = [
                'status' => $brief->status,
                'readiness_snapshot' => $brief->readiness_snapshot,
                'components' => $brief->components()->get()->keyBy('component')->map(fn ($item) => [
                    'value' => $item->value,
                    'is_provisional' => $item->is_provisional,
                    'information_basis' => $item->information_basis,
                ])->all(),
            ];

            foreach (MarketingBriefReadiness::COMPONENTS as $component => $label) {
                if (! array_key_exists($component, $data['components'] ?? [])) {
                    continue;
                }
                $value = $data['components'][$component] ?? [];
                $brief->components()->updateOrCreate(
                    ['component' => $component],
                    [
                        'value' => $value['value'] ?? null,
                        'is_provisional' => (bool) ($value['is_provisional'] ?? false),
                        'information_basis' => $value['information_basis'] ?? null,
                    ],
                );
            }

            $brief->status = 'DRAFT';
            $brief->readiness_snapshot = null;
            $brief->save();
            $after = [
                'status' => $brief->status,
                'readiness_snapshot' => $brief->readiness_snapshot,
                'components' => $brief->components()->get()->keyBy('component')->map(fn ($item) => [
                    'value' => $item->value,
                    'is_provisional' => $item->is_provisional,
                    'information_basis' => $item->information_basis,
                ])->all(),
            ];
            $audit->record($brief, null, $request->user(), 'MARKETING', 'BRIEF_DRAFT_UPDATED', $before, $after);
        });

        return redirect()->route('prod03.briefs.edit', $marketingBrief)->with('message', 'Draft Brief Marketing berhasil disimpan.');
    }

    public function validateReadiness(
        Request $request,
        MarketingBrief $marketingBrief,
        BpmRoleAuthorization $roles,
        MarketingBriefReadiness $readiness,
        ProductionWorkflowAudit $audit,
    ) {
        $roles->require($request->user(), 'MARKETING');

        $brief = DB::transaction(function () use ($request, $marketingBrief, $readiness, $audit) {
            $brief = MarketingBrief::query()->whereKey($marketingBrief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOwner($request, $brief);
            if ($brief->status === 'SUBMITTED') {
                throw ValidationException::withMessages(['brief' => 'Brief yang sudah dikirim tidak dapat divalidasi ulang pada PROD-03.']);
            }

            $before = ['status' => $brief->status, 'readiness_snapshot' => $brief->readiness_snapshot];
            $result = $readiness->evaluate($brief);
            $brief->status = $result['status'];
            $brief->readiness_snapshot = $result;
            $brief->save();
            $audit->record(
                $brief,
                null,
                $request->user(),
                'MARKETING',
                $result['ready'] ? 'BRIEF_READY' : 'READINESS_CHECKED',
                $before,
                ['status' => $brief->status, ...$result],
            );

            return $brief;
        });

        return redirect()->route('prod03.briefs.show', $brief)->with(
            $brief->status === 'DRAFT' ? 'warning' : 'message',
            $brief->status === 'DRAFT'
                ? 'Brief belum memenuhi seluruh persyaratan kesiapan.'
                : 'Kesiapan Brief Marketing berhasil dicatat.'
        );
    }

    public function show(Request $request, MarketingBrief $marketingBrief, BpmRoleAuthorization $roles)
    {
        $roles->require($request->user(), 'MARKETING');
        $this->authorizeOwner($request, $marketingBrief);
        $brief = $marketingBrief->load([
            'clientBrief',
            'components',
            'designRequests.currentPic',
            'designRequests.attachments',
            'designRequests.deadlineChanges',
            'events.actor',
        ]);
        $events = $brief->events()->with(['actor', 'designRequest'])->latest('occurred_at')->get();

        return view('prod03.briefs.show', compact('brief', 'events'));
    }

    private function authorizeOwner(Request $request, MarketingBrief $brief): void
    {
        abort_unless($brief->marketing_user_id === $request->user()->id, 403);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function componentRules(): array
    {
        $rules = ['components' => ['sometimes', 'array']];
        foreach (MarketingBriefReadiness::COMPONENTS as $key => $label) {
            $rules["components.{$key}"] = ['sometimes', 'array'];
            $rules["components.{$key}.value"] = ['nullable', 'string', 'max:10000'];
            $rules["components.{$key}.is_provisional"] = ['sometimes', 'boolean'];
            $rules["components.{$key}.information_basis"] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }
}
