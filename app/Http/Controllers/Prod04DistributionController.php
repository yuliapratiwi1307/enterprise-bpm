<?php

namespace App\Http\Controllers;

use App\Models\MarketingBrief;
use App\Models\Prod04BriefVersion;
use App\Services\BpmRoleAuthorization;
use App\Services\Prod04Workflow;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Prod04DistributionController extends Controller
{
    public function index(Request $request, BpmRoleAuthorization $roles): View
    {
        $user = $request->user();
        abort_unless(
            $roles->has($user, 'MARKETING')
            || $roles->has($user, 'ASST_MANAGER_PRODUKSI')
            || $roles->has($user, 'ADMIN_PRODUKSI')
            || $roles->has($user, 'ADMIN_BPM'),
            403,
        );

        $versions = Prod04BriefVersion::query()
            ->with(['marketingBrief.clientBrief', 'recipients.user', 'acknowledgement'])
            ->where(function ($query) use ($user, $roles): void {
                if ($roles->has($user, 'MARKETING')) {
                    $query->whereHas('marketingBrief', fn ($brief) => $brief->where('marketing_user_id', $user->id));
                }

                if ($roles->has($user, 'ASST_MANAGER_PRODUKSI') || $roles->has($user, 'ADMIN_PRODUKSI')) {
                    $query->orWhereHas('recipients', fn ($recipient) => $recipient->where('user_id', $user->id));
                }

                if ($roles->has($user, 'ADMIN_BPM')) {
                    $query->orWhere('distribution_status', 'DELIVERY_FAILED');
                }
            })
            ->latest('id')
            ->paginate(15);

        return view('prod04.index', compact('versions'));
    }

    public function submit(
        Request $request,
        MarketingBrief $marketingBrief,
        BpmRoleAuthorization $roles,
        Prod04Workflow $workflow,
    ): RedirectResponse {
        $roles->require($request->user(), 'MARKETING');
        abort_unless($marketingBrief->marketing_user_id === $request->user()->id, 403);

        $version = $workflow->submit($marketingBrief, $request->user());
        $version = $workflow->distribute($version, $request->user());

        $message = $version->distribution_status === 'DELIVERY_FAILED'
            ? 'Snapshot resmi tersimpan. Distribusi belum lengkap; retry dapat dilakukan tanpa membuat versi atau tugas ganda.'
            : 'Snapshot Brief Marketing dikunci dan distribusi diproses.';

        return redirect()->route('prod04.show', $version)->with(
            $version->distribution_status === 'DELIVERY_FAILED' ? 'warning' : 'message',
            $message,
        );
    }

    public function show(
        Request $request,
        Prod04BriefVersion $version,
        BpmRoleAuthorization $roles,
        Prod04Workflow $workflow,
    ): View {
        $version->load([
            'marketingBrief.clientBrief',
            'marketingBrief.marketing',
            'submitter',
            'recipients.user',
            'validationTask.assignee',
            'acknowledgement.actor',
            'slaTracking',
            'events.actor',
        ]);
        $this->authorizeVersion($request, $version, $roles);
        if ($version->recipients->contains('user_id', $request->user()->id)) {
            $workflow->recordDocumentViewed($version, $request->user());
            $version->load('events.actor');
        }

        return view('prod04.show', compact('version'));
    }

    public function retry(
        Request $request,
        Prod04BriefVersion $version,
        BpmRoleAuthorization $roles,
        Prod04Workflow $workflow,
    ): RedirectResponse {
        $user = $request->user();
        $isMarketingOwner = $roles->has($user, 'MARKETING')
            && $version->marketingBrief()->where('marketing_user_id', $user->id)->exists();
        abort_unless($isMarketingOwner || $roles->has($user, 'ADMIN_BPM'), 403);
        abort_unless($version->distribution_status === 'DELIVERY_FAILED', 409);

        $version = $workflow->distribute($version, $user);

        return redirect()->route('prod04.show', $version)->with(
            $version->distribution_status === 'DELIVERY_FAILED' ? 'warning' : 'message',
            $version->distribution_status === 'DELIVERY_FAILED'
                ? 'Retry selesai, tetapi distribusi masih belum lengkap.'
                : 'Retry berhasil; seluruh penerima dan tugas validasi tersedia.',
        );
    }

    public function acknowledge(
        Request $request,
        Prod04BriefVersion $version,
        BpmRoleAuthorization $roles,
        Prod04Workflow $workflow,
    ): RedirectResponse {
        $roles->require($request->user(), 'ASST_MANAGER_PRODUKSI');
        $workflow->acknowledge($version, $request->user());

        return redirect()->route('prod04.show', $version)->with('message', 'Penerimaan dokumen berhasil dikonfirmasi.');
    }

    private function authorizeVersion(Request $request, Prod04BriefVersion $version, BpmRoleAuthorization $roles): void
    {
        $user = $request->user();
        $isMarketingOwner = $roles->has($user, 'MARKETING')
            && $version->marketingBrief->marketing_user_id === $user->id;
        $isBpmAdministrator = $roles->has($user, 'ADMIN_BPM');
        $hasDistributionAccess = $version->recipients()
            ->where('user_id', $user->id)
            ->where('delivery_status', 'DELIVERED')
            ->exists();

        abort_unless($isMarketingOwner || $isBpmAdministrator || $hasDistributionAccess, 403);
    }
}
