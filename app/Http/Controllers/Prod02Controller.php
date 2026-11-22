<?php

namespace App\Http\Controllers;

use App\Models\ClientBrief;
use App\Models\ClientBriefEvent;
use App\Services\ClientBriefAttachmentService;
use App\Services\Prod02DuplicateChecker;
use App\Services\Prod02RequestNumberIssuer;
use App\Support\ProjectDescriptionRichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Prod02Controller extends Controller
{
    private const COMMUNICATION_SOURCES = ['WhatsApp', 'Telepon', 'Email', 'Meeting', 'Lainnya'];

    private const PROJECT_TYPES = [
        'Exhibition/Booth',
        'Event',
        'Display',
        'Branding',
        'Printing',
        'Other',
    ];

    private const PRIORITIES = ['Normal', 'High', 'Urgent'];

    private const PROJECT_INFORMATION_FIELDS = [
        'project_name',
        'project_type',
        'brand',
        'estimated_value',
        'priority',
        'project_objectives',
        'project_description',
    ];

    private const CLIENT_CONTACT_FIELDS = [
        'client_type',
        'client_address',
        'client_position',
        'client_email',
        'client_phone',
        'client_notes',
    ];

    private const BRIEF_REQUIREMENT_FIELDS = [
        'key_requirements',
        'target_audience',
        'reference_inspiration',
    ];

    private function requireMarketing(Request $request): void
    {
        $allowed = DB::table('bpm_role_user as ru')
            ->join('bpm_roles as r', 'r.id', '=', 'ru.bpm_role_id')
            ->where('ru.user_id', $request->user()->id)
            ->where('r.code', 'MARKETING')->exists();
        abort_unless($allowed, 403, 'Akses ini hanya untuk Marketing.');
    }

    private function validateDraft(Request $request): array
    {
        $projectInformationAvailable = $this->projectInformationSchemaAvailable();
        $clientContactFieldsAvailable = $this->clientContactSchemaAvailable();

        if (! $projectInformationAvailable) {
            foreach (self::PROJECT_INFORMATION_FIELDS as $field) {
                if (filled($request->input($field))) {
                    throw ValidationException::withMessages([
                        'project_information' => 'Informasi proyek belum dapat disimpan karena migration Project Information belum diterapkan.',
                    ]);
                }
            }
        }

        if (! $clientContactFieldsAvailable) {
            foreach (self::CLIENT_CONTACT_FIELDS as $field) {
                if (filled($request->input($field))) {
                    throw ValidationException::withMessages([
                        'client_contact' => 'Detail kontak tambahan belum dapat disimpan karena migration Client & Contact belum diterapkan.',
                    ]);
                }
            }
        }

        $briefRequirementFieldsAvailable = $this->briefRequirementSchemaAvailable();

        if (! $briefRequirementFieldsAvailable) {
            foreach (self::BRIEF_REQUIREMENT_FIELDS as $field) {
                if (filled($request->input($field))) {
                    throw ValidationException::withMessages([
                        'brief_requirements' => 'Key Requirements, Target Audience, dan Reference/Inspiration belum dapat disimpan karena migration Brief & Requirement belum diterapkan.',
                    ]);
                }
            }
        }

        $rules = [
            'client_name' => ['nullable', 'string', 'max:255'],
            'communication_source' => ['nullable', 'string', Rule::in(self::COMMUNICATION_SOURCES)],
            'requirement_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'client_pic' => ['nullable', 'string', 'max:255'],
            'target_date' => ['nullable', 'date'],
            'work_location' => ['nullable', 'string', 'max:255'],
        ];

        if ($projectInformationAvailable) {
            $rules += [
                'project_name' => ['nullable', 'string', 'max:255'],
                'project_type' => ['nullable', 'string', Rule::in(self::PROJECT_TYPES)],
                'brand' => ['nullable', 'string', 'max:255'],
                'estimated_value' => [
                    'nullable',
                    'numeric',
                    'decimal:0,2',
                    'min:0',
                    'regex:/\\A\\d{1,16}(?:\\.\\d{1,2})?\\z/',
                ],
                'priority' => ['nullable', 'string', Rule::in(self::PRIORITIES)],
                'project_objectives' => ['nullable', 'string', 'max:10000'],
                'project_description' => ['nullable', 'string', 'max:10000', function ($attribute, $value, $fail) {
                    if (! ProjectDescriptionRichText::valid($value)) {
                        $fail('Deskripsi Proyek mengandung format tidak valid atau melebihi batas penyimpanan.');
                    }
                }],
            ];
        }

        if ($clientContactFieldsAvailable) {
            $rules += [
                'client_type' => ['nullable', 'string', 'max:100'],
                'client_address' => ['nullable', 'string', 'max:5000'],
                'client_position' => ['nullable', 'string', 'max:255'],
                'client_email' => ['nullable', 'email', 'max:255'],
                'client_phone' => ['nullable', 'string', 'max:50'],
                'client_notes' => ['nullable', 'string', 'max:5000'],
            ];
        }

        if ($briefRequirementFieldsAvailable) {
            $rules += [
                'key_requirements' => ['nullable', 'string', 'max:10000'],
                'target_audience' => ['nullable', 'string', 'max:5000'],
                'reference_inspiration' => ['nullable', 'string', 'max:5000'],
            ];
        }

        return $request->validate($rules);
    }

    private function projectInformationSchemaAvailable(): bool
    {
        return Schema::hasColumns('client_briefs', self::PROJECT_INFORMATION_FIELDS);
    }

    private function clientContactSchemaAvailable(): bool
    {
        return Schema::hasColumns('client_briefs', self::CLIENT_CONTACT_FIELDS);
    }

    private function briefRequirementSchemaAvailable(): bool
    {
        return Schema::hasColumns('client_briefs', self::BRIEF_REQUIREMENT_FIELDS);
    }

    /**
     * @return array{projectInformationAvailable: bool, clientContactFieldsAvailable: bool, briefRequirementFieldsAvailable: bool, projectTypes: array<int, string>, priorities: array<int, string>}
     */
    private function projectInformationFormData(): array
    {
        return [
            'projectInformationAvailable' => $this->projectInformationSchemaAvailable(),
            'clientContactFieldsAvailable' => $this->clientContactSchemaAvailable(),
            'briefRequirementFieldsAvailable' => $this->briefRequirementSchemaAvailable(),
            'projectTypes' => self::PROJECT_TYPES,
            'priorities' => self::PRIORITIES,
        ];
    }

    public function index(Request $request)
    {
        $this->requireMarketing($request);
        $briefs = ClientBrief::with('primaryBrief')
            ->where('marketing_user_id', $request->user()->id)
            ->latest('id')->paginate(15);

        return view('prod02.index', compact('briefs'));
    }

    public function create(Request $request, ClientBriefAttachmentService $attachments)
    {
        $this->requireMarketing($request);

        return view('prod02.form', [
            'brief' => new ClientBrief,
            ...$this->projectInformationFormData(),
            'communicationSources' => self::COMMUNICATION_SOURCES,
            'duplicateCandidates' => [],
            'duplicateCheckEvent' => null,
            'duplicateMessage' => null,
            'latestDuplicateDecision' => null,
            'canSubmit' => false,
            'submitMessage' => null,
            'currentStep' => 1,
            'attachmentsAvailable' => false,
            'attachments' => collect(),
            'attachmentMaxUploadKilobytes' => $attachments->maximumFileSizeKilobytes(),
        ]);
    }

    public function store(Request $request)
    {
        $this->requireMarketing($request);
        $nextStep = $this->validatedNextStep($request);
        $data = $this->validateDraft($request);
        $brief = DB::transaction(function () use ($data, $request) {
            $brief = new ClientBrief($data);
            // UI V4.2: compatibility field for existing submission and duplicate rules.
            // Only derive it from a known project type; never overwrite legacy with blank.
            if (filled($brief->project_type)) {
                $brief->requirement_type = $brief->project_type;
            }
            $brief->marketing_user_id = $request->user()->id;
            $brief->status = 'DRAFT';
            $brief->save();
            $brief->events()->create([
                'event_type' => 'DRAFT_CREATED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => null,
                'after_data' => $brief->only(['id', 'status', 'marketing_user_id', ...array_keys($data)]),
                'occurred_at' => now(),
            ]);

            return $brief;
        });

        return $this->draftEditRedirect($brief, $nextStep, 'Draft berhasil dibuat.');
    }

    public function edit(
        Request $request,
        ClientBrief $brief,
        ClientBriefAttachmentService $attachments,
    ) {
        $this->requireMarketing($request);
        abort_unless($brief->marketing_user_id === $request->user()->id, 403);
        abort_unless($brief->status === 'DRAFT', 403);
        $latestDuplicateDecision = $brief->events()
            ->where('event_type', 'DUPLICATE_DECISION')
            ->latest('id')
            ->first();
        $submissionState = $this->submissionState($brief, app(Prod02DuplicateChecker::class));
        $submissionRequirements = $this->submissionRequirements($brief);
        $attachmentsAvailable = $attachments->schemaIsReady();
        $activeAttachments = $attachmentsAvailable
            ? $brief->attachments()->with('uploader')->latest('id')->get()
            : collect();

        return view('prod02.form', [
            'brief' => $brief,
            ...$this->projectInformationFormData(),
            'communicationSources' => self::COMMUNICATION_SOURCES,
            'duplicateCandidates' => $submissionState['check_event']?->after_data['candidates'] ?? [],
            'duplicateCheckEvent' => $submissionState['check_event'],
            'duplicateMessage' => null,
            'latestDuplicateDecision' => $latestDuplicateDecision,
            'canSubmit' => $submissionState['can_submit'],
            'submitMessage' => $submissionState['message'],
            'submissionRequirements' => $submissionRequirements,
            'currentStep' => match ($request->query('step')) {
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                default => 1,
            },
            'attachmentsAvailable' => $attachmentsAvailable,
            'attachments' => $activeAttachments,
            'attachmentMaxUploadKilobytes' => $attachments->maximumFileSizeKilobytes(),
        ]);
    }

    public function checkDuplicates(Request $request, ClientBrief $brief, Prod02DuplicateChecker $checker)
    {
        $this->requireMarketing($request);

        [$brief, $checkEvent] = DB::transaction(function () use ($request, $brief, $checker) {
            $brief = ClientBrief::whereKey($brief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeDraft($request, $brief);
            $result = $checker->findCandidates($brief);

            $checkEvent = $brief->events()->create([
                'event_type' => 'DUPLICATE_CHECKED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => null,
                'after_data' => [
                    'algorithm_version' => $result['algorithm_version'],
                    'draft_fingerprint' => $result['fingerprint'],
                    'input' => $result['input'],
                    'candidates' => $result['candidates'],
                ],
                'occurred_at' => now(),
            ]);

            return [$brief, $checkEvent];
        });

        return $this->duplicateReviewView($brief, $checkEvent);
    }

    public function submit(
        Request $request,
        ClientBrief $brief,
        Prod02DuplicateChecker $checker,
        Prod02RequestNumberIssuer $numberIssuer
    ) {
        $this->requireMarketing($request);

        $result = DB::transaction(function () use ($request, $brief, $checker, $numberIssuer) {
            $brief = ClientBrief::whereKey($brief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeDraft($request, $brief);
            $this->validateRequiredForSubmission($brief);

            $latestDecision = $brief->events()
                ->where('event_type', 'DUPLICATE_DECISION')
                ->latest('id')
                ->first();

            if (($latestDecision?->after_data['decision'] ?? null) !== 'new') {
                throw ValidationException::withMessages([
                    'submission' => 'Periksa kandidat dan pilih Permintaan Baru yang masih berlaku sebelum submit.',
                ]);
            }

            $numberIssuer->lockRecordedCandidateSet();
            $submissionState = $this->submissionState($brief, $checker);

            if (! $submissionState['can_submit']) {
                $checkEvent = $submissionState['check_event'];

                if ($checkEvent === null || $submissionState['stale']) {
                    $freshResult = $checker->findCandidates($brief);
                    $checkEvent = $this->recordDuplicateCheck($brief, $request, $freshResult);
                }

                return [
                    'stale' => true,
                    'brief' => $brief,
                    'check_event' => $checkEvent,
                    'message' => $submissionState['message'],
                ];
            }

            $recordedAt = now();
            $year = (int) $recordedAt->format('Y');
            $sequenceNumber = $numberIssuer->nextNumber($year);
            $requestNumber = $numberIssuer->format($year, $sequenceNumber);
            $before = [
                'status' => $brief->status,
                'request_number' => $brief->request_number,
                'recorded_at' => $brief->recorded_at?->toISOString(),
                'client_name' => $brief->client_name,
                'communication_source' => $brief->communication_source,
                'requirement_type' => $brief->requirement_type,
                'description' => $brief->description,
                'primary_brief_id' => $brief->primary_brief_id,
            ];

            $brief->status = 'RECORDED';
            $brief->request_number = $requestNumber;
            $brief->recorded_at = $recordedAt;
            $brief->save();

            $after = [
                'status' => $brief->status,
                'request_number' => $brief->request_number,
                'recorded_at' => $recordedAt->toISOString(),
                'client_name' => $brief->client_name,
                'communication_source' => $brief->communication_source,
                'requirement_type' => $brief->requirement_type,
                'description' => $brief->description,
                'primary_brief_id' => $brief->primary_brief_id,
                'decision' => 'new',
                'duplicate_check_event_id' => $submissionState['check_event']->id,
            ];

            $brief->events()->create([
                'event_type' => 'REQUEST_RECORDED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => $before,
                'after_data' => $after,
                'occurred_at' => $recordedAt,
            ]);

            return [
                'stale' => false,
                'brief' => $brief,
            ];
        });

        if ($result['stale']) {
            return $this->duplicateReviewView(
                $result['brief'],
                $result['check_event'],
                $result['message'] ?? 'Hasil pemeriksaan berubah. Tinjau ulang kandidat dan putuskan kembali sebelum submit.'
            );
        }

        return redirect()->route('prod02.show', $result['brief'])
            ->with('message', 'Permintaan berhasil direkam.');
    }

    public function show(Request $request, ClientBrief $brief)
    {
        $this->requireMarketing($request);
        abort_unless($brief->marketing_user_id === $request->user()->id, 403);
        abort_unless(in_array($brief->status, ['RECORDED', 'DUPLICATE', 'CANCELLED'], true), 404);

        return view('prod02.show', [
            'brief' => $brief->load('primaryBrief'),
        ]);
    }

    public function decideDuplicate(Request $request, ClientBrief $brief, Prod02DuplicateChecker $checker)
    {
        $this->requireMarketing($request);
        $data = $request->validate([
            'duplicate_check_event_id' => ['required', 'integer'],
            'decision' => ['required', Rule::in(['new', 'continuation', 'duplicate'])],
            'primary_brief_id' => ['nullable', 'integer'],
            'decision_reason' => ['nullable', 'required_if:decision,continuation,duplicate', 'string', 'max:2000'],
        ]);

        if ($data['decision'] === 'new' && ! empty($data['primary_brief_id'])) {
            throw ValidationException::withMessages([
                'primary_brief_id' => 'Permintaan baru tidak menggunakan referensi permintaan utama.',
            ]);
        }

        $result = DB::transaction(function () use ($request, $brief, $checker, $data) {
            $brief = ClientBrief::whereKey($brief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeDraft($request, $brief);

            $latestCheck = $brief->events()->where('event_type', 'DUPLICATE_CHECKED')->latest('id')->first();
            $latestDecision = $brief->events()->where('event_type', 'DUPLICATE_DECISION')->latest('id')->first();
            $checkEvent = $brief->events()
                ->whereKey($data['duplicate_check_event_id'])
                ->where('event_type', 'DUPLICATE_CHECKED')
                ->first();
            $freshResult = $checker->findCandidates($brief);
            $draftChangedAfterCheck = $checkEvent !== null && $brief->events()
                ->where('event_type', 'DRAFT_UPDATED')
                ->where('id', '>', $checkEvent->id)
                ->get()
                ->contains(fn (ClientBriefEvent $event): bool => $event->after_data['duplicate_fields_changed'] ?? false);

            $isFresh = $checkEvent !== null
                && $latestCheck?->is($checkEvent)
                && ($latestDecision === null || $latestDecision->id < $checkEvent->id)
                && ! $draftChangedAfterCheck
                && ($checkEvent->after_data['draft_fingerprint'] ?? null) === $freshResult['fingerprint']
                && ($checkEvent->after_data['candidates'] ?? null) == $freshResult['candidates'];

            if (! $isFresh) {
                $freshCheckEvent = $this->recordDuplicateCheck($brief, $request, $freshResult);

                return [
                    'stale' => true,
                    'brief' => $brief,
                    'check_event' => $freshCheckEvent,
                ];
            }

            $primaryBrief = null;

            if ($data['decision'] !== 'new') {
                if (empty($data['primary_brief_id'])) {
                    throw ValidationException::withMessages([
                        'primary_brief_id' => 'Pilih permintaan utama dari kandidat yang diperiksa.',
                    ]);
                }

                $candidateIds = array_column($freshResult['candidates'], 'id');

                if (! in_array((int) $data['primary_brief_id'], $candidateIds, true)) {
                    throw ValidationException::withMessages([
                        'primary_brief_id' => 'Permintaan utama harus dipilih dari kandidat RECORDED yang ditemukan.',
                    ]);
                }

                $primaryBrief = ClientBrief::query()
                    ->whereKey($data['primary_brief_id'])
                    ->where('status', 'RECORDED')
                    ->lockForUpdate()
                    ->first();

                if ($primaryBrief === null || $this->wouldCreatePrimaryCycle($primaryBrief, $brief->id)) {
                    throw ValidationException::withMessages([
                        'primary_brief_id' => 'Referensi utama tidak valid atau membentuk relasi siklik.',
                    ]);
                }
            }

            $before = [
                'status' => $brief->status,
                'primary_brief_id' => $brief->primary_brief_id,
                'duplicate_reason' => $brief->duplicate_reason,
            ];

            $brief->primary_brief_id = $primaryBrief?->id;
            $brief->duplicate_reason = $data['decision'] === 'duplicate'
                ? $data['decision_reason']
                : null;

            if ($data['decision'] === 'duplicate') {
                $brief->status = 'DUPLICATE';
            }

            $brief->save();

            $after = [
                'status' => $brief->status,
                'primary_brief_id' => $brief->primary_brief_id,
                'duplicate_reason' => $brief->duplicate_reason,
                'decision' => $data['decision'],
                'decision_reason' => $data['decision_reason'] ?? null,
                'source_communication' => $freshResult['input']['communication_source'],
                'primary_request_number' => $primaryBrief?->request_number,
                'duplicate_check_event_id' => $checkEvent->id,
            ];

            $brief->events()->create([
                'event_type' => 'DUPLICATE_DECISION',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => $before,
                'after_data' => $after,
                'reason' => $data['decision_reason'] ?? null,
                'occurred_at' => now(),
            ]);

            return [
                'stale' => false,
                'brief' => $brief,
            ];
        });

        if ($result['stale']) {
            return $this->duplicateReviewView(
                $result['brief'],
                $result['check_event'],
                'Draft atau daftar kandidat berubah. Hasil pemeriksaan diperbarui; tinjau kembali sebelum memutuskan.'
            );
        }

        return redirect()->route('prod02.edit', $result['brief'])
            ->with('message', 'Keputusan pemeriksaan duplikasi berhasil dicatat.');
    }

    public function update(Request $request, ClientBrief $brief, Prod02DuplicateChecker $checker)
    {
        $this->requireMarketing($request);
        $nextStep = $this->validatedNextStep($request);
        $data = $this->validateDraft($request);
        DB::transaction(function () use ($request, $brief, $data, $checker) {
            $brief = ClientBrief::whereKey($brief->id)->lockForUpdate()->firstOrFail();
            $this->authorizeDraft($request, $brief);
            $inputBefore = $checker->inputSnapshot($brief);
            $before = [
                ...$brief->only(array_keys($data)),
                'primary_brief_id' => $brief->primary_brief_id,
                'duplicate_check_input' => $inputBefore,
            ];
            $legacyRequirementType = $brief->requirement_type;
            $brief->fill($data);
            // UI V4.2: keep legacy field and duplicate fingerprint aligned.
            if (filled($brief->project_type)) {
                $brief->requirement_type = $brief->project_type;
            } else {
                $brief->requirement_type = $legacyRequirementType;
            }
            $inputAfter = $checker->inputSnapshot($brief);
            $duplicateFieldsChanged = $checker->fingerprint($inputBefore) !== $checker->fingerprint($inputAfter);

            if ($duplicateFieldsChanged) {
                $brief->primary_brief_id = null;
            }

            $brief->save();
            $after = [
                ...$brief->only(array_keys($data)),
                'primary_brief_id' => $brief->primary_brief_id,
                'duplicate_check_input' => $inputAfter,
                'duplicate_fields_changed' => $duplicateFieldsChanged,
            ];
            $brief->events()->create([
                'event_type' => 'DRAFT_UPDATED',
                'actor_user_id' => $request->user()->id,
                'actor_role' => 'MARKETING',
                'before_data' => $before,
                'after_data' => $after,
                'occurred_at' => now(),
            ]);
        });

        return $this->draftEditRedirect($brief, $nextStep, 'Draft berhasil diperbarui.');
    }

    private function validatedNextStep(Request $request): int
    {
        $data = $request->validate([
            'next_step' => ['sometimes', Rule::in(['1', '2', '3', '4'])],
        ]);

        return (int) ($data['next_step'] ?? 1);
    }

    private function draftEditRedirect(ClientBrief $brief, int $step, string $message)
    {
        $routeParameters = ['brief' => $brief];

        if ($step > 1) {
            $routeParameters['step'] = $step;
        }

        return redirect()->route('prod02.edit', $routeParameters)->with('message', $message);
    }

    private function authorizeDraft(Request $request, ClientBrief $brief): void
    {
        abort_unless(
            $brief->marketing_user_id === $request->user()->id && $brief->status === 'DRAFT',
            403
        );
    }

    private function duplicateReviewView(
        ClientBrief $brief,
        ClientBriefEvent $checkEvent,
        ?string $message = null
    ) {
        return view('prod02.form', [
            'brief' => $brief,
            ...$this->projectInformationFormData(),
            'communicationSources' => self::COMMUNICATION_SOURCES,
            'duplicateCandidates' => $checkEvent->after_data['candidates'] ?? [],
            'duplicateCheckEvent' => $checkEvent,
            'duplicateMessage' => $message,
            'latestDuplicateDecision' => $brief->events()
                ->where('event_type', 'DUPLICATE_DECISION')
                ->latest('id')
                ->first(),
            'canSubmit' => false,
            'submitMessage' => $message,
            'currentStep' => 1,
        ]);
    }

    /**
     * @return array{can_submit: bool, stale: bool, message: ?string, check_event: ?ClientBriefEvent}
     */
    private function submissionState(ClientBrief $brief, Prod02DuplicateChecker $checker): array
    {
        $checkEvent = $brief->events()
            ->where('event_type', 'DUPLICATE_CHECKED')
            ->latest('id')
            ->first();
        $decision = $brief->events()
            ->where('event_type', 'DUPLICATE_DECISION')
            ->latest('id')
            ->first();

        if ($checkEvent === null || $decision === null) {
            return [
                'can_submit' => false,
                'stale' => false,
                'message' => 'Periksa kandidat dan pilih Permintaan Baru sebelum submit.',
                'check_event' => $checkEvent,
            ];
        }

        $currentResult = $checker->findCandidates($brief);
        $draftChangedAfterCheck = $brief->events()
            ->where('event_type', 'DRAFT_UPDATED')
            ->where('id', '>', $checkEvent->id)
            ->get()
            ->contains(fn (ClientBriefEvent $event): bool => $event->after_data['duplicate_fields_changed'] ?? false);
        $fresh = ! $draftChangedAfterCheck
            && ($checkEvent->after_data['draft_fingerprint'] ?? null) === $currentResult['fingerprint']
            && ($checkEvent->after_data['candidates'] ?? null) == $currentResult['candidates'];
        $isNewDecision = ($decision->after_data['decision'] ?? null) === 'new'
            && (int) ($decision->after_data['duplicate_check_event_id'] ?? 0) === $checkEvent->id
            && $decision->id > $checkEvent->id;

        return [
            'can_submit' => $fresh && $isNewDecision,
            'stale' => ! $fresh,
            'message' => ! $fresh
                ? 'Draft atau kandidat berubah. Periksa ulang kandidat dan putuskan kembali sebelum submit.'
                : ($isNewDecision ? null : 'Hanya keputusan Permintaan Baru yang masih berlaku dapat disubmit.'),
            'check_event' => $checkEvent,
        ];
    }

    private function validateRequiredForSubmission(ClientBrief $brief): void
    {
        $this->submissionValidator($brief)->validate();
    }

    /**
     * @return array{ready: bool, fields: array<int, array{label: string, ready: bool}>}
     */
    private function submissionRequirements(ClientBrief $brief): array
    {
        $validator = $this->submissionValidator($brief);
        $ready = $validator->passes();
        $fields = [];

        foreach ([
            'client_name' => 'Client / Company',
            'communication_source' => 'Communication Source',
            'requirement_type' => 'Jenis Proyek (Tahap 1)',
            'description' => 'Brief Summary',
        ] as $field => $label) {
            $fields[] = [
                'label' => $label,
                'ready' => ! $validator->errors()->has($field),
            ];
        }

        return [
            'ready' => $ready,
            'fields' => $fields,
        ];
    }

    private function submissionValidator(ClientBrief $brief): \Illuminate\Validation\Validator
    {
        $attributes = [
            'client_name' => is_string($brief->client_name) ? trim($brief->client_name) : $brief->client_name,
            'communication_source' => is_string($brief->communication_source)
                ? trim($brief->communication_source)
                : $brief->communication_source,
            'requirement_type' => is_string($brief->requirement_type)
                ? trim($brief->requirement_type)
                : $brief->requirement_type,
            'description' => is_string($brief->description) ? trim($brief->description) : $brief->description,
        ];

        return Validator::make($attributes, [
            'client_name' => ['required', 'string', 'max:255'],
            'communication_source' => ['required', 'string', Rule::in(self::COMMUNICATION_SOURCES)],
            'requirement_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function recordDuplicateCheck(
        ClientBrief $brief,
        Request $request,
        array $result
    ): ClientBriefEvent {
        return $brief->events()->create([
            'event_type' => 'DUPLICATE_CHECKED',
            'actor_user_id' => $request->user()->id,
            'actor_role' => 'MARKETING',
            'before_data' => null,
            'after_data' => [
                'algorithm_version' => $result['algorithm_version'],
                'draft_fingerprint' => $result['fingerprint'],
                'input' => $result['input'],
                'candidates' => $result['candidates'],
            ],
            'occurred_at' => now(),
        ]);
    }

    private function wouldCreatePrimaryCycle(ClientBrief $primaryBrief, int $draftId): bool
    {
        $visited = [];
        $current = $primaryBrief;

        while ($current !== null) {
            if ($current->id === $draftId || in_array($current->id, $visited, true)) {
                return true;
            }

            $visited[] = $current->id;
            $current = $current->primary_brief_id === null
                ? null
                : ClientBrief::query()
                    ->select(['id', 'primary_brief_id'])
                    ->find($current->primary_brief_id);
        }

        return false;
    }
}
