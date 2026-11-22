@extends('layouts.app')

@section('title', 'Detail Validasi Brief · Enterprise BPM')
@section('eyebrow', 'Production · PROD-05')
@section('page-title', $validation->briefVersion->document_number · 'Rev.' . str_pad((string) $validation->briefVersion->revision, 2, '0', STR_PAD_LEFT))
@section('page-description', $validation->briefVersion->marketingBrief->clientBrief->request_number · ' · validasi Tahap 5')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if (session('warning'))<div class="notice notice-warning" role="alert">{{ session('warning') }}</div>@endif

        <section class="card">
            <div class="section-heading">
                <div>
                    <h2>Status validasi</h2>
                    <p>{{ $validation->briefVersion->marketingBrief->clientBrief->request_number }} · submit {{ $validation->briefVersion->submitted_at->format('d-m-Y H:i:s') }}</p>
                </div>
                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $validation->status)) }}">{{ str_replace('_', ' ', $validation->status) }}</span>
            </div>

            <dl class="detail-grid">
                <div class="detail-item"><dt>Nomor resmi dan revisi</dt><dd>{{ $validation->briefVersion->document_number }} · Rev.{{ str_pad((string) $validation->briefVersion->revision, 2, '0', STR_PAD_LEFT) }}</dd></div>
                <div class="detail-item"><dt>Client Brief</dt><dd>{{ $validation->briefVersion->marketingBrief->clientBrief->request_number }} · {{ $validation->briefVersion->marketingBrief->clientBrief->client_name }}</dd></div>
                <div class="detail-item"><dt>Pemasok validasi</dt><dd>{{ $validation->validator?->name ?? 'Belum ditetapkan' }}</dd></div>
                <div class="detail-item"><dt>Klasifikasi dampak</dt><dd>{{ $validation->impact_classification ?? 'Belum diklasifikasi' }} @if($validation->impact_classification_confirmed) · disahkan@endif</dd></div>
                <div class="detail-item"><dt>Data Provisional</dt><dd>
                    @if ($validation->provisional_data && count($validation->provisional_data) > 0)
                        Ada {{ count($validation->provisional_data) }} informasi Provisional
                    @else
                        Tidak ada
                    @endif
                </dd></div>
                <div class="detail-item-wide"><dt>Keputusan Manager Produksi</dt><dd>
                    @if ($validation->manager_decision)
                        {{ $validation->manager_decision }} · {{ $validation->manager_decision_at?->format('d-m-Y H:i:s') }}
                    @else
                        Belum ada keputusan
                    @endif
                </dd></div>
                <div class="detail-item-wide"><dt>ON HOLD</dt><dd>
                    @if ($validation->activityControls->contains('control_status', 'ON_HOLD'))
                        Aktif · dilepaskan {{ $validation->hold_released_at?->format('d-m-Y H:i:s') ?? 'Belum' }}
                    @else
                        Tidak aktif
                    @endif
                </dd></div>
            </dl>

            <div class="actions" style="margin-top:18px">
                @if ($roles->has(auth()->user(), 'ASST_MANAGER_PRODUKSI') && $validation->status === 'UNDER_REVIEW')
                    <form method="POST" action="{{ route('prod05.result', $validation) }}">@csrf
                        <select name="status" class="field" style="display:inline-block; min-height:43px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                            <option value="VALIDATED">VALIDATED</option>
                            <option value="VALIDATED_WITH_NOTES">VALIDATED_WITH_NOTES</option>
                            <option value="RETURNED">RETURNED</option>
                            <option value="PENDING_MANAGER">PENDING_MANAGER</option>
                        </select>
                        <input type="text" name="reason" placeholder="Alasan" class="field" style="display:inline-block; min-height:43px; width:200px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                        <button class="button" type="submit">Berikan hasil validasi</button>
                    </form>
                @endif

                @if ($roles->has(auth()->user(), 'ASST_MANAGER_PRODUKSI') && $validation->status !== 'DRAFT')
                    <form method="POST" action="{{ route('prod05.classify', $validation) }}">@csrf
                        <select name="classification" class="field" style="display:inline-block; min-height:43px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                            <option value="NO_IMPACT">NO_IMPACT</option>
                            <option value="MINOR">MINOR</option>
                            <option value="MAJOR">MAJOR</option>
                            <option value="CRITICAL">CRITICAL</option>
                            <option value="UNDETERMINED">UNDETERMINED</option>
                        </select>
                        <input type="text" name="reason" placeholder="Alasan klasifikasi" class="field" style="display:inline-block; min-height:43px; width:200px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                        <button class="button" type="submit">Klasifikasikan dampak</button>
                    </form>
                @endif

                @if ($roles->has(auth()->user(), 'MANAGER_PRODUKSI') && $validation->status === 'PENDING_MANAGER')
                    <form method="POST" action="{{ route('prod05.manager-decision', $validation) }}">@csrf
                        @if ($validation->status === 'PENDING_MANAGER')
                            <select name="decision" class="field" style="display:inline-block; min-height:43px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                                <option value="APPROVED_FOR_REVIEW">APPROVED_FOR_REVIEW</option>
                                <option value="APPROVED_CONDITIONAL">APPROVED_CONDITIONAL</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        @endif
                        <input type="text" name="reason" placeholder="Alasan keputusan" class="field" style="display:inline-block; min-height:43px; width:200px; padding:10px 12px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; font-size:12px;">
                        <button class="button" type="submit">Buat keputusan</button>
                    </form>
                @endif

                @if ($roles->has(auth()->user(), 'MANAGER_PRODUKSI') && $validation->impact_classification === 'CRITICAL')
                    <form method="POST" action="{{ route('prod05.approve-downgrade', $validation) }}">@csrf
                        <select name="classification"><option value="MAJOR">MAJOR</option><option value="MINOR">MINOR</option><option value="NO_IMPACT">NO_IMPACT</option></select>
                        <input name="reason" type="text" required maxlength="1000" placeholder="Dasar keputusan Manager">
                        <button class="button" type="submit">Setujui penurunan CRITICAL</button>
                    </form>
                @endif
                <a class="button button-secondary" href="{{ route('prod05.index') }}">Daftar validasi</a>
            </div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Snapshot dokumen sumber (PROD-04)</h2><p>Snapshot versi ini tidak dapat ditimpa oleh perubahan Draft berikutnya.</p></div></div>
            <dl class="detail-grid">
                @foreach ($validation->briefVersion->snapshot['marketing_brief']['components'] ?? [] as $component)
                    <div class="detail-item"><dt>{{ str_replace('_', ' ', $component['component']) }}</dt><dd>{{ $component['value'] ?: '—' }} @if($component['is_provisional'])<span class="status-badge status-review">PROVISIONAL</span><p class="small muted">Dasar: {{ $component['information_basis'] ?: '—' }}</p>@endif</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Kontrol aktivitas (ON HOLD)</h2><p>Kontrol spesifik per aktivitas, terpisah dari status dokumen validasi.</p></div></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Aktivitas</th><th>Tipe</th><th>Kontrol</th><th>Alasan hold</th><th>Diminta</th><th>Dilepas</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($validation->activityControls as $control)
                        <tr>
                            <td>{{ $control->activity_key }}</td>
                            <td>{{ $control->activity_type }}</td>
                            <td><span class="status-badge status-{{ strtolower($control->control_status) }}">{{ $control->control_status }}</span></td>
                            <td>{{ $control->hold_reason ?? '—' }}</td>
                            <td>{{ $control->hold_requested_at?->format('d-m-Y H:i:s') ?? '—' }}</td>
                            <td>{{ $control->hold_released_at?->format('d-m-Y H:i:s') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada kontrol aktivitas</h3><p>Kontrol aktivitas dibuat saat klasifikasi CRITICAL atau penangguhan aktivitas tertentu.</p></div></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Riwayat klasifikasi revisi</h2><p>Riwayat klasifikasi dampak revisi bersifat append-only.</p></div></div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Revisi</th><th>Direkomendasikan oleh</th><th>Rekomendasi</th><th>Dikonfirmasi</th><th>Klasifikasi akhir</th><th>Waktu</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($validation->revisionClassifications as $classification)
                        <tr>
                            <td>{{ $classification->revision }}</td>
                            <td>{{ $classification->recommendedBy->name }}</td>
                            <td>{{ $classification->recommended_classification }}</td>
                            <td>{{ $classification->confirmedBy->name }}</td>
                            <td>{{ $classification->confirmed_classification }}</td>
                            <td>{{ $classification->occurred_at->format('d-m-Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada klasifikasi</h3><p>Klasifikasi dampak dibuat oleh Asst Manager Produksi.</p></div></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Audit PROD-05</h2><p>Riwayat validasi bersifat append-only; koreksi dicatat sebagai event baru.</p></div></div>
            <ol class="timeline">
                @forelse ($validation->events->sortByDesc('occurred_at') as $event)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>{{ str_replace('_', ' ', $event->event_type) }}</strong><time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">{{ $event->actor?->name ?? 'BPM' }} · {{ $event->actor_role }} @if($event->reason) · {{ $event->reason }}@endif</p></div></li>
                @empty
                    <li class="small muted">Belum ada aktivitas.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
