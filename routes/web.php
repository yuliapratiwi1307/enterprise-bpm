<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignRequestController;
use App\Http\Controllers\MarketingBriefController;
use App\Http\Controllers\MarketingWorkspaceBriefMappingController;
use App\Http\Controllers\MarketingWorkspaceDraftController;
use App\Http\Controllers\MarketingWorkspacePrefillController;
use App\Http\Controllers\MarketingWorkspacePreparationController;
use App\Http\Controllers\MmMasterDataController;
use App\Http\Controllers\MmMaterialPriceController;
use App\Http\Controllers\MmMaterialWizardController;
use App\Http\Controllers\MmSpecificationController;
use App\Http\Controllers\MmSpecificationTemplateController;
use App\Http\Controllers\MmSupplierController;
use App\Http\Controllers\MmSupplierQuoteController;
use App\Http\Controllers\MmWorkspaceController;
use App\Http\Controllers\Prod02AttachmentController;
use App\Http\Controllers\Prod02Controller;
use App\Http\Controllers\Prod02ReferenceImageController;
use App\Http\Controllers\Prod04DistributionController;
use App\Http\Controllers\Prod05ValidationController;
use App\Http\Controllers\ProductionNotificationController;
use App\Http\Controllers\SjrDesignDecisionController;
use App\Http\Controllers\SjrDesignerItemInboxController;
use App\Http\Controllers\SjrDesignerItemResultController;
use App\Http\Controllers\SjrDesignSubmissionController;
use App\Http\Controllers\SjrDesignWorkspaceController;
use App\Http\Controllers\SjrJobController;
use App\Http\Controllers\SjrMarketingItemReviewController;
use App\Http\Controllers\SjrMultiItemBriefReviewController;
use App\Http\Controllers\SjrMultiItemController;
use App\Http\Controllers\SjrMultiItemHandoffController;
use App\Http\Controllers\SjrMultiItemTechnicalDraftController;
use App\Http\Controllers\SjrMultiItemTechnicalFinalizationController;
use App\Http\Controllers\SjrPreviewController;
use App\Http\Controllers\SjrProductionTechnicalController;
use App\Http\Controllers\TaskPoolController;
use App\Models\ClientBrief;
use App\Models\MarketingBrief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

// PROD-02 - Penerimaan Brief Klien
Route::middleware('auth')
    ->prefix('production/briefs')
    ->name('prod02.')
    ->group(function () {
        Route::get('/', [Prod02Controller::class, 'index'])->name('index');
        Route::get('/create', [Prod02Controller::class, 'create'])->name('create');
        Route::post('/', [Prod02Controller::class, 'store'])->name('store');
        Route::post('/{brief}/submit', [Prod02Controller::class, 'submit'])->whereNumber('brief')->name('submit');
        Route::get('/{brief}', [Prod02Controller::class, 'show'])->whereNumber('brief')->name('show');
        Route::get('/{brief}/edit', [Prod02Controller::class, 'edit'])->whereNumber('brief')->name('edit');
        Route::put('/{brief}', [Prod02Controller::class, 'update'])->whereNumber('brief')->name('update');
        // PROD02_REFERENCE_IMAGES_ISOLATED_V1 — separate Step 3 resource
        Route::post('/{brief}/reference-images', [Prod02ReferenceImageController::class, 'store'])->whereNumber('brief')->name('reference-images.store');
        Route::get('/{brief}/reference-images/{image}', [Prod02ReferenceImageController::class, 'show'])->whereNumber('brief')->whereNumber('image')->name('reference-images.show');
        Route::delete('/{brief}/reference-images/{image}', [Prod02ReferenceImageController::class, 'destroy'])->whereNumber('brief')->whereNumber('image')->name('reference-images.destroy');
        Route::post('/{brief}/attachments', [Prod02AttachmentController::class, 'store'])->whereNumber('brief')->name('attachments.store');
        Route::get('/{brief}/attachments/{attachment}/download', [Prod02AttachmentController::class, 'download'])->whereNumber('brief')->name('attachments.download');
        Route::delete('/{brief}/attachments/{attachment}', [Prod02AttachmentController::class, 'destroy'])->whereNumber('brief')->name('attachments.destroy');
        Route::post('/{brief}/duplicate-check', [Prod02Controller::class, 'checkDuplicates'])->whereNumber('brief')->name('duplicate-check');
        Route::post('/{brief}/duplicate-decision', [Prod02Controller::class, 'decideDuplicate'])->whereNumber('brief')->name('duplicate-decision');
    });

// PROD-03 - Penyusunan Brief Marketing dan Design Request
Route::middleware('auth')->prefix('production/marketing-briefs')->name('prod03.briefs.')->group(function () {
    Route::get('/', [MarketingBriefController::class, 'index'])->name('index');
    Route::get('/from-client-brief/{clientBrief}/create', [MarketingBriefController::class, 'create'])->name('create');
    Route::post('/from-client-brief/{clientBrief}', [MarketingBriefController::class, 'store'])->name('store');
    Route::get('/{marketingBrief}', [MarketingBriefController::class, 'show'])->name('show');
    Route::get('/{marketingBrief}/edit', [MarketingBriefController::class, 'edit'])->name('edit');
    Route::put('/{marketingBrief}', [MarketingBriefController::class, 'update'])->name('update');
    Route::post('/{marketingBrief}/validate-readiness', [MarketingBriefController::class, 'validateReadiness'])->name('validate-readiness');
});

Route::middleware('auth')->prefix('production/design-requests')->name('prod03.design-requests.')->group(function () {
    Route::get('/for-brief/{marketingBrief}/create', [DesignRequestController::class, 'create'])->name('create');
    Route::post('/for-brief/{marketingBrief}', [DesignRequestController::class, 'store'])->name('store');
    Route::get('/{designRequest}', [DesignRequestController::class, 'show'])->name('show');
    Route::post('/{designRequest}/classification', [DesignRequestController::class, 'changeClassification'])->name('classification');
    Route::post('/{designRequest}/cancel', [DesignRequestController::class, 'cancel'])->name('cancel');
    Route::post('/{designRequest}/deadline-changes/{deadlineChange}/decision', [DesignRequestController::class, 'decideDeadline'])->name('deadline-decision');
    Route::get('/attachments/{attachment}/download', [DesignRequestController::class, 'downloadAttachment'])->name('attachments.download');
});

Route::middleware('auth')->prefix('production/design-task-pool')->name('prod03.task-pool.')->group(function () {
    Route::get('/', [TaskPoolController::class, 'index'])->name('index');
    Route::post('/{designRequest}/take', [TaskPoolController::class, 'take'])->name('take');
    Route::post('/{designRequest}/release', [TaskPoolController::class, 'release'])->name('release');
    Route::post('/{designRequest}/submit-result', [TaskPoolController::class, 'submitResult'])->name('submit-result');
    Route::post('/{designRequest}/review', [TaskPoolController::class, 'review'])->name('review');
    Route::post('/{designRequest}/deadline-change', [TaskPoolController::class, 'requestDeadlineChange'])->name('deadline-change');
    Route::post('/{designRequest}/acknowledge-cancellation', [TaskPoolController::class, 'acknowledgeCancellation'])->name('acknowledge-cancellation');
});

Route::middleware('auth')->prefix('production/notifications')->name('prod03.notifications.')->group(function () {
    Route::get('/', [ProductionNotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [ProductionNotificationController::class, 'markRead'])->name('read');
});

// PROD-04 - Distribusi Brief Marketing
Route::middleware('auth')->prefix('production/brief-distributions')->name('prod04.')->group(function () {
    Route::get('/', [Prod04DistributionController::class, 'index'])->name('index');
    Route::get('/{version}', [Prod04DistributionController::class, 'show'])->name('show');
    Route::post('/{version}/retry', [Prod04DistributionController::class, 'retry'])->name('retry');
    Route::post('/{version}/acknowledge', [Prod04DistributionController::class, 'acknowledge'])->name('acknowledge');
});

Route::middleware('auth')->post(
    '/production/marketing-briefs/{marketingBrief}/submit-distribute',
    [Prod04DistributionController::class, 'submit'],
)->name('prod04.submit');

// PROD-05 - Validasi Brief Marketing
Route::middleware('auth')->prefix('production/brief-validations')->name('prod05.')->group(function () {
    Route::get('/', [Prod05ValidationController::class, 'index'])->name('index');
    Route::get('/{validation}', [Prod05ValidationController::class, 'show'])->name('show');
    Route::post('/brief-version/{version}/start', [Prod05ValidationController::class, 'start'])->name('start');
    Route::post('/{validation}/result', [Prod05ValidationController::class, 'result'])->name('result');
    Route::post('/{validation}/classify', [Prod05ValidationController::class, 'classify'])->name('classify');
    Route::post('/{validation}/approve-downgrade', [Prod05ValidationController::class, 'approveDowngrade'])->name('approve-downgrade');
    Route::post('/{validation}/manager-decision', [Prod05ValidationController::class, 'managerDecision'])->name('manager-decision');
    Route::post('/{validation}/release-hold', [Prod05ValidationController::class, 'releaseHold'])->name('release-hold');
    Route::post('/activity/{activity}/hold', [Prod05ValidationController::class, 'holdActivity'])->name('activity-hold');
    Route::post('/activity/{activity}/release', [Prod05ValidationController::class, 'releaseActivity'])->name('activity-release');
});

// Enterprise BPM - Authentication
Route::middleware('guest')->get('/login', function () {
    return view('auth.login');
})->name('login');

Route::middleware('guest')->post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);

        throw new TooManyRequestsHttpException(
            $seconds,
            "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik."
        );
    }

    if (Auth::attempt($credentials)) {
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    RateLimiter::hit($key, 60);

    return back()->withErrors([
        'email' => 'Email atau password salah.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->middleware('auth')->name('logout');

// MARKETING_WORKSPACE_V2_STAGE1_AUTH_PREVIEW
// UI-only: no database writes, no workflow modification.
Route::get('/marketing/workspace', function (Request $request) {
    // Strictly restrict this simulation to local development + isolated test DB.
    abort_unless(app()->environment('local') && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
    abort_unless($request->user()->roles()->where('code', 'MARKETING')->exists(), 403);

    return view('marketing.workspace');
})->middleware('auth')->name('marketing.workspace');

// MARKETING_WORKSPACE_V2_STAGE2A_DRAFT_API — test environment only
Route::middleware('auth')->prefix('marketing/workspace/drafts')->name('marketing.workspace.drafts.')->group(function () {
    Route::get('/', [MarketingWorkspaceDraftController::class, 'index'])->name('index');
    Route::post('/', [MarketingWorkspaceDraftController::class, 'store'])->name('store');
    Route::put('/{draft}', [MarketingWorkspaceDraftController::class, 'update'])->whereNumber('draft')->name('update');
});

// MARKETING_WORKSPACE_V2_STAGE2B_PREPARATION — local test only, no PROD-03 issue or distribution
Route::middleware('auth')->prefix('marketing/workspace/preparation')->name('marketing.workspace.preparation.')->group(function () {
    Route::get('/{draft}', [MarketingWorkspacePreparationController::class, 'show'])->whereNumber('draft')->name('show');
    Route::put('/{draft}', [MarketingWorkspacePreparationController::class, 'update'])->whereNumber('draft')->name('update');
});

// MARKETING_WORKSPACE_V2_STAGE2D_READONLY_STATUS — no writes, no submit.
Route::get('/marketing/workspace/prod02-status/{draft}', function (Request $request, ClientBrief $draft) {
    abort_unless(app()->environment('local') && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);
    abort_unless($request->user()->roles()->where('code', 'MARKETING')->exists(), 403);
    abort_unless((int) $draft->marketing_user_id === (int) $request->user()->id, 403);
    $labels = ['client_name' => 'Nama klien', 'communication_source' => 'Sumber komunikasi', 'requirement_type' => 'Jenis kebutuhan', 'description' => 'Deskripsi kebutuhan'];
    $missing = [];
    foreach ($labels as $field => $label) {
        if (blank($draft->{$field})) {
            $missing[] = $label;
        }
    }
    $existingBrief = MarketingBrief::query()
        ->where('client_brief_id', $draft->id)
        ->where('marketing_user_id', $request->user()->id)
        ->orderByDesc('id')->value('id');

    return response()->json([
        'status' => $draft->status,
        'request_number' => $draft->request_number,
        'missing_basic' => $missing,
        'existing_marketing_brief_id' => $existingBrief,
    ])->header('Cache-Control', 'private, no-store');
})->middleware('auth')->whereNumber('draft')->name('marketing.workspace.prod02-status');

// MARKETING_WORKSPACE_STAGE2F_READONLY_MAPPING (no writes or official brief creation)
Route::get('/marketing/workspace/brief-mapping/{draft}', [MarketingWorkspaceBriefMappingController::class, 'show'])
    ->middleware('auth')->whereNumber('draft')->name('marketing.workspace.brief-mapping');

// MARKETING_WORKSPACE_STAGE2G_PREFILL (read-only, checked again inside controller)
Route::get('/marketing/workspace/brief-prefill/{draft}', [MarketingWorkspacePrefillController::class, 'show'])
    ->middleware('auth')->whereNumber('draft')->name('marketing.workspace.brief-prefill');

// SJR_MODULE_UI_FIRST_V1 — standalone module, independent of PROD-02..09
Route::get('/simple-jobs', [SjrPreviewController::class, 'index'])
    ->middleware('auth')->name('sjr.preview');

// SJR_BRIEF_PERSISTENCE_V1 — isolated SJR only
Route::middleware('auth')->prefix('simple-jobs/api')->group(function () {
    Route::get('/jobs', [SjrJobController::class, 'index']);
    Route::post('/jobs', [SjrJobController::class, 'store']);
});

// SJR_ATTACHMENTS_V1
Route::middleware('auth')->get('/simple-jobs/api/jobs/{job}/attachments/{attachment}/download', [SjrJobController::class, 'download'])->name('sjr.reference.download');

// SJR DESIGN WORKSPACE STAGE1 READONLY
Route::get('/sjr/design-workspace', [SjrDesignWorkspaceController::class, 'index'])->middleware('auth')->name('sjr.design.workspace');

// SJR DESIGN WORKSPACE STAGE2A WRITE
Route::post('/sjr/design-workspace/{jobNumber}/submit', [SjrDesignSubmissionController::class, 'store'])->middleware('auth')->name('sjr.design.submit');
Route::get('/sjr/design-files/{file}/download', [SjrDesignSubmissionController::class, 'download'])->middleware('auth')->name('sjr.design.files.download');

// SJR DESIGN WORKSPACE STAGE2C MARKETING REVIEW
Route::post('/sjr/design-workspace/{jobNumber}/decision', [SjrDesignDecisionController::class, 'store'])->middleware('auth')->name('sjr.design.decision');

// SJR INTEGRATION FIX V2 REVISED (read-only Marketing activity)
Route::get('/sjr/api/design-history', [SjrDesignWorkspaceController::class, 'history'])->middleware('auth');

// SJR_DETAIL_V3_READONLY
Route::get('/sjr/api/design-detail', [SjrDesignWorkspaceController::class, 'detail'])->middleware('auth');

// SJR_TECH_PREPARATION_UI_V1_LOCAL_PREVIEW
// Read-only UI simulation; isolated from legacy PROD-02..09 and SJR mutations.
Route::get('/sjr/production/technical-preparation-preview', function (Request $request) {
    abort_unless(app()->environment('local') && config('database.default') === 'pgsql'
        && config('database.connections.pgsql.database') === 'enterprise_bpm_browser', 404);

    return response()->view('sjr.technical-preparation-preview')->header('Cache-Control', 'private, no-store');
})->middleware('auth')->name('sjr.production.technical-preview');

// SJR_PRODUCTION_TECHNICAL_STAGE3 - isolated SJR business workflow
Route::middleware('auth')->prefix('sjr/production')->name('sjr.production.technical.')->group(function () {
    Route::get('/inbox', [SjrProductionTechnicalController::class, 'index'])->name('index');
    Route::get('/jobs/{jobNumber}/technical', [SjrProductionTechnicalController::class, 'show'])->name('show');
    Route::post('/jobs/{jobNumber}/review', [SjrProductionTechnicalController::class, 'review'])->name('review');
    Route::post('/jobs/{jobNumber}/technical', [SjrProductionTechnicalController::class, 'save'])->name('save');
});

// SJR V5.2 — additive multi-item API; existing endpoints remain untouched.
Route::middleware('auth')->prefix('simple-jobs/api')->group(function () {
    Route::post('/jobs-v52', [SjrMultiItemController::class, 'store']);
    Route::get('/jobs-v52/{job}', [SjrMultiItemController::class, 'show'])->whereNumber('job');
    Route::get('/jobs-v52/{job}/previews/{preview}', [SjrMultiItemController::class, 'preview'])->whereNumber('job')->whereNumber('preview');
});

// SJR V5.5 STAGE 1 — Designer item inbox (read-only, no handoff)
Route::middleware('auth')->prefix('simple-jobs/api')->group(function () {
    Route::get('/designer-items-v55', [SjrDesignerItemInboxController::class, 'index']);
    Route::get('/designer-items-v55/{item}/previews/{preview}', [SjrDesignerItemInboxController::class, 'preview'])->whereNumber('item')->whereNumber('preview');
    Route::get('/designer-items-v55/{item}/project-documents/{document}', [SjrDesignerItemInboxController::class, 'projectDocument'])->whereNumber('item')->whereNumber('document');
});

// SJR V5.5 STAGE 2 — per-item Designer draft and submission; no production handoff.
Route::middleware('auth')->prefix('simple-jobs/api')->group(function () {
    Route::get('/designer-items-v55/{item}/results', [SjrDesignerItemResultController::class, 'index'])->whereNumber('item');
    Route::post('/designer-items-v55/{item}/results', [SjrDesignerItemResultController::class, 'save'])->whereNumber('item');
    Route::delete('/designer-items-v55/{item}/results/files/{file}', [SjrDesignerItemResultController::class, 'destroyDraftFile'])->whereNumber('item')->whereNumber('file');
    Route::get('/designer-items-v55/{item}/results/files/{file}', [SjrDesignerItemResultController::class, 'download'])->whereNumber('item')->whereNumber('file');
});

// SJR V5.5 STAGE 3 — Marketing review per item; never forwards Project to Production.
Route::middleware('auth')->prefix('simple-jobs/api')->group(function () {
    Route::post('/marketing-items-v55/{item}/review', [SjrMarketingItemReviewController::class, 'decide'])->whereNumber('item');
});

// SJR V5.6 — isolated full multi-item handoff (NOT PROD-02..09).
Route::middleware('auth')->group(function () {
    Route::post('/simple-jobs/api/jobs-v56/{job}/handoff', [SjrMultiItemHandoffController::class, 'send'])->whereNumber('job');
    Route::get('/sjr/production/multi-item-inbox', [SjrMultiItemHandoffController::class, 'inbox'])->name('sjr.production.multi.inbox');
    Route::get('/sjr/production/multi-item/{jobNumber}/previews/{preview}', [SjrMultiItemHandoffController::class, 'preview'])->whereNumber('preview')->name('sjr.production.multi.preview');
    Route::get('/sjr/production/multi-item/{jobNumber}/attachments/{file}', [SjrMultiItemHandoffController::class, 'download'])->whereNumber('file')->name('sjr.production.multi.download');
    Route::get('/sjr/production/multi-item/{jobNumber}/design-files/{file}', [SjrMultiItemHandoffController::class, 'designFile'])->whereNumber('file')->name('sjr.production.multi.designfile');
    Route::get('/sjr/production/multi-item/{jobNumber}/tabler-preview', [SjrMultiItemHandoffController::class, 'show'])->name('sjr.production.multi.tabler-preview');
    Route::get('/sjr/production/multi-item/{jobNumber}', [SjrMultiItemHandoffController::class, 'show'])->name('sjr.production.multi.show');
});

// SJR V5.7.1 — review brief Project Multi-Item only.
Route::middleware('auth')->post('/sjr/production/multi-item/{jobNumber}/brief-review', [SjrMultiItemBriefReviewController::class, 'store'])->name('sjr.production.multi.brief.review');

// SJR V5.8.2 — atomic project-wide READY_FOR_HPP (does NOT create HPP).
Route::middleware('auth')->post('/sjr/production/multi-item/{jobNumber}/technical/finalize', [SjrMultiItemTechnicalFinalizationController::class, 'store'])->name('sjr.production.multi.technical.finalize');

// SJR V5.7.2 — save per-item technical draft (not HPP).
Route::middleware('auth')->post('/sjr/production/multi-item/{jobNumber}/technical/{itemId}/draft', [SjrMultiItemTechnicalDraftController::class, 'save'])->whereNumber('itemId')->name('sjr.production.multi.technical.save');
    Route::middleware('auth')->get('/sjr/production/multi-item/{jobNumber}/technical/{itemId}/materials/{materialIndex}/attachments/{attachmentIndex}', [SjrMultiItemTechnicalDraftController::class, 'downloadMaterialAttachment'])->whereNumber('itemId')->whereNumber('materialIndex')->whereNumber('attachmentIndex')->name('sjr.production.multi.material.attachment');

// MM-05 Material Selection for SJR Technical Preparation
Route::middleware('auth')->post('/sjr/production/{jobNumber}/mm05/store', [SjrProductionTechnicalController::class, 'storeMm05'])
    ->name('sjr.production.mm05.store');

Route::middleware('auth')->delete('/sjr/production/{jobNumber}/mm05/clear', [SjrProductionTechnicalController::class, 'clearMm05'])
    ->name('sjr.production.mm05.clear')
    ->whereNumber('jobNumber');

// MM-05 Master Material Workspace — dashboard and isolated material CRUD.
Route::middleware('auth')->group(function () {
    Route::get('/material-master', [MmWorkspaceController::class, 'index'])
        ->name('mm05.workspace');

    Route::prefix('material-master/suppliers')->name('mm05.suppliers.')->group(function () {
        Route::get('/', [MmSupplierController::class, 'index'])->name('index');
        Route::post('/', [MmSupplierController::class, 'store'])->name('store');
        Route::put('/{supplier}', [MmSupplierController::class, 'update'])->whereNumber('supplier')->name('update');
        Route::post('/{supplier}/deactivate', [MmSupplierController::class, 'deactivate'])->whereNumber('supplier')->name('deactivate');
        Route::post('/{supplier}/activate', [MmSupplierController::class, 'activate'])->whereNumber('supplier')->name('activate');
    });

    Route::prefix('material-master/supplier-quotes')->name('mm05.quotes.')->group(function () {
        Route::get('/', fn () => redirect()->route('mm05.prices.index'))->name('index');
        Route::post('/', [MmSupplierQuoteController::class, 'store'])->name('store');
        Route::put('/{quote}', [MmSupplierQuoteController::class, 'update'])->whereNumber('quote')->name('update');
        Route::post('/{quote}/approve', [MmSupplierQuoteController::class, 'approve'])->whereNumber('quote')->name('approve');
        Route::post('/{quote}/reject', [MmSupplierQuoteController::class, 'reject'])->whereNumber('quote')->name('reject');
        Route::post('/{quote}/activate', [MmSupplierQuoteController::class, 'activate'])->whereNumber('quote')->name('activate');
        Route::post('/{quote}/deactivate', [MmSupplierQuoteController::class, 'deactivate'])->whereNumber('quote')->name('deactivate');
    });

    Route::prefix('material-master/materials')->name('mm05.materials.')->group(function () {
        Route::get('/create', [MmMaterialWizardController::class, 'create'])->name('wizard.create');
        Route::post('/wizard', [MmMaterialWizardController::class, 'store'])->name('wizard.store');

        Route::get('/', [MmWorkspaceController::class, 'materials'])->name('index');
        Route::get('/{material}', [MmWorkspaceController::class, 'showMaterial'])->whereNumber('material')->name('show');
        Route::put('/{material}/variants/{variant}/attributes', [MmSpecificationController::class, 'updateMaterialVariantAttributes'])->whereNumber('material')->whereNumber('variant')->name('variants.attributes.update');
        Route::post('/{material}/variants/{variant}/prices', [MmMaterialPriceController::class, 'updateFromMaterial'])->whereNumber('material')->whereNumber('variant')->name('variants.prices.update');
        Route::post('/{material}/suppliers', [MmSupplierController::class, 'storeFromMaterial'])->whereNumber('material')->name('suppliers.store');
        Route::post('/', [MmWorkspaceController::class, 'storeMaterial'])->name('store');
        Route::put('/{material}', [MmWorkspaceController::class, 'updateMaterial'])->whereNumber('material')->name('update');
        Route::post('/{material}/deactivate', [MmWorkspaceController::class, 'deactivateMaterial'])->whereNumber('material')->name('deactivate');

        Route::post('/{material}/activate', [MmWorkspaceController::class, 'activateMaterial'])->whereNumber('material')->name('activate');
    });

    // MM-05 master data CRUD — reuses the existing guarded service and schema.
    Route::prefix('material-master/categories')->name('mm05.categories.')->group(function () {
        Route::get('/', [MmMasterDataController::class, 'categories'])->name('index');
        Route::post('/', [MmMasterDataController::class, 'storeCategory'])->name('store');
        Route::get('/{category}/specifications', [MmSpecificationController::class, 'categoryDefinitions'])->whereNumber('category')->name('specifications.index');
        Route::post('/{category}/specifications', [MmSpecificationController::class, 'storeCategoryDefinition'])->whereNumber('category')->name('specifications.store');
        Route::put('/{category}/specifications/{definition}', [MmSpecificationController::class, 'updateCategoryDefinition'])->whereNumber('category')->whereNumber('definition')->name('specifications.update');
        Route::post('/{category}/specifications/{definition}/deactivate', [MmSpecificationController::class, 'deactivateCategoryDefinition'])->whereNumber('category')->whereNumber('definition')->name('specifications.deactivate');
        Route::put('/{category}', [MmMasterDataController::class, 'updateCategory'])->whereNumber('category')->name('update');
        Route::post('/{category}/deactivate', [MmMasterDataController::class, 'deactivateCategory'])->whereNumber('category')->name('deactivate');
        Route::delete('/{category}', [MmMasterDataController::class, 'deleteCategory'])->whereNumber('category')->name('delete');
    });

    Route::prefix('material-master/specification-templates')->name('mm05.templates.')->group(function () {
        Route::get('/', [MmSpecificationTemplateController::class, 'index'])->name('index');
        Route::get('/{template}/attributes', [MmSpecificationTemplateController::class, 'attributes'])->whereNumber('template')->name('attributes.index');
        Route::post('/', [MmSpecificationTemplateController::class, 'store'])->name('store');
        Route::put('/{template}', [MmSpecificationTemplateController::class, 'update'])->whereNumber('template')->name('update');
        Route::delete('/{template}', [MmSpecificationTemplateController::class, 'destroy'])->whereNumber('template')->name('delete');
        Route::post('/{template}/activate', [MmSpecificationTemplateController::class, 'activate'])->whereNumber('template')->name('activate');
        Route::post('/{template}/deactivate', [MmSpecificationTemplateController::class, 'deactivate'])->whereNumber('template')->name('deactivate');
        Route::post('/{template}/attributes', [MmSpecificationTemplateController::class, 'attachAttribute'])->whereNumber('template')->name('attributes.attach');
        Route::put('/{template}/attributes/{attribute}', [MmSpecificationTemplateController::class, 'updateAttribute'])->whereNumber('template')->whereNumber('attribute')->name('attributes.update');
        Route::delete('/{template}/attributes/{attribute}', [MmSpecificationTemplateController::class, 'detachAttribute'])->whereNumber('template')->whereNumber('attribute')->name('attributes.detach');
    });

    Route::prefix('material-master/units')->name('mm05.units.')->group(function () {
        Route::get('/', [MmMasterDataController::class, 'units'])->name('index');
        Route::post('/', [MmMasterDataController::class, 'storeUnit'])->name('store');
        Route::put('/{unit}', [MmMasterDataController::class, 'updateUnit'])->whereNumber('unit')->name('update');
        Route::post('/{unit}/deactivate', [MmMasterDataController::class, 'deactivateUnit'])->whereNumber('unit')->name('deactivate');
        Route::delete('/{unit}', [MmMasterDataController::class, 'deleteUnit'])->whereNumber('unit')->name('delete');
    });

    Route::prefix('material-master/variants')->name('mm05.variants.')->group(function () {
        Route::get('/', [MmSpecificationController::class, 'variants'])->name('index');
        Route::post('/', [MmSpecificationController::class, 'storeVariant'])->name('store');
        Route::put('/{variant}', [MmSpecificationController::class, 'updateVariant'])->whereNumber('variant')->name('update');
        Route::post('/{variant}/deactivate', [MmSpecificationController::class, 'deactivateVariant'])->whereNumber('variant')->name('deactivate');
        Route::put('/{variant}/attributes', [MmSpecificationController::class, 'setVariantAttributes'])->whereNumber('variant')->name('attributes.update');
    });

    // Compatibility endpoints for existing global MM-05 specification navigation.
    Route::prefix('material-master/specifications')->name('mm05.specifications.')->group(function () {
        Route::get('/', [MmSpecificationController::class, 'definitions'])->name('index');
        Route::post('/', [MmSpecificationController::class, 'storeDefinition'])->name('store');
        Route::put('/{definition}', [MmSpecificationController::class, 'updateDefinition'])->whereNumber('definition')->name('update');
        Route::post('/{definition}/deactivate', [MmSpecificationController::class, 'deactivateDefinition'])->whereNumber('definition')->name('deactivate');
    });

    // MM-06.1 Material Price Bank
    Route::prefix('material-master/material-prices')->name('mm05.prices.')->group(function () {
        Route::get('/', [MmMaterialPriceController::class, 'index'])->name('index');
        Route::post('/direct', [MmMaterialPriceController::class, 'storeDirect'])->name('direct.store');
        Route::post('/', [MmMaterialPriceController::class, 'store'])->name('store');
    });

});
