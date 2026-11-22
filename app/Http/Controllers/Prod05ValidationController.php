<?php

namespace App\Http\Controllers;

use App\Models\Prod04BriefVersion;
use App\Models\Prod05ActivityControl;
use App\Models\Prod05Validation;
use App\Services\BpmRoleAuthorization;
use App\Services\Prod05ValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Prod05ValidationController extends Controller
{
    public function index(Request $request, BpmRoleAuthorization $roles): View
    {
        $user = $request->user();
        abort_unless(
            $roles->has($user, 'ASST_MANAGER_PRODUKSI')
            || $roles->has($user, 'MANAGER_PRODUKSI')
            || $roles->has($user, 'ADMIN_PRODUKSI')
            || $roles->has($user, 'ADMIN_BPM'),
            403,
        );

        $validations = Prod05Validation::query()
            ->with(['briefVersion.marketingBrief.clientBrief', 'validator', 'events'])
            ->when($roles->has($user, 'MARKETING'), fn ($query) => $query->whereHas(
                'briefVersion.marketingBrief',
                fn ($brief) => $brief->where('marketing_user_id', $user->id)
            ))
            ->when($roles->has($user, 'ASST_MANAGER_PRODUKSI') || $roles->has($user, 'MANAGER_PRODUKSI'), fn ($query) => $query->whereIn(
                'status',
                ['UNDER_REVIEW', 'PENDING_MANAGER', 'VALIDATED_WITH_NOTES', 'APPROVED_CONDITIONAL']
            ))
            ->latest('id')
            ->paginate(15);

        return view('prod05.index', compact('validations'));
    }

    public function show(Prod05Validation $validation, Request $request, BpmRoleAuthorization $roles): View
    {
        $user = $request->user();
        abort_unless(
            $roles->has($user, 'ASST_MANAGER_PRODUKSI')
            || $roles->has($user, 'MANAGER_PRODUKSI')
            || $roles->has($user, 'ADMIN_PRODUKSI')
            || $roles->has($user, 'ADMIN_BPM'),
            403,
        );

        $validation->load(['briefVersion.marketingBrief.clientBrief', 'validator', 'events.actor', 'revisionClassifications.recommendedBy', 'activityControls']);

        return view('prod05.show', compact('validation'));
    }

    public function start(Prod04BriefVersion $version, Request $request, BpmRoleAuthorization $roles, Prod05ValidationService $service): RedirectResponse
    {
        $roles->require($request->user(), 'ASST_MANAGER_PRODUKSI');

        $service->startValidation($version, $request->user());

        return redirect()->route('prod05.show', $version->prod05Validation()->first() ?? $version->id)
            ->with('message', 'Validasi dimulai. Status: UNDER_REVIEW.');
    }

    public function result(
        Prod05Validation $validation,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'ASST_MANAGER_PRODUKSI');

        $validated = $request->validate([
            'status' => ['required', 'in:VALIDATED,VALIDATED_WITH_NOTES,RETURNED,PENDING_MANAGER'],
            'reason' => ['sometimes', 'string', 'max:1000'],
            'return_field' => ['sometimes', 'string', 'max:255'],
            'provisional_data' => ['sometimes', 'array'],
        ]);

        $service->giveValidationResult($validation, $request->user(), $validated['status'], [
            'reason' => $validated['reason'] ?? null,
            'return_reason' => $validated['reason'] ?? null,
            'return_field' => $validated['return_field'] ?? null,
            'provisional_data' => $validated['provisional_data'] ?? null,
        ]);

        return redirect()->route('prod05.show', $validation)
            ->with('message', 'Hasil validasi dicatat: ' . str_replace('_', ' ', $validated['status']));
    }

    public function classify(
        Prod05Validation $validation,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'ASST_MANAGER_PRODUKSI');

        $validated = $request->validate([
            'classification' => ['required', 'in:NO_IMPACT,MINOR,MAJOR,CRITICAL,UNDETERMINED'],
            'reason' => ['required', 'string', 'max:1000'],
            'override_recommendation' => ['sometimes', 'boolean'],
            'affected_activity_keys' => ['sometimes', 'array', 'min:1'],
            'affected_activity_keys.*' => ['required', 'string', 'max:100'],
        ]);

        $service->classifyImpact($validation, $request->user(), $validated['classification'], $validated['reason'], $validated['override_recommendation'] ?? false, $validated['affected_activity_keys'] ?? null);

        return redirect()->route('prod05.show', $validation)
            ->with('message', 'Klasifikasi dampak dicatat: ' . $validated['classification']);
    }

    public function approveDowngrade(Prod05Validation $validation, Request $request, BpmRoleAuthorization $roles, Prod05ValidationService $service): RedirectResponse
    {
        $roles->require($request->user(), 'MANAGER_PRODUKSI');
        $data = $request->validate([
            'classification' => ['required', 'in:NO_IMPACT,MINOR,MAJOR'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $service->approveCriticalDowngrade($validation, $request->user(), $data['classification'], $data['reason']);
        return redirect()->route('prod05.show', $validation)->with('message', 'Downgrade disetujui Manager. HOLD tetap aktif sampai evaluasi masing-masing.');
    }

    public function managerDecision(
        Prod05Validation $validation,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'MANAGER_PRODUKSI');

        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVED_FOR_REVIEW,APPROVED_CONDITIONAL,REJECTED'],
            'reason' => ['sometimes', 'string', 'max:1000'],
            'conditions' => ['sometimes', 'array'],
            'provisional_data' => ['sometimes', 'array'],
        ]);

        $service->managerDecision($validation, $request->user(), $validated['decision'], [
            'reason' => $validated['reason'] ?? null,
            'conditions' => $validated['conditions'] ?? [],
            'provisional_data' => $validated['provisional_data'] ?? null,
        ]);

        return redirect()->route('prod05.show', $validation)
            ->with('message', 'Keputusan Manager Produksi dicatat: ' . $validated['decision']);
    }

    public function releaseHold(
        Prod05Validation $validation,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'MANAGER_PRODUKSI');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->releaseHold($validation, $request->user(), $validated['reason']);

        return redirect()->route('prod05.show', $validation)
            ->with('message', 'ON HOLD dilepas.');
    }

    public function holdActivity(
        Prod05ActivityControl $activity,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'ASST_MANAGER_PRODUKSI');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $service->holdActivity($activity, $request->user(), $validated['reason']);

        return redirect()->route('prod05.show', $activity->validation)
            ->with('message', 'Aktivitas diletakkan ON HOLD.');
    }

    public function releaseActivity(
        Prod05ActivityControl $activity,
        Request $request,
        BpmRoleAuthorization $roles,
        Prod05ValidationService $service
    ): RedirectResponse {
        $roles->require($request->user(), 'MANAGER_PRODUKSI');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'impact_cleared' => ['required', 'accepted'],
        ]);

        $service->releaseActivity($activity, $request->user(), $validated['reason'], (bool) ($validated['impact_cleared'] ?? false));

        return redirect()->route('prod05.show', $activity->validation)
            ->with('message', 'Aktivitas dilepas dari ON HOLD.');
    }
}
