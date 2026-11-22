@extends('layouts.app')

@php
    $isDuplicateReview = request()->routeIs('prod02.duplicate-check');
    $isStepOne = !$isDuplicateReview && ($currentStep ?? 1) === 1;
    $isStepTwo = !$isDuplicateReview && ($currentStep ?? 1) === 2;
    $isStepThree = !$isDuplicateReview && ($currentStep ?? 1) === 3;
    $isStepFour = !$isDuplicateReview && ($currentStep ?? 1) === 4;
    $isStepFive = !$isDuplicateReview && ($currentStep ?? 1) === 5;
    $editDraftRouteKey = $brief->exists && filled($brief->getKey()) ? $brief->getKey() : null;
    $editDraftUrl = $editDraftRouteKey ? route('prod02.edit', ['brief' => $editDraftRouteKey]) : null;
    $editStepThreeUrl = $editDraftRouteKey ? route('prod02.edit', ['brief' => $editDraftRouteKey, 'step' => 3]) : null;
    $editStepFourUrl = $editDraftRouteKey ? route('prod02.edit', ['brief' => $editDraftRouteKey, 'step' => 4]) : null;
    $editStepFiveUrl = $editDraftRouteKey ? route('prod02.edit', ['brief' => $editDraftRouteKey, 'step' => 5]) : null;
@endphp

@php
    $pageTitle = $isDuplicateReview
        ? 'Pemeriksaan Kemungkinan Duplikasi'
        : ($isStepOne
            ? ($brief->exists ? 'Ubah Permintaan Klien #'.$brief->id : 'Buat Permintaan Klien')
            : ($isStepTwo ? 'Klien & Kontak'
                : ($isStepThree ? 'Brief & Kebutuhan'
                    : ($isStepFour ? 'Lampiran' : 'Tinjau & Ajukan'))));

    $pageEyebrow = 'Permintaan Klien · PROD-02';

    $pageDescription = $isDuplicateReview
        ? 'Tinjau kemungkinan duplikasi sebelum pengajuan resmi.'
        : ($isStepOne
            ? 'Catat informasi awal proyek sebelum melanjutkan ke tahap berikutnya.'
            : 'Lengkapi dan periksa informasi permintaan klien.');

    $documentTitle = $pageTitle.' — PROD-02';
@endphp

@section('title', $documentTitle)
@section('eyebrow', $pageEyebrow)
@section('page-title', $pageTitle)
@section('page-description', $pageDescription)

@section('content')
    <div class="stack ui03-project-form {{ $isStepOne ? 'ui03-step-one' : ($isStepTwo ? 'ui03-step-two' : ($isStepThree ? 'ui03-step-three' : ($isStepFour ? 'ui03-step-four ui03-step-four-v1' : ''))) }}">
        <div class="button-row {{ $isStepOne ? 'ui03-step-one-back-row' : '' }}">
            @if ($isDuplicateReview && $editDraftUrl !== null)
                <a class="button button-secondary" href="{{ $editDraftUrl }}"><span aria-hidden="true">←</span> Kembali ke Edit Draft #{{ $brief->id }}</a>
            @else
                <a class="button button-secondary" href="{{ route('prod02.index') }}"><span aria-hidden="true">←</span> {{ $isStepOne || $isStepFour ? 'Kembali ke Daftar Permintaan' : 'Back to Project List' }}</a>
            @endif
        </div>

        @if (session('message'))
            <div class="notice notice-success ui03-dismissible-notice" role="status" data-ui03-auto-hide>{{ session('message') }}</div>
        @endif
        @if (!empty($duplicateMessage))
            <div class="notice ui03-dismissible-notice" role="status" data-ui03-auto-hide>{{ $duplicateMessage }}</div>
        @endif
        @if (!empty($submitMessage) && !$isStepFive)
            <div class="notice notice-warning ui03-dismissible-notice" role="status" data-ui03-auto-hide>{{ $submitMessage }}</div>
        @endif

        @if ($errors->any())
            <div class="alert-error" role="alert" aria-labelledby="validation-heading">
                <strong id="validation-heading">Periksa kembali informasi berikut:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isStepOne || $isStepTwo || $isStepThree || $isStepFour)
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('.ui03-step-one [data-ui03-auto-hide], .ui03-step-two [data-ui03-auto-hide], .ui03-step-three [data-ui03-auto-hide], .ui03-step-four [data-ui03-auto-hide]').forEach(function (notice) {
                        window.setTimeout(function () {
                            if (notice.closest('.ui03-step-two')) {
                                notice.style.maxHeight =
                                    notice.scrollHeight + 'px';
                                notice.offsetHeight;
                            }
                            notice.classList.add('is-hiding');
                            window.setTimeout(function () { notice.hidden = true; }, 350);
                        }, 5000);
                    });
                });
            </script>
        @endif
        @unless ($isDuplicateReview)
        <nav class="ui03-stepper-card" aria-label="Tahapan permintaan klien">
        <ol class="ui03-stepper">
            <li class="ui03-step {{ ($currentStep ?? 1) > 1 ? 'is-complete' : 'is-active' }}" @if(($currentStep ?? 1) === 1) aria-current="step" @endif>
                <span class="ui03-step-number">1</span>
                <span class="ui03-step-copy"><strong>Informasi Proyek</strong><small>{{ ($currentStep ?? 1) > 1 ? 'Tersimpan' : 'Tahap Saat Ini' }}</small></span>
            </li>
            <li class="ui03-step {{ ($currentStep ?? 1) > 2 ? 'is-complete' : '' }} @if($isStepTwo) is-active @endif" @if($isStepTwo) aria-current="step" @endif>
                <span class="ui03-step-number">2</span>
                <span class="ui03-step-copy"><strong>Klien &amp; Kontak</strong><small>{{ $isStepTwo ? 'Tahap Saat Ini' : (($currentStep ?? 1) > 2 ? 'Tersimpan' : 'Tahap Berikutnya') }}</small></span>
            </li>
            <li class="ui03-step {{ ($currentStep ?? 1) > 3 ? 'is-complete' : '' }} @if($isStepThree) is-active @endif" @if($isStepThree) aria-current="step" @endif>
                <span class="ui03-step-number">3</span>
                <span class="ui03-step-copy"><strong>Brief &amp; Kebutuhan</strong><small>{{ $isStepThree ? 'Tahap Saat Ini' : (($currentStep ?? 1) > 3 ? 'Tersimpan' : 'Tahap Berikutnya') }}</small></span>
            </li>
            <li class="ui03-step {{ ($currentStep ?? 1) > 4 ? 'is-complete' : '' }} @if($isStepFour) is-active @endif" @if($isStepFour) aria-current="step" @endif>
                <span class="ui03-step-number">4</span>
                <span class="ui03-step-copy"><strong>Lampiran</strong><small>{{ $isStepFour ? 'Tahap Saat Ini' : (($currentStep ?? 1) > 4 ? 'Tersimpan' : 'Tahap Berikutnya') }}</small></span>
            </li>
            <li class="ui03-step @if($isStepFive) is-active @endif" @if($isStepFive) aria-current="step" @endif>
                <span class="ui03-step-number">5</span>
                <span class="ui03-step-copy"><strong>Tinjau &amp; Ajukan</strong><small>{{ $isStepFive ? 'Tahap Saat Ini' : 'Tahap Berikutnya' }}</small></span>
            </li>
        </ol>
        </nav>

        @if (!$isStepTwo && !$isStepThree && !$isStepFour && !$isStepFive && !$projectInformationAvailable)
        <div class="notice notice-warning ui03-schema-notice" role="status">
            Informasi proyek ditampilkan sebagai pratinjau dan belum dapat disimpan karena pembaruan struktur belum diterapkan. Kolom PROD-02 yang tersedia tetap dapat disimpan sebagai Draft.
        </div>
        @endif

        @if (!$isStepFour && !$isStepFive)
        @unless ($isStepOne)
        <form method="POST" action="{{ $brief->exists ? route('prod02.update', $brief) : route('prod02.store') }}">
        @csrf
        @if ($brief->exists)
            @method('PUT')
        @endif
        @endunless

        @if (!$isStepTwo && !$isStepThree && !$isStepFour)
        <div class="ui03-form-layout">
            <div class="ui03-form-main">
                @if ($isStepOne)
                <form id="ui03-step-one-form" method="POST" action="{{ $brief->exists ? route('prod02.update', $brief) : route('prod02.store') }}">
                    @csrf
                    @if ($brief->exists) @method('PUT') @endif
                @endif
                <section class="card stack ui03-project-card" aria-labelledby="project-information-heading">
                    <div class="section-heading">
                        <span class="ui03-custom-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 9h6M9 13h6M9 17h4"/></svg></span>
                        <div>
                            <span class="ui03-section-kicker">TAHAP 1 · INFORMASI PROYEK</span>
                            <h2 id="project-information-heading">Informasi Proyek</h2>
                            <p>Catat informasi dasar proyek. Kolom opsional dapat dilengkapi jika sudah diketahui.</p>
                        </div>
                        @if ($brief->exists)
                            <span class="status-badge status-draft">DRAFT</span>
                        @endif
                    </div>

                    <div class="ui03-project-columns">
                        <div class="ui03-project-column">
                            <div class="field">
                                <label for="project_code">Kode Proyek <span class="muted">(otomatis)</span></label>
                                <output class="ui03-readonly-field ui03-project-code" id="project_code">
                                    {{ $brief->request_number ?: 'Diterbitkan setelah pengajuan' }}
                                </output>
                                <span class="field-hint">Kode proyek resmi diterbitkan setelah pengajuan berhasil.</span>
                            </div>

                            <div class="field">
                                <label for="project_name">Nama Proyek <span class="muted">(opsional)</span></label>
                                <input id="project_name" name="project_name" maxlength="255"
                                       value="{{ old('project_name', $brief->project_name) }}"
                                       placeholder="Masukkan nama proyek"
                                       @disabled(! $projectInformationAvailable)
                                       @if($errors->has('project_name')) aria-invalid="true" aria-describedby="project_name-error" @endif>
                                @error('project_name')<p class="field-error" id="project_name-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="project_type">Jenis Proyek <span class="muted">(opsional)</span></label>
                                <select id="project_type" name="project_type" @disabled(! $projectInformationAvailable)
                                        @if($errors->has('project_type')) aria-invalid="true" aria-describedby="project_type-error" @endif>
                                    <option value="">Pilih jenis proyek</option>
                                    @foreach ($projectTypes as $projectType)
                                        <option value="{{ $projectType }}" @selected(old('project_type', $brief->project_type) === $projectType)>{{ match ($projectType) {
                                            'Exhibition/Booth' => 'Pameran/Booth',
                                            'Event' => 'Acara',
                                            'Other' => 'Lainnya',
                                            default => $projectType,
                                        } }}</option>
                                    @endforeach
                                </select>
                                @error('project_type')<p class="field-error" id="project_type-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="target_date">Tanggal Target <span class="muted">(opsional)</span></label>
                                <input id="target_date" type="date" name="target_date"
                                       value="{{ old('target_date', $brief->target_date?->format('Y-m-d')) }}"
                                       @if($errors->has('target_date')) aria-invalid="true" aria-describedby="target_date-error" @endif>
                                @error('target_date')<p class="field-error" id="target_date-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="estimated_value">Estimasi Nilai (Rp) <span class="muted">(opsional)</span></label>
                                <div class="ui03-currency-input">
                                    <span aria-hidden="true">Rp</span>
                                    <input id="estimated_value" name="estimated_value" type="text" inputmode="decimal"
                                           maxlength="19" placeholder="Contoh: 250000000"
                                           value="{{ old('estimated_value', $brief->estimated_value) }}"
                                           @disabled(! $projectInformationAvailable)
                                           @if($errors->has('estimated_value')) aria-invalid="true" @endif
                                           aria-describedby="@if($errors->has('estimated_value'))estimated_value-error @endif estimated_value-hint estimated_value-preview">
                                </div>
                                <p class="field-hint" id="estimated_value-hint">Masukkan nilai dalam Rupiah, tanpa pemisah ribuan.</p>
                                <output class="ui03-currency-preview" id="estimated_value-preview" aria-live="polite"></output>
                                @error('estimated_value')<p class="field-error" id="estimated_value-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="ui03-project-column">
                            <div class="field">
                                <label for="brand">Merek <span class="muted">(opsional)</span></label>
                                <input id="brand" name="brand" maxlength="255" value="{{ old('brand', $brief->brand) }}"
                                       placeholder="Pilih atau masukkan merek" @disabled(! $projectInformationAvailable)
                                       @if($errors->has('brand')) aria-invalid="true" aria-describedby="brand-error" @endif>
                                @error('brand')<p class="field-error" id="brand-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="work_location">Lokasi Proyek <span class="muted">(opsional)</span></label>
                                <input id="work_location" name="work_location" maxlength="255"
                                       value="{{ old('work_location', $brief->work_location) }}"
                                       placeholder="Masukkan lokasi proyek"
                                       @if($errors->has('work_location')) aria-invalid="true" aria-describedby="work_location-error" @endif>
                                @error('work_location')<p class="field-error" id="work_location-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="project_pic_internal">Unit Bisnis / PIC Internal <span class="muted">(otomatis)</span></label>
                                <output class="ui03-readonly-field" id="project_pic_internal">{{ $brief->exists ? ($brief->marketing?->name ?? '—') : auth()->user()->name }} (Marketing)</output>
                            </div>

                            <div class="field">
                                <label for="priority">Prioritas <span class="muted">(opsional)</span></label>
                                <select id="priority" name="priority" @disabled(! $projectInformationAvailable)
                                        @if($errors->has('priority')) aria-invalid="true" aria-describedby="priority-error" @endif>
                                    <option value="">Pilih prioritas</option>
                                    @foreach ($priorities as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority', $brief->priority) === $priority)>{{ match ($priority) {
                                            'High' => 'Tinggi',
                                            'Urgent' => 'Mendesak',
                                            default => $priority,
                                        } }}</option>
                                    @endforeach
                                </select>
                                @error('priority')<p class="field-error" id="priority-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="field">
                                <label for="project_objectives">Tujuan Proyek <span class="muted">(opsional)</span></label>
                                <textarea id="project_objectives" name="project_objectives" rows="3" maxlength="10000"
                                          placeholder="Jelaskan tujuan proyek"
                                          @disabled(! $projectInformationAvailable)
                                          @if($errors->has('project_objectives')) aria-invalid="true" aria-describedby="project_objectives-error" @endif>{{ old('project_objectives', $brief->project_objectives) }}</textarea>
                                <span class="field-hint ui03-character-count" data-count-for="project_objectives">0/10,000</span>
                                @error('project_objectives')<p class="field-error" id="project_objectives-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card stack ui03-project-description-card" aria-labelledby="project-description-heading">
                    <div class="section-heading">
                        <span class="ui03-custom-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></svg></span>
                        <div>
                            <h2 id="project-description-heading">Deskripsi Proyek</h2>
                            <p>Jelaskan secara singkat latar belakang dan kebutuhan proyek.</p>
                        </div>
                    </div>
                    <div class="field">
                        <label for="project_description">Deskripsi Proyek <span class="muted">(opsional)</span></label>
                        <textarea id="project_description" name="project_description" data-sms-rich-source rows="4" maxlength="10000"
  @disabled(! $projectInformationAvailable)
  @if($errors->has('project_description')) aria-invalid="true" aria-describedby="project_description-error" @endif>{{ old('project_description', $brief->project_description) }}</textarea>
<div data-sms-rich-editor>
  <div class="sms-rich-shell">
    <div class="sms-rich-toolbar" role="toolbar" aria-label="Pemformatan deskripsi">
      <select data-rich-format aria-label="Format paragraf"><option value="p">Normal</option><option value="h3">Subjudul</option><option value="blockquote">Kutipan</option></select>
      <button type="button" data-rich-cmd="bold" aria-label="Tebal"><b>B</b></button>
      <button type="button" data-rich-cmd="italic" aria-label="Miring"><i>I</i></button>
      <button type="button" data-rich-cmd="underline" aria-label="Garis bawah"><u>U</u></button>
      <button type="button" data-rich-cmd="insertUnorderedList" aria-label="Daftar berpoin">• Daftar</button>
      <button type="button" data-rich-cmd="insertOrderedList" aria-label="Daftar bernomor">1. Daftar</button>
      <button type="button" data-rich-link aria-label="Tambah tautan">Tautan</button>
      <button type="button" data-rich-cmd="unlink" aria-label="Hapus tautan">Putus tautan</button>
      <button type="button" data-rich-cmd="removeFormat" aria-label="Hapus format">Hapus format</button>
    </div>
    <div class="sms-rich-content" contenteditable="{{ $projectInformationAvailable ? 'true' : 'false' }}" role="textbox" aria-label="Deskripsi Proyek" aria-multiline="true">{!! \App\Support\ProjectDescriptionRichText::html(old('project_description', $brief->project_description)) !!}</div>
  </div>
  <div class="sms-rich-footer"><span>Format tersimpan bersama Draf; teks lama tetap didukung.</span><output data-sms-rich-count>0 / 10.000 karakter</output></div>
  <p class="sms-rich-error" role="alert" data-sms-rich-error hidden></p>
</div>
<script src="{{ asset('js/prod02-rich-editor.js') }}" defer></script>
                        @error('project_description')<p class="field-error" id="project_description-error">{{ $message }}</p>@enderror
                    </div>
                </section>

        <div class="ui03-form-actions">
            <div>
                <a class="button button-secondary" href="{{ route('prod02.index') }}"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></span><span>Batal</span></a>
                <div class="ui03-nav-right"><button class="button button-secondary" form="ui03-step-one-form" type="submit"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></span><span>Simpan Draf</span></button>
                <button class="button ui03-step-one-primary-action" form="ui03-step-one-form" type="submit" name="next_step" value="2"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span><span>Simpan &amp; Lanjut</span></button></div>
            </div>
            <span>Tahap 1 dari 5 · Draft tersimpan sebelum membuka tahap Klien &amp; Kontak</span>
        </div>
                @if ($isStepOne) </form> @endif
            </div>

            <aside class="ui03-form-summary" aria-label="Informasi dan panduan proyek">
                <section class="card ui03-info-card">
                    <span class="ui03-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v6"/><circle cx="12" cy="7.5" r="0.7" fill="currentColor" stroke="none"/></svg></span>
                    <div>
                        <h2>Informasi</h2>
                        <p>Lengkapi informasi dasar proyek. Informasi klien dan kontak tersedia pada Tahap 2, sedangkan kebutuhan proyek pada Tahap 3.</p>
                    </div>
                </section>

                <section class="card ui03-client-card">
                    <div class="ui03-panel-heading">
                        <span class="ui03-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                        <h2>Informasi Klien</h2>
                    </div>
                    <dl>
                        <div>
                            <dt>Klien / Perusahaan</dt>
                            <dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Kontak Utama</dt>
                            <dd>{{ filled($brief->client_pic) ? $brief->client_pic : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Sumber Komunikasi</dt>
                            <dd>{{ filled($brief->communication_source) ? $brief->communication_source : 'Belum dipilih' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="card ui03-tips-card">
                    <div class="ui03-tips-heading"><span class="ui03-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6m-5 3h4M9 15.5c0-2-4-4-4-8a7 7 0 0 1 14 0c0 4-4 6-4 8v1H9z"/></svg></span>
                    <h2>Petunjuk</h2></div>
                    <ul>
                        <li>Pastikan nama proyek jelas dan mudah dipahami.</li>
                        <li>Pilih jenis proyek yang sesuai.</li>
                        <li>Cantumkan tanggal target yang realistis.</li>
                        <li>Lengkapi brief dan referensi pada tahap berikutnya.</li>
                    </ul>
                </section>
                @if ($isStepOne && $brief->exists)
                <section class="card ui03-pre-submit-card" aria-labelledby="ui03-aside-review-heading">
                    <h2 class="card-title" id="ui03-aside-review-heading">Pemeriksaan Sebelum Pengajuan</h2>
                    <p>Simpan Draf tetap tersedia tanpa pemeriksaan ini. Pemeriksaan kandidat diperlukan sebelum pengajuan resmi sesuai ketentuan.</p>
                    <form method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                        @csrf
                        <button class="ui03-review-link" type="submit">Buka Pemeriksaan <span aria-hidden="true">→</span></button>
                    </form>
                </section>
                @endif
            </aside>
        </div>
        @elseif ($isStepTwo)
        <div class="ui03-form-layout">
            <div class="ui03-form-main">
                <section class="card stack" aria-labelledby="client-information-heading">
                    <div class="section-heading">
                        <div>
                            <span class="ui03-section-kicker">TAHAP 2 · KLIEN &amp; KONTAK</span>
                            <h2 id="client-information-heading">Informasi Klien</h2>
                            <p>Masukkan nama perusahaan/klien pada data brief. Direktori klien belum tersedia, jadi tidak ada dropdown atau data contoh.</p>
                        </div>
                        <span class="status-badge status-draft">DRAFT</span>
                    </div>

                    <div class="ui03-client-master-notice" role="status">
                        <span>Master klien dan fitur Tambah Klien belum tersedia. Nama klien disimpan langsung pada Draft ini.</span>
                        <button class="button button-secondary ui03-client-master-action" type="button" disabled>Tambah Klien · belum tersedia</button>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="client_name">Klien / Perusahaan <span class="muted">(wajib saat submit resmi)</span></label>
                            <input id="client_name" name="client_name" maxlength="255"
                                   value="{{ old('client_name', $brief->client_name) }}"
                                   autocomplete="organization" placeholder="Masukkan nama klien atau perusahaan"
                                   @if($errors->has('client_name')) aria-invalid="true" aria-describedby="client_name-error" @endif>
                            @error('client_name')<p class="field-error" id="client_name-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="client_type">Jenis Klien
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <input id="client_type" name="client_type" maxlength="100"
                                   value="{{ old('client_type', $brief->client_type) }}"
                                   placeholder="{{ $clientContactFieldsAvailable ? 'Masukkan tipe klien' : 'Belum dapat disimpan' }}"
                                   @disabled(! $clientContactFieldsAvailable)>
                            @error('client_type')<p class="field-error" id="client_type-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field field-full">
                            <label for="client_address">Profil / Alamat Perusahaan
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <textarea id="client_address" name="client_address" rows="3" maxlength="5000"
                                      placeholder="{{ $clientContactFieldsAvailable ? 'Masukkan profil atau alamat perusahaan' : 'Belum dapat disimpan' }}"
                                      @if($errors->has('client_address')) aria-invalid="true" aria-describedby="client_address-error" @endif
                                      @disabled(! $clientContactFieldsAvailable)>{{ old('client_address', $brief->client_address) }}</textarea>
                            @error('client_address')<p class="field-error" id="client_address-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @unless ($clientContactFieldsAvailable)
                        <div class="notice notice-warning ui03-schema-notice" role="status">
                            Detail Client Type, Address, Position, Email, Phone, dan Notes dinonaktifkan karena migration Client &amp; Contact belum dijalankan. Nama klien, kontak utama, dan sumber komunikasi tetap dapat disimpan.
                        </div>
                    @endunless
                </section>

                <section class="card stack" aria-labelledby="client-contact-heading">
                    <div class="section-heading">
                        <div>
                            <span class="ui03-section-kicker">INFORMASI KONTAK</span>
                            <h2 id="client-contact-heading">Kontak Klien</h2>
                            <p>Nama kontak dan sumber komunikasi menggunakan field PROD-02 yang sudah tersedia.</p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="field">
                            <label for="client_pic">Nama Kontak / PIC Klien <span class="muted">(opsional)</span></label>
                            <input id="client_pic" name="client_pic" maxlength="255"
                                   value="{{ old('client_pic', $brief->client_pic) }}"
                                   autocomplete="name" placeholder="Nama kontak utama"
                                   @if($errors->has('client_pic')) aria-invalid="true" aria-describedby="client_pic-error" @endif>
                            @error('client_pic')<p class="field-error" id="client_pic-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="client_position">Jabatan
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <input id="client_position" name="client_position" maxlength="255"
                                   value="{{ old('client_position', $brief->client_position) }}"
                                   placeholder="{{ $clientContactFieldsAvailable ? 'Posisi kontak' : 'Belum dapat disimpan' }}"
                                   @disabled(! $clientContactFieldsAvailable)>
                            @error('client_position')<p class="field-error" id="client_position-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="client_email">Email
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <input id="client_email" name="client_email" type="email" maxlength="255"
                                   value="{{ old('client_email', $brief->client_email) }}"
                                   placeholder="{{ $clientContactFieldsAvailable ? 'Email kontak' : 'Belum dapat disimpan' }}"
                                   @disabled(! $clientContactFieldsAvailable)
                                   @if($errors->has('client_email')) aria-invalid="true" aria-describedby="client_email-error" @endif>
                            @error('client_email')<p class="field-error" id="client_email-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="client_phone">Telepon
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <input id="client_phone" name="client_phone" type="tel" maxlength="50"
                                   value="{{ old('client_phone', $brief->client_phone) }}"
                                   placeholder="{{ $clientContactFieldsAvailable ? 'Nomor telepon kontak' : 'Belum dapat disimpan' }}"
                                   @disabled(! $clientContactFieldsAvailable)>
                            @error('client_phone')<p class="field-error" id="client_phone-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="communication_source">Sumber Komunikasi <span class="muted">(wajib saat submit resmi)</span></label>
                            <select id="communication_source" name="communication_source"
                                    @if($errors->has('communication_source')) aria-invalid="true" aria-describedby="communication_source-error" @endif>
                                <option value="">— Pilih sumber —</option>
                                @foreach ($communicationSources as $source)
                                    <option value="{{ $source }}" @selected(old('communication_source', $brief->communication_source) === $source)>{{ $source }}</option>
                                @endforeach
                            </select>
                            @error('communication_source')<p class="field-error" id="communication_source-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field field-full">
                            <label for="client_notes">Catatan Kontak
                                <span class="muted">{{ $clientContactFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span>
                            </label>
                            <textarea id="client_notes" name="client_notes" rows="3" maxlength="5000"
                                      placeholder="{{ $clientContactFieldsAvailable ? 'Catatan kontak (opsional)' : 'Belum dapat disimpan' }}"
                                      @if($errors->has('client_notes')) aria-invalid="true" aria-describedby="client_notes-error" @endif
                                      @disabled(! $clientContactFieldsAvailable)>{{ old('client_notes', $brief->client_notes) }}</textarea>
                            @error('client_notes')<p class="field-error" id="client_notes-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

        <div class="ui03-form-actions">
            <div>
                <button class="button button-secondary" type="submit" name="next_step" value="1"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg></span><span>Simpan &amp; Kembali</span></button>
                <div class="ui03-nav-right"><button class="button" type="submit" name="next_step" value="2"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></span><span>Simpan Draf</span></button>
                <button class="button" type="submit" name="next_step" value="3"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span><span>Simpan &amp; Lanjut</span></button></div>
            </div>
            <span>Step 2 of 5 · Simpan &amp; Lanjut membuka Brief &amp; Requirement</span>
        </div>

            </div>

            <aside class="ui03-form-summary" aria-label="Panduan Client & Contact">
                <section class="card ui03-client-history-card">
                    <div class="ui03-panel-heading">
                        <span class="ui03-panel-icon" aria-hidden="true">↻</span>
                        <h2>Riwayat Klien</h2>
                    </div>
                    <p>Riwayat belum tersedia karena master klien dan relasi historis belum tersedia.</p>
                </section>
                <section class="card ui03-info-card">
                    <span class="ui03-guidance-icon" aria-hidden="true">ⓘ</span>
                    <div>
                        <h2>Informasi Kontak</h2>
                        <p>Gunakan nama dan kontak yang benar-benar diketahui. {{ $clientContactFieldsAvailable ? 'Detail kontak tambahan disimpan pada Draft.' : 'Detail kontak tambahan baru dapat disimpan setelah migration tersedia.' }}</p>
                    </div>
                </section>
                <section class="card ui03-tips-card">
                    <div class="ui03-tips-heading"><span class="ui03-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6m-5 3h4M9 15.5c0-2-4-4-4-8a7 7 0 0 1 14 0c0 4-4 6-4 8v1H9z"/></svg></span>
                    <h2>Petunjuk</h2></div>
                    <ul>
                        <li>Pastikan nama perusahaan sesuai informasi yang diterima.</li>
                        <li>Pilih sumber komunikasi yang benar-benar digunakan.</li>
                        <li>Perubahan tersimpan saat Simpan Draf atau Previous.</li>
                    </ul>
                </section>

                @if ($brief->exists)
                {{-- PROD02_STEP2_V3_REVIEW_LINK: button targets separate form below main form --}}
                <section class="card ui03-pre-submit-card ui03-step-two-review-card" aria-labelledby="step2-pre-submit-heading">
                    <h2 class="card-title" id="step2-pre-submit-heading">Pemeriksaan Sebelum Pengajuan</h2>
                    <p>Simpan Draf tetap dapat dilakukan tanpa pemeriksaan ini. Sebelum pengajuan resmi, tinjau kandidat dan catat keputusan Marketing.</p>
                    <button class="ui03-review-link" type="submit" form="ui03-step2-review-form">Buka Pemeriksaan <span aria-hidden="true">→</span></button>
                </section>
                @endif
            </aside>
        </div>

        @elseif ($isStepThree)
        <div class="ui03-form-layout">
            <div class="ui03-form-main">
                <section class="card stack ui03-step-three-card" aria-labelledby="brief-requirement-heading">
                    <div class="section-heading">
                        <!-- PROD02_UI_V4_BEGIN: header icon -->
                        <span class="ui03-step3-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5.5V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1.5M9 11h6m-6 4h6"/></svg></span>
                        <div>
                            <span class="ui03-section-kicker">TAHAP 3 · BRIEF &amp; KEBUTUHAN</span>
                            <h2 id="brief-requirement-heading">Informasi Kebutuhan</h2>
                            <p>Catat ringkasan dan kebutuhan berdasarkan informasi yang benar-benar tersedia.</p>
                        </div>
                        <span class="status-badge status-draft">DRAFT</span>
                    </div>

                    <div class="field-grid ui03-step3-fields">
                        <div class="field field-full">
                            <label for="description">Ringkasan Brief / Deskripsi Permintaan <span class="muted">(wajib saat submit resmi)</span></label>
                            <textarea id="description" name="description" rows="5" maxlength="10000"
                                      placeholder="Ringkas latar belakang, tujuan, dan permintaan klien"
                                      @if($errors->has('description')) aria-invalid="true" aria-describedby="description-error" @endif>{{ old('description', $brief->description) }}</textarea>
                            <span class="field-hint">Tersimpan pada kolom PROD-02 `description` yang sudah tersedia.</span>
                            @error('description')<p class="field-error" id="description-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field field-full">
                            <label for="key_requirements">Kebutuhan Utama <span class="muted">{{ $briefRequirementFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span></label>
                            <textarea id="key_requirements" name="key_requirements" rows="4" maxlength="10000"
                                      placeholder="{{ $briefRequirementFieldsAvailable ? 'Tuliskan kebutuhan utama' : 'Belum dapat disimpan' }}"
                                      @if($errors->has('key_requirements')) aria-invalid="true" aria-describedby="key_requirements-error" @endif
                                      @disabled(! $briefRequirementFieldsAvailable)>{{ old('key_requirements', $brief->key_requirements) }}</textarea>
                            @error('key_requirements')<p class="field-error" id="key_requirements-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field field-full">
                            <label for="target_audience">Target Audiens <span class="muted">{{ $briefRequirementFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span></label>
                            <textarea id="target_audience" name="target_audience" rows="3" maxlength="5000"
                                      placeholder="{{ $briefRequirementFieldsAvailable ? 'Jelaskan target audience' : 'Belum dapat disimpan' }}"
                                      @if($errors->has('target_audience')) aria-invalid="true" aria-describedby="target_audience-error" @endif
                                      @disabled(! $briefRequirementFieldsAvailable)>{{ old('target_audience', $brief->target_audience) }}</textarea>
                            @error('target_audience')<p class="field-error" id="target_audience-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field field-full">
                            <label for="reference_inspiration">Referensi / Inspirasi <span class="muted">{{ $briefRequirementFieldsAvailable ? '(opsional)' : '(menunggu migration)' }}</span></label>
                            <textarea id="reference_inspiration" name="reference_inspiration" rows="3" maxlength="5000"
                                      placeholder="{{ $briefRequirementFieldsAvailable ? 'Catat referensi atau inspirasi' : 'Belum dapat disimpan' }}"
                                      @if($errors->has('reference_inspiration')) aria-invalid="true" aria-describedby="reference_inspiration-error" @endif
                                      @disabled(! $briefRequirementFieldsAvailable)>{{ old('reference_inspiration', $brief->reference_inspiration) }}</textarea>
                            @error('reference_inspiration')<p class="field-error" id="reference_inspiration-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    @unless ($briefRequirementFieldsAvailable)
                        <div class="notice notice-warning ui03-schema-notice" role="status">
                            Key Requirements, Target Audience, dan Reference / Inspiration dinonaktifkan sampai migration Brief &amp; Requirement disetujui dan dijalankan. Jenis Proyek berasal dari Tahap 1; Brief Summary tersimpan pada field PROD-02 yang sudah ada.
                        </div>
                    @endunless
                </section>

                                @include('prod02.partials.reference-images')

        <div class="ui03-form-actions">
            <div>
                <button class="button button-secondary" type="submit" name="next_step" value="2"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg></span><span>Simpan &amp; Kembali</span></button>
                <div class="ui03-nav-right"><button class="button" type="submit" name="next_step" value="3"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></span><span>Simpan Draf</span></button>
                <button class="button" type="submit" name="next_step" value="4"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span><span>Simpan &amp; Lanjut</span></button></div>
            </div>
            <span></span>
        </div>

            </div>

            <aside class="ui03-form-summary" aria-label="Ringkasan brief dan panduan">
                <section class="card ui03-client-card">
                    <div class="ui03-panel-heading">
                        <span class="ui03-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v5h4M9 12h6m-6 4h6"/></svg></span>
                        <h2>Ringkasan Proyek</h2>
                    </div>
                    <dl>
                        <div>
                            <dt>Proyek</dt>
                            <dd>{{ filled($brief->project_name) ? $brief->project_name : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Klien</dt>
                            <dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Kontak Utama</dt>
                            <dd>{{ filled($brief->client_pic) ? $brief->client_pic : 'Belum diisi' }}</dd>
                        </div>

                        <div>
                            <dt>Jenis Proyek</dt>
                            <dd>{{ filled($brief->project_type) ? $brief->project_type : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Tanggal Target</dt>
                            <dd>{{ $brief->target_date?->format('d/m/Y') ?? 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Estimasi Nilai</dt>
                            <dd>{{ filled($brief->estimated_value) ? 'Rp '.number_format((float) $brief->estimated_value, 2, ',', '.') : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Lokasi</dt>
                            <dd>{{ filled($brief->work_location) ? $brief->work_location : 'Belum diisi' }}</dd>
                        </div>
                    </dl>
                </section>
                <!-- PROD02_UI_V4_END: Informasi Penyimpanan Tahap 3 dihapus dari tampilan -->

                <section class="card ui03-tips-card">
                    <div class="ui03-tips-heading"><span class="ui03-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6m-5 3h4M9 15.5c0-2-4-4-4-8a7 7 0 0 1 14 0c0 4-4 6-4 8v1H9z"/></svg></span>
                    <h2>Petunjuk</h2></div>
                    <ul>
                        <li>Gunakan ringkasan yang sesuai brief klien.</li>
                        <li>Bedakan kebutuhan utama dengan target audiens.</li>
                        <li>Tambahkan referensi yang benar-benar diberikan.</li>
                    </ul>
                </section>

                @if ($brief->exists)
                <section class="card ui03-pre-submit-card ui03-step-three-review-card">
                    <h2 class="card-title">Pemeriksaan Sebelum Pengajuan</h2>
                    <p>Simpan Draf tetap tersedia tanpa pemeriksaan.
                    Sebelum pengajuan resmi, tinjau kandidat dan catat
                    keputusan Marketing.</p>
                    <button class="ui03-review-link" type="submit"
                            form="ui03-step3-review-form">
                        Buka Pemeriksaan <span aria-hidden="true">→</span>
                    </button>
                </section>
                @endif
            </aside>
        </div>

        @unless ($isStepOne) </form> @endunless
        @endif
        @endif

        @if ($isStepFour)
        <div class="ui03-form-layout">
            <div class="ui03-form-main">
                <section class="card stack ui03-attachments-card" aria-labelledby="attachments-heading">
                    <div class="section-heading ui03-step4-heading">
                        <span class="ui03-step4-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h6l5 5v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a2 2 0 0 1 2-2h1z"/><path d="M14 3v6h5M8 13h8M8 17h6"/></svg></span>
                        <div class="ui03-step4-heading-copy">
                            <h2 id="attachments-heading">Lampiran Dokumen</h2>
                        </div>
                        <span class="status-badge status-draft">DRAFT</span>
                    </div>

                    @if ($attachmentsAvailable && $attachmentMaxUploadKilobytes > 0)
                        <form method="POST" action="{{ route('prod02.attachments.store', $brief) }}" enctype="multipart/form-data" class="ui03-attachment-upload" data-business-max-bytes="209715200" data-runtime-max-bytes="{{ $attachmentMaxUploadKilobytes * 1024 }}">
                            @csrf
                            <div class="ui03-attachment-dropzone">
                                <span class="ui03-attachment-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m-4 4 4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></span>
                                <strong id="ui03-step4-file-label">Klik untuk memilih dokumen</strong>
                                <p>PDF, JPG/JPEG, PNG, WebP, DOCX, XLSX, PPTX. Maksimum efektif saat ini {{ number_format($attachmentMaxUploadKilobytes / 1024, 1) }} MiB per file.</p>
                                <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.pptx" required>
                                <p id="attachment-client-error" class="field-error" role="alert" aria-live="polite" hidden></p>
                                @error('attachment')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                            </div>

                            <div class="field-grid ui03-attachment-fields">
                                <div class="field field-full">
                                    <label for="attachment_notes">Catatan Lampiran <span class="muted">(opsional)</span></label>
                                    <textarea id="attachment_notes" name="notes" placeholder="Keterangan dokumen (opsional)" rows="4" maxlength="5000">{{ old('notes') }}</textarea>
                                    @error('notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <button class="button" type="submit"><span class="ui03-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m-4 4 4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></span><span>Unggah Lampiran</span></button>
                        </form>
                        <script>document.addEventListener('DOMContentLoaded', function () { const input=document.getElementById('attachment'); const label=document.getElementById('ui03-step4-file-label'); if(input && label) input.addEventListener('change', function(){label.textContent = input.files && input.files[0] ? input.files[0].name : 'Klik untuk memilih dokumen';}); });</script>
                    @else
                        <div class="ui03-attachment-dropzone" aria-disabled="true" role="status">
                            <span class="ui03-attachment-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4m-4 4 4-4 4 4"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></span>
                            <strong>Upload belum aktif</strong>
                            <p>
                                @if (!$attachmentsAvailable)
                                    Migration category/notes dan soft-delete belum tersedia pada schema aktif. Upload dan daftar lampiran tidak diaktifkan.
                                @else
                                    Konfigurasi runtime PHP saat ini tidak mengizinkan upload file.
                                @endif
                            </p>
                            <input type="file" disabled aria-label="Upload lampiran belum tersedia">
                        </div>
                    @endif
                <div class="ui03-step4-list-section ui03-attachment-list-card" aria-labelledby="attachment-list-heading">
                    <div class="section-heading">
                        <div>
                            <h2 id="attachment-list-heading"><span class="ui03-step4-list-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h9a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3z"/><path d="M9 9h6M9 13h6M9 17h4"/></svg></span>Daftar Lampiran <span class="ui03-step4-file-count">({{ $attachmentsAvailable ? $attachments->count() : 0 }})</span></h2>
                        </div>
                    </div>
                    @if ($attachmentsAvailable)
                        @if ($attachments->isNotEmpty())
                            <div class="ui03-attachment-table-wrap" role="region" aria-label="Tabel daftar lampiran" tabindex="0">
                                <table class="ui03-attachment-table">
                                    <thead><tr><th scope="col">No.</th><th scope="col">Nama File</th><th scope="col">Tipe</th><th scope="col">Ukuran</th><th scope="col">Diunggah Oleh</th><th scope="col">Tanggal</th><th scope="col">Aksi</th></tr></thead>
                                    <tbody>
                                        @foreach ($attachments as $attachment)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="ui03-attachment-file-cell">
                                                    <span class="ui03-step4-file-identity">
                                                        <span class="ui03-step4-file-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v12H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v6h5M8 16h8"/></svg></span>
                                                        <span class="ui03-attachment-file-name" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</span>
                                                    </span>
                                                    @if ($attachment->category)<small>Kategori: {{ $attachment->category }}</small>@endif
                                                </td>
                                                <td>{{ strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) ?: '—' }}</td>
                                                <td>{{ $attachment->size_bytes !== null ? number_format($attachment->size_bytes / 1048576, 2, ',', '.') . ' MB' : '—' }}</td>
                                                <td>{{ $attachment->uploader?->name ?? 'Tidak diketahui' }}</td>
                                                <td>{{ $attachment->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td><div class="ui03-attachment-table-actions">
                                                    <button type="button" class="button button-secondary ui03-attachment-notes-trigger" data-ui03-attachment-note data-file-name="{{ $attachment->original_name }}" data-note="{{ $attachment->notes ?? '' }}" aria-label="Lihat catatan {{ $attachment->original_name }}" title="Lihat Catatan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 10h8M8 14h5"/><path d="M20 11.5c0 4.7-3.6 8.5-8 8.5a9 9 0 0 1-3.7-.8L4 20l1-4.2A8.8 8.8 0 0 1 4 11.5C4 6.8 7.6 3 12 3s8 3.8 8 8.5Z"/></svg><span class="ui03-visually-hidden">Lihat Catatan</span></button>
                                                    <a class="button button-secondary" href="{{ route('prod02.attachments.download', ['brief' => $brief, 'attachment' => $attachment]) }}" aria-label="Unduh {{ $attachment->original_name }}" title="Unduh"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M4 17v3h16v-3"/></svg><span class="ui03-visually-hidden">Unduh</span></a>
                                                    <form method="POST" action="{{ route('prod02.attachments.destroy', ['brief' => $brief, 'attachment' => $attachment]) }}" onsubmit="return confirm('Hapus lampiran ini dari daftar aktif? File privat akan dipertahankan untuk retensi.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="button button-secondary" type="submit" aria-label="Hapus {{ $attachment->original_name }}" title="Hapus"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 3h4l1 4H9l1-4zM6 7l1 14h10l1-14M10 11v6m4-6v6"/></svg><span class="ui03-visually-hidden">Hapus</span></button>
                                                    </form>
                                                </div></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <dialog class="ui03-attachment-notes-dialog" id="ui03-attachment-notes-dialog" aria-labelledby="ui03-attachment-notes-title">
                                <div class="ui03-attachment-notes-header">
                                    <h3 id="ui03-attachment-notes-title">Catatan Lampiran</h3>
                                    <button type="button" class="ui03-attachment-notes-close" data-ui03-note-close aria-label="Tutup catatan">&times;</button>
                                </div>
                                <p class="ui03-attachment-notes-filename" id="ui03-attachment-notes-filename"></p>
                                <p class="ui03-attachment-notes-content" id="ui03-attachment-notes-content"></p>
                                <div class="ui03-attachment-notes-footer"><button type="button" class="button button-secondary" data-ui03-note-close>Tutup</button></div>
                            </dialog>
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const dialog = document.getElementById('ui03-attachment-notes-dialog');
                                    if (!dialog || typeof dialog.showModal !== 'function') return;
                                    const fileName = document.getElementById('ui03-attachment-notes-filename');
                                    const noteContent = document.getElementById('ui03-attachment-notes-content');
                                    document.querySelectorAll('[data-ui03-attachment-note]').forEach(function (button) {
                                        button.addEventListener('click', function () {
                                            fileName.textContent = button.dataset.fileName || '';
                                            noteContent.textContent = (button.dataset.note || '').trim() || 'Tidak ada catatan untuk lampiran ini.';
                                            dialog.showModal();
                                        });
                                    });
                                    dialog.querySelectorAll('[data-ui03-note-close]').forEach(function (button) {
                                        button.addEventListener('click', function () { dialog.close(); });
                                    });
                                    dialog.addEventListener('click', function (event) {
                                        if (event.target === dialog) dialog.close();
                                    });
                                });
                            </script>
                        @else
                            <div class="ui03-attachment-empty" role="status"><strong>Belum ada lampiran aktif</strong><span>Pilih dokumen di atas untuk mulai mengunggah.</span></div>
                        @endif
                    @else
                        <div class="ui03-attachment-empty" role="status"><strong>Daftar lampiran belum tersedia</strong><span>Migration metadata dan soft-delete harus tersedia sebelum daftar aman dimuat.</span></div>
                    @endif
                </div>
                </section>
        <div class="ui03-form-actions">
            <div>
                @if ($editDraftUrl !== null)
                    <a class="button button-secondary ui03-step4-nav-leave" href="{{ $editStepThreeUrl }}"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg></span><span>Simpan &amp; Kembali</span></a>
                    <div class="ui03-step4-next-actions">
                        <a class="button ui03-step4-nav-leave" href="{{ route('prod02.index') }}"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></span><span>Simpan Draf</span></a>
                        <a class="button ui03-step4-nav-leave" href="{{ $editStepFiveUrl }}"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span><span>Simpan &amp; Lanjut</span></a>
                    </div>
                @else
                    <a class="button button-secondary" href="{{ route('prod02.index') }}"><span class="ui03-nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg></span><span>Kembali ke daftar permintaan</span></a>
                @endif
            </div>
            <span>Dokumen tersimpan setelah berhasil diunggah.</span>
        </div>
            </div>

            <aside class="ui03-form-summary" aria-label="Panduan lampiran">
                <section class="card ui03-client-card">
                    <div class="ui03-panel-heading">
                        <span class="ui03-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v5h4M9 12h6m-6 4h6"/></svg></span>
                        <h2>Ringkasan Proyek</h2>
                    </div>
                    <dl>
                        <div>
                            <dt>Proyek</dt>
                            <dd>{{ filled($brief->project_name) ? $brief->project_name : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Klien</dt>
                            <dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Kontak Utama</dt>
                            <dd>{{ filled($brief->client_pic) ? $brief->client_pic : 'Belum diisi' }}</dd>
                        </div>

                        <div>
                            <dt>Jenis Proyek</dt>
                            <dd>{{ filled($brief->project_type) ? $brief->project_type : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Tanggal Target</dt>
                            <dd>{{ $brief->target_date?->format('d/m/Y') ?? 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Estimasi Nilai</dt>
                            <dd>{{ filled($brief->estimated_value) ? 'Rp '.number_format((float) $brief->estimated_value, 2, ',', '.') : 'Belum diisi' }}</dd>
                        </div>
                        <div>
                            <dt>Lokasi</dt>
                            <dd>{{ filled($brief->work_location) ? $brief->work_location : 'Belum diisi' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="card ui03-tips-card">
                    <div class="ui03-tips-heading"><span class="ui03-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18h6m-5 3h4M9 15.5c0-2-4-4-4-8a7 7 0 0 1 14 0c0 4-4 6-4 8v1H9z"/></svg></span><h2>Petunjuk</h2></div>
                    <ul>
                        <li>Lampirkan dokumen yang relevan dengan permintaan klien.</li>
                        <li>Gambar inspirasi diunggah melalui Tahap 3.</li>
                        <li>Pastikan dokumen sudah benar sebelum melanjutkan.</li>
                    </ul>
                </section>
                @if ($brief->exists)
                <section class="card ui03-step4-review-card">
                    <h2>Pemeriksaan Sebelum Pengajuan</h2>
                    <p>Tinjau kelengkapan sebelum pengajuan resmi melalui pemeriksaan Marketing.</p>
                    <form method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                        @csrf
                        <button class="ui03-review-link" type="submit">Buka Pemeriksaan <span aria-hidden="true">→</span></button>
                    </form>
                </section>
                @endif
            </aside>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var fileInput = document.getElementById('attachment');
            document.querySelectorAll('.ui03-step-four .ui03-step4-nav-leave').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    if (fileInput && fileInput.files && fileInput.files.length &&
                        !window.confirm('Dokumen yang dipilih belum diunggah. Pilih Batal untuk mengunggah terlebih dahulu, atau OK untuk berpindah tanpa menyimpan file tersebut.')) {
                        event.preventDefault();
                    }
                });
            });
        });
        </script>
        @endif

        @if ($isStepFive && $brief->exists)
            <section class="ui03-review-layout ui03-s5-layout" aria-label="Ringkasan dan kesiapan Draft">
                <div class="stack ui03-s5-main">
                    <section class="card ui03-s5-review" aria-labelledby="review-summary-heading">
                        <div class="ui03-s5-title"><span class="ui03-s5-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 9h6M9 13h6M9 17h4"/></svg></span><div><h2 id="review-summary-heading">Tinjau &amp; Ajukan · Draft #{{ $brief->id }}</h2><p>Periksa informasi tersimpan sebelum pengajuan resmi.</p></div><span class="status-badge status-draft">DRAFT</span></div>
<section class="ui03-s5-group" aria-label="Informasi Proyek">
<div class="ui03-s5-group-head"><span class="ui03-s5-number">1</span><h3>Informasi Proyek</h3><a href="{{ route('prod02.edit', ['brief' => $brief, 'step' => 1]) }}" aria-label="Edit Informasi Proyek" class="ui03-s5-edit-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg>Edit</a></div>
<dl class="ui03-s5-fields">
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Nama Proyek</dt>
                                    <dd>{{ filled($brief->project_name) ? $brief->project_name : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Jenis Proyek</dt>
                                    <dd>{{ filled($brief->project_type) ? $brief->project_type : 'Belum dipilih' }}</dd>
                                </div>
@endif
<div class="ui03-review-detail">
                                <dt>Tanggal Target</dt>
                                <dd>{{ $brief->target_date?->format('d M Y') ?? 'Belum diisi' }}</dd>
                            </div>
<div class="ui03-review-detail">
                                <dt>Lokasi Proyek</dt>
                                <dd>{{ filled($brief->work_location) ? $brief->work_location : 'Belum diisi' }}</dd>
                            </div>
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Brand</dt>
                                    <dd>{{ filled($brief->brand) ? $brief->brand : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Estimasi Nilai</dt>
                                    <dd>{{ filled($brief->estimated_value) ? 'Rp '.number_format((float) $brief->estimated_value, 2, ',', '.') : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Priority</dt>
                                    <dd>{{ filled($brief->priority) ? $brief->priority : 'Belum dipilih' }}</dd>
                                </div>
@endif
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Tujuan Proyek</dt>
                                    <dd>{{ filled($brief->project_objectives) ? $brief->project_objectives : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($projectInformationAvailable)
<div class="ui03-review-detail">
                                    <dt>Deskripsi Proyek</dt>
                                    <dd>{!! filled($brief->project_description) ? \App\Support\ProjectDescriptionRichText::html($brief->project_description) : 'Belum diisi' !!}</dd>
                                </div>
@endif
</dl>
</section><section class="ui03-s5-group" aria-label="Klien & Kontak">
<div class="ui03-s5-group-head"><span class="ui03-s5-number">2</span><h3>Klien & Kontak</h3><a href="{{ route('prod02.edit', ['brief' => $brief, 'step' => 2]) }}" aria-label="Edit Klien & Kontak" class="ui03-s5-edit-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg>Edit</a></div>
<dl class="ui03-s5-fields">
<div class="ui03-review-detail">
                                <dt>Klien / Perusahaan</dt>
                                <dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd>
                            </div>
<div class="ui03-review-detail">
                                <dt>Sumber Komunikasi</dt>
                                <dd>{{ filled($brief->communication_source) ? $brief->communication_source : 'Belum dipilih' }}</dd>
                            </div>
<div class="ui03-review-detail">
                                <dt>Kontak Utama</dt>
                                <dd>{{ filled($brief->client_pic) ? $brief->client_pic : 'Belum diisi' }}</dd>
                            </div>
@if ($clientContactFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Jenis Klien</dt>
                                    <dd>{{ filled($brief->client_type) ? $brief->client_type : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($clientContactFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Alamat</dt>
                                    <dd>{{ filled($brief->client_address) ? $brief->client_address : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($clientContactFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Jabatan Kontak</dt>
                                    <dd>{{ filled($brief->client_position) ? $brief->client_position : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($clientContactFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Email / Phone</dt>
                                    <dd>{{ filled($brief->client_email) ? $brief->client_email : 'Belum diisi' }} / {{ filled($brief->client_phone) ? $brief->client_phone : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($clientContactFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Catatan Kontak</dt>
                                    <dd>{{ filled($brief->client_notes) ? $brief->client_notes : 'Belum diisi' }}</dd>
                                </div>
@endif
</dl>
</section><section class="ui03-s5-group" aria-label="Brief & Kebutuhan">
<div class="ui03-s5-group-head"><span class="ui03-s5-number">3</span><h3>Brief & Kebutuhan</h3><a href="{{ route('prod02.edit', ['brief' => $brief, 'step' => 3]) }}" aria-label="Edit Brief & Kebutuhan" class="ui03-s5-edit-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg>Edit</a></div>
<dl class="ui03-s5-fields">
<div class="ui03-review-detail">
                                <dt>Ringkasan Brief</dt>
                                <dd>{{ filled($brief->description) ? $brief->description : 'Belum diisi' }}</dd>
                            </div>
@if ($briefRequirementFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Kebutuhan Utama</dt>
                                    <dd>{{ filled($brief->key_requirements) ? $brief->key_requirements : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($briefRequirementFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Target Audiens</dt>
                                    <dd>{{ filled($brief->target_audience) ? $brief->target_audience : 'Belum diisi' }}</dd>
                                </div>
@endif
@if ($briefRequirementFieldsAvailable)
<div class="ui03-review-detail">
                                    <dt>Referensi / Inspirasi</dt>
                                    <dd>{{ filled($brief->reference_inspiration) ? $brief->reference_inspiration : 'Belum diisi' }}</dd>
                                </div>
@endif
</dl>
</section><section class="ui03-s5-group" aria-labelledby="review-attachments-heading">
<div class="ui03-s5-group-head"><span class="ui03-s5-number">4</span><h3 id="review-attachments-heading">Lampiran</h3><a href="{{ $editStepFourUrl }}" aria-label="Edit Lampiran" class="ui03-s5-edit-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg>Edit</a></div>
@if (!$attachmentsAvailable)
<p class="ui03-s5-empty">Daftar lampiran belum tersedia.</p>
@elseif ($attachments->isEmpty())
<p class="ui03-s5-empty">Belum ada lampiran aktif.</p>
@else
<div class="ui03-s5-filegrid">
@foreach ($attachments as $attachment)
<div class="ui03-s5-file"><span class="ui03-s5-file-icon" aria-hidden="true">▤</span><span class="ui03-s5-file-name">{{ $attachment->original_name }}</span><small>{{ $attachment->size_bytes !== null ? number_format($attachment->size_bytes / 1048576, 2).' MB' : 'Ukuran tidak diketahui' }}</small></div>
@endforeach
</div>
@endif
</section>
                    </section>
<div class="ui03-s5-footer ui03-form-actions">
<a class="button button-secondary" href="{{ $editStepFourUrl }}"><span class="ui03-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7 7-7-7 7-7"/></svg></span><span>Kembali ke Lampiran</span></a>
<div class="ui03-s5-footer-right"><a class="button button-secondary" href="{{ route('prod02.index') }}"><span class="ui03-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg></span><span>Simpan Draf</span></a>

</div></div>
                </div>
                <aside class="stack ui03-s5-sidebar" aria-label="Status kesiapan submit">
<section class="card ui03-s5-aside-summary" aria-labelledby="ui03-s5-project-summary">
<div class="ui03-s5-aside-title"><span class="ui03-s5-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M15 3v5h5M9 12h6M9 16h6"/></svg></span><h2 id="ui03-s5-project-summary">Ringkasan Proyek</h2></div>
<dl>
<div><dt>Nama Proyek</dt><dd>{{ filled($brief->project_name) ? $brief->project_name : 'Belum diisi' }}</dd></div>
<div><dt>Klien</dt><dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd></div>
<div><dt>Tanggal Target</dt><dd>{{ $brief->target_date?->format('d/m/Y') ?? 'Belum diisi' }}</dd></div>
<div><dt>Lokasi</dt><dd>{{ filled($brief->work_location) ? $brief->work_location : 'Belum diisi' }}</dd></div>
</dl></section>
                    <section class="card stack" aria-labelledby="review-readiness-heading">
                        <div class="section-heading">
                            <div>
                                <h2 id="review-readiness-heading">Kesiapan Pengajuan</h2>
                                <p>Persyaratan wajib mengikuti validasi server saat pengajuan.</p>
                            </div>
                        </div>
                        <ul class="ui03-readiness-list">
                            @foreach ($submissionRequirements['fields'] as $requirement)
                                <li>
                                    <span>{{ $requirement['label'] }}</span>
                                    <strong>{{ $requirement['ready'] ? 'Lengkap' : 'Perlu dilengkapi' }}</strong>
                                </li>
                            @endforeach
                        </ul>
                        @if (!$submissionRequirements['ready'])
                            <p class="notice notice-warning" role="status">Lengkapi persyaratan wajib sebelum submit. Periksa kembali data pada Tahap 2 dan Tahap 3.</p>
                        @endif
                        @if ($canSubmit)
                            <p class="notice notice-success" role="status">Pemeriksaan duplikasi masih berlaku dan keputusan Marketing adalah Permintaan Baru.</p>
                        @else
                            <p class="notice notice-warning" role="status">{{ $submitMessage ?: 'Pemeriksaan duplikasi dan keputusan Marketing diperlukan sebelum submit.' }}</p>
                        @endif
                    </section>

                    <section class="card stack" aria-labelledby="review-duplicate-heading">
                        <div class="section-heading">
                            <div>
                                <h2 id="review-duplicate-heading">Pemeriksaan Duplikasi</h2>
                                <p>Pemeriksaan menghasilkan kandidat; Marketing mencatat keputusan pada halaman berikutnya. Draft tidak diubah dan tidak disubmit.</p>
                            </div>
                        </div>
                        @if ($duplicateCheckEvent)
                            <p class="small muted">Pemeriksaan terakhir tercatat {{ $duplicateCheckEvent->occurred_at?->format('d M Y H:i') ?? 'tanpa waktu' }}.</p>
                            @if (count($duplicateCandidates) === 0)
                                <p role="status">Pemeriksaan terakhir tidak menemukan kandidat.</p>
                            @else
                                <ul class="ui03-review-attachments">
                                    @foreach ($duplicateCandidates as $candidate)
                                        <li>{{ $candidate['request_number'] ?: 'Draft #'.$candidate['id'] }} — {{ $candidate['client_name'] ?: 'Klien belum diisi' }} ({{ $candidate['score'] }}%)</li>
                                    @endforeach
                                </ul>
                            @endif
                        @else
                            <p role="status">Belum ada pemeriksaan duplikasi yang tercatat.</p>
                        @endif
                        <form method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                            @csrf
                            <button class="button button-secondary" type="submit">Periksa kandidat</button>
                        </form>
                    </section>
                    @if ($canSubmit && $submissionRequirements['ready'])
                    <section class="card stack ui03-s5-submit-card" aria-labelledby="ui03-s5-submit-heading">
                        <div class="ui03-s5-submit-head"><h2 id="ui03-s5-submit-heading">Submit Permintaan Baru</h2><span class="status-badge status-review">Siap ditinjau</span></div>
                        <p>Pastikan informasi dan keputusan pemeriksaan sudah sesuai sebelum pencatatan resmi.</p>
                        <form id="ui03-step5-submit-form" method="POST" action="{{ route('prod02.submit', $brief) }}">
                            @csrf
                            <span class="ui03-visually-hidden">Form pengajuan resmi menggunakan konfirmasi Tahap 5.</span>
                        </form>
                        <button class="button ui03-s5-submit-action" type="button" id="ui03-step5-open-confirm" aria-controls="ui03-step5-confirm-dialog"><span class="ui03-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span><span>Ajukan Permintaan</span></button>
                    </section>
                    @endif
<section class="card ui03-s5-next" aria-labelledby="ui03-s5-next-heading"><div class="ui03-s5-aside-title"><span aria-hidden="true">◉</span><h2 id="ui03-s5-next-heading">Langkah Berikutnya</h2></div><p>Setelah pengajuan berhasil, permintaan dicatat secara resmi sesuai proses PROD-02. Tindak lanjut berikutnya mengikuti workflow dan kewenangan yang berlaku.</p></section>
                </aside>
            </section>
        @endif

        @if ($isStepFour && $attachmentsAvailable && $attachmentMaxUploadKilobytes > 0)
        <script>
        (() => {
            const form = document.querySelector('.ui03-attachment-upload');
            const input = document.getElementById('attachment');
            const message = document.getElementById('attachment-client-error');

            if (!form || !input || !message) {
                return;
            }

            const businessMaximumBytes = Number(form.dataset.businessMaxBytes);
            const runtimeMaximumBytes = Number(form.dataset.runtimeMaxBytes);
            const effectiveMaximumBytes = Math.min(businessMaximumBytes, runtimeMaximumBytes);
            const formatMiB = (bytes) => `${(bytes / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 2 })} MiB`;

            const validateSelectedFile = () => {
                const file = input.files?.[0];

                if (!file || file.size <= effectiveMaximumBytes) {
                    message.hidden = true;
                    message.textContent = '';
                    input.removeAttribute('aria-invalid');
                    return true;
                }

                message.textContent = `Ukuran file ${formatMiB(file.size)} melebihi batas efektif saat ini ${formatMiB(effectiveMaximumBytes)} per file (batas bisnis ${formatMiB(businessMaximumBytes)}). File tidak dikirim; pilih file yang lebih kecil.`;
                message.hidden = false;
                input.setAttribute('aria-invalid', 'true');
                return false;
            };

            input.addEventListener('change', validateSelectedFile);
            form.addEventListener('submit', (event) => {
                if (!validateSelectedFile()) {
                    event.preventDefault();
                    input.focus();
                }
            });
        })();
        </script>
        @endif

        <script>
        (() => {
            const input = document.getElementById('estimated_value');
            const preview = document.getElementById('estimated_value-preview');

            if (!input || !preview) {
                return;
            }

            const updateCurrencyPreview = () => {
                const value = input.value.trim();
                const match = /^(\d{1,16})(?:\.(\d{0,2}))?$/.exec(value);

                if (!value) {
                    preview.textContent = 'Belum diisi';
                    return;
                }

                if (!match) {
                    preview.textContent = 'Format IDR tidak valid';
                    return;
                }

                const groupedInteger = match[1].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                const decimalPart = match[2] ? `,${match[2]}` : '';
                preview.textContent = `Rp ${groupedInteger}${decimalPart}`;
            };

            input.addEventListener('input', updateCurrencyPreview);
            updateCurrencyPreview();

            document.querySelectorAll('[data-count-for]').forEach((textarea) => {
                const count = document.querySelector(`[data-count-for="${textarea.id}"]`);
                const updateCount = () => {
                    count.textContent = `${textarea.value.length.toLocaleString('en-US')}/10,000`;
                };

                textarea.addEventListener('input', updateCount);
                updateCount();
            });
        })();
        </script>
        @endunless

        @if ($isStepFive && $canSubmit && $submissionRequirements['ready'])
        <dialog id="ui03-step5-confirm-dialog" class="ui03-step5-confirm-dialog" aria-labelledby="ui03-step5-dialog-title">
            <div class="ui03-step5-confirm-body">
                <div class="ui03-step5-confirm-heading"><span aria-hidden="true">?</span><div><h2 id="ui03-step5-dialog-title">Ajukan Permintaan Klien?</h2><p>Pastikan seluruh informasi benar sebelum pengajuan resmi.</p></div></div>
                <dl class="ui03-step5-confirm-summary">
                    <div><dt>Proyek</dt><dd>{{ filled($brief->project_name) ? $brief->project_name : 'Belum diisi' }}</dd></div>
                    <div><dt>Klien</dt><dd>{{ filled($brief->client_name) ? $brief->client_name : 'Belum diisi' }}</dd></div>
                    <div><dt>Tanggal Target</dt><dd>{{ $brief->target_date?->format('d/m/Y') ?? 'Belum diisi' }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ filled($brief->work_location) ? $brief->work_location : 'Belum diisi' }}</dd></div>
                </dl>
                <p>Setelah dikonfirmasi, sistem akan menjalankan validasi server dan proses pencatatan sesuai aturan PROD-02.</p>
                <div class="ui03-step5-confirm-actions"><button class="button button-secondary" type="button" id="ui03-step5-cancel-confirm">Batal</button><button class="button" type="button" id="ui03-step5-confirm-submit">Ya, Ajukan</button></div>
            </div>
        </dialog>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dialog = document.getElementById('ui03-step5-confirm-dialog');
            const form = document.getElementById('ui03-step5-submit-form');
            const open = document.getElementById('ui03-step5-open-confirm');
            const cancel = document.getElementById('ui03-step5-cancel-confirm');
            const confirm = document.getElementById('ui03-step5-confirm-submit');
            if (!dialog || !form || !open || !cancel || !confirm) return;
            open.addEventListener('click', function () { dialog.showModal(); });
            cancel.addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
            confirm.addEventListener('click', function () { confirm.disabled = true; form.requestSubmit(); });
        });
        </script>
        @endif

        @if ($brief->primaryBrief)
            <section class="notice">
                <strong>Permintaan utama:</strong>
                {{ $brief->primaryBrief->request_number ?: 'Brief #'.$brief->primaryBrief->id }}
                @if ($brief->duplicate_reason)
                    <p style="margin-bottom:0">Alasan duplikat: {{ $brief->duplicate_reason }}</p>
                @endif
            </section>
        @endif

        @if ($latestDuplicateDecision)
            <section class="card card-subtle" aria-labelledby="decision-heading">
                <h2 class="card-title" id="decision-heading">Keputusan pemeriksaan terakhir</h2>
                <p style="margin:10px 0 0">
                    @switch($latestDuplicateDecision->after_data['decision'] ?? null)
                        @case('new')<span class="status-badge status-recorded">Permintaan Baru</span>@break
                        @case('continuation')<span class="status-badge status-review">Kelanjutan</span>@break
                        @case('duplicate')<span class="status-badge status-duplicate">Duplikat</span>@break
                    @endswitch
                </p>
                @if ($latestDuplicateDecision->reason)
                    <p class="small muted" style="margin-bottom:0">Alasan: {{ $latestDuplicateDecision->reason }}</p>
                @endif
            </section>
        @endif

        @if ($brief->exists && ($isDuplicateReview || (request()->routeIs('prod02.submit') && $duplicateCheckEvent)))
            <section class="card stack" aria-labelledby="duplicate-check-heading">
                <div class="section-heading">
                    <div>
                        <span class="ui03-section-kicker">REVIEW SEBELUM SUBMIT · TERPISAH DARI SAVE AS DRAFT</span>
                        <h2 id="duplicate-check-heading">{{ $isDuplicateReview ? 'Tinjau Kandidat dan Keputusan' : 'Pemeriksaan kemungkinan duplikasi' }}</h2>
                        <p>
                            Pemeriksaan dan keputusan ini diperlukan untuk proses submit resmi, tetapi tidak diperlukan untuk menyimpan atau memperbarui Draft.
                            Kandidat adalah saran; keputusan tetap ditetapkan Marketing.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                    @csrf
                    <button class="button button-secondary" type="submit">Periksa kandidat</button>
                </form>

                @if ($duplicateCheckEvent)
                    <div>
                        <h3 class="card-title">Kandidat RECORDED</h3>
                        @if (count($duplicateCandidates) === 0)
                            <div class="notice" role="status" style="margin-top:12px">Tidak ditemukan kandidat yang memenuhi aturan pencocokan.</div>
                        @else
                            <ul class="candidate-list">
                                @foreach ($duplicateCandidates as $candidate)
                                    <li class="candidate-item">
                                        <strong>{{ $candidate['request_number'] ?: 'Draft #'.$candidate['id'] }}</strong>
                                        <span class="muted">— {{ $candidate['client_name'] ?: 'Klien belum diisi' }}</span>
                                        <span class="status-badge status-review" style="margin-left:6px">Kecocokan {{ $candidate['score'] }}%</span>
                                        <ul class="candidate-reasons">
                                            @foreach ($candidate['reasons'] as $reason)
                                                <li>{{ $reason['label'] }}: {{ $reason['similarity'] }}%</li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <form method="POST" action="{{ route('prod02.duplicate-decision', $brief) }}" class="stack">
                            @csrf
                            <input type="hidden" name="duplicate_check_event_id" value="{{ $duplicateCheckEvent->id }}">
                            <div class="field-grid">
                                <div class="field">
                                    <label for="decision">Keputusan Marketing</label>
                                    <select id="decision" name="decision" required @if($errors->has('decision')) aria-invalid="true" aria-describedby="decision-error" @endif>
                                        <option value="">— Pilih keputusan —</option>
                                        <option value="new" @selected(old('decision') === 'new')>Permintaan Baru</option>
                                        <option value="continuation" @selected(old('decision') === 'continuation')>Kelanjutan</option>
                                        <option value="duplicate" @selected(old('decision') === 'duplicate')>Duplikat</option>
                                    </select>
                                    @error('decision')<p class="field-error" id="decision-error">{{ $message }}</p>@enderror
                                </div>

                                <div class="field">
                                    <label for="primary_brief_id">Permintaan utama <span class="muted">(wajib untuk Kelanjutan/Duplikat)</span></label>
                                    <select id="primary_brief_id" name="primary_brief_id" @if($errors->has('primary_brief_id')) aria-invalid="true" aria-describedby="primary_brief_id-error" @endif>
                                        <option value="">— Pilih kandidat —</option>
                                        @foreach ($duplicateCandidates as $candidate)
                                            <option value="{{ $candidate['id'] }}" @selected((string) old('primary_brief_id') === (string) $candidate['id'])>
                                                {{ $candidate['request_number'] ?: 'Draft #'.$candidate['id'] }} — {{ $candidate['client_name'] ?: 'Klien belum diisi' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('primary_brief_id')<p class="field-error" id="primary_brief_id-error">{{ $message }}</p>@enderror
                                </div>

                                <div class="field field-full">
                                    <label for="decision_reason">Alasan keputusan <span class="muted">(wajib untuk Kelanjutan/Duplikat)</span></label>
                                    <textarea id="decision_reason" name="decision_reason" maxlength="2000" rows="3"
                                              @if($errors->has('decision_reason')) aria-invalid="true" aria-describedby="decision_reason-error" @endif>{{ old('decision_reason') }}</textarea>
                                    @error('decision_reason')<p class="field-error" id="decision_reason-error">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <button class="button" type="submit">Simpan keputusan</button>
                            </div>
                        </form>
                    </div>
                @endif
            </section>
        @elseif ($brief->exists && $isStepTwo)
            {{-- Separate POST form remains outside the main update form. --}}
            <form id="ui03-step2-review-form" method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                @csrf
            </form>
        @elseif ($brief->exists && $isStepThree)
            <form id="ui03-step3-review-form" method="POST"
                  action="{{ route('prod02.duplicate-check', $brief) }}">
                @csrf
            </form>
        @elseif ($brief->exists && !$isStepFive
                 && !$isStepOne && !$isStepTwo && !$isStepThree && !$isStepFour)
            <section class="card ui03-pre-submit-card" aria-labelledby="pre-submit-review-heading">
                <div>
                    <span class="ui03-section-kicker">LANGKAH SEBELUM PENGAJUAN RESMI</span>
                    <h2 class="card-title" id="pre-submit-review-heading">Pemeriksaan Sebelum Pengajuan</h2>
                    <p>Simpan Draf tetap dapat dilakukan tanpa pemeriksaan ini. Sebelum pengajuan resmi, tinjau kandidat dan catat keputusan Marketing melalui pemeriksaan yang tersedia.</p>
                </div>
                <form method="POST" action="{{ route('prod02.duplicate-check', $brief) }}">
                    @csrf
                    <button class="ui03-review-link" type="submit">Buka Pemeriksaan Sebelum Pengajuan <span aria-hidden="true">→</span></button>
                </form>
            </section>
        @endif
    </div>
@endsection
