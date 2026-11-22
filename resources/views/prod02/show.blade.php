@extends('layouts.app')

@section('title', 'Permintaan '.($brief->request_number ?: '#'.$brief->id).' — PROD-02')
@section('eyebrow', 'Production · PROD-02')
@section('page-title', 'Detail permintaan')
@section('page-description', 'Informasi resmi, metadata, dan riwayat proses brief klien.')

@section('content')
    <div class="stack">
        <div class="button-row">
            <a class="button button-secondary" href="{{ route('prod02.index') }}"><span aria-hidden="true">←</span> Kembali ke daftar</a>
        </div>

        @if (session('message'))
            <div class="notice notice-success" role="status">{{ session('message') }}</div>
        @endif

        <section class="card stack" aria-labelledby="request-heading">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Brief #{{ $brief->id }}</p>
                    <h2 class="page-title" id="request-heading" style="font-size:22px">Permintaan {{ $brief->status }}</h2>
                    <p class="page-description">Dimiliki dan dikelola oleh Marketing.</p>
                </div>
                <span class="status-badge status-{{ strtolower($brief->status) }}">{{ $brief->status }}</span>
            </div>

            @if ($brief->status === 'RECORDED')
                <div class="card card-subtle">
                    <p class="eyebrow">Nomor permintaan resmi</p>
                    <div class="button-row">
                        <span class="request-number" id="official-request-number">{{ $brief->request_number }}</span>
                        <button class="button button-secondary btn-inline" type="button" id="copy-request-number" aria-describedby="copy-request-feedback">Salin</button>
                    </div>
                    <p class="small muted" id="copy-request-feedback" role="status" aria-live="polite" style="margin:10px 0 0"></p>
                    <p class="small muted" style="margin:10px 0 0">Tanggal dan waktu pencatatan resmi: {{ $brief->recorded_at?->format('d-m-Y H:i:s') ?? '—' }}</p>
                </div>
            @endif

            <dl class="detail-grid">
                <div class="detail-item">
                    <dt>Nama klien</dt>
                    <dd>{{ $brief->client_name ?: '—' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>Sumber komunikasi</dt>
                    <dd>{{ $brief->communication_source ?: '—' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>Jenis kebutuhan</dt>
                    <dd>{{ $brief->requirement_type ?: '—' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>PIC klien</dt>
                    <dd>{{ $brief->client_pic ?: '—' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>Target waktu</dt>
                    <dd>{{ $brief->target_date?->format('d-m-Y') ?? '—' }}</dd>
                </div>
                <div class="detail-item">
                    <dt>Lokasi pekerjaan</dt>
                    <dd>{{ $brief->work_location ?: '—' }}</dd>
                </div>
                <div class="detail-item detail-item-wide">
                    <dt>Deskripsi permintaan</dt>
                    <dd>{{ $brief->description ?: '—' }}</dd>
                </div>
            </dl>

            @if ($brief->primaryBrief)
                <div class="notice">
                    <strong>Permintaan utama:</strong>
                    {{ $brief->primaryBrief->request_number ?: 'Brief #'.$brief->primaryBrief->id }}
                </div>
            @endif
            @if ($brief->duplicate_reason)
                <div class="notice notice-warning">
                    <strong>Alasan duplikat</strong>
                    <p style="margin:5px 0 0">{{ $brief->duplicate_reason }}</p>
                </div>
            @endif
        </section>

        <section class="card" aria-labelledby="audit-heading">
            <div class="section-heading">
                <div>
                    <h2 id="audit-heading">Riwayat audit</h2>
                    <p>Peristiwa yang dicatat untuk brief ini, diurutkan dari yang terbaru.</p>
                </div>
            </div>

            @php
                $eventLabels = [
                    'DRAFT_CREATED' => 'Draft dibuat',
                    'DRAFT_UPDATED' => 'Draft diperbarui',
                    'DUPLICATE_CHECKED' => 'Pemeriksaan duplikasi',
                    'DUPLICATE_DECISION' => 'Keputusan Marketing',
                    'REQUEST_RECORDED' => 'Permintaan dicatat resmi',
                ];
                $auditEvents = $brief->events->sortByDesc('occurred_at');
            @endphp

            @if ($auditEvents->isEmpty())
                <div class="empty-state">
                    <span class="empty-icon" aria-hidden="true">↻</span>
                    <h3>Belum ada peristiwa audit</h3>
                    <p>Riwayat proses akan tampil di sini saat ada aktivitas yang tercatat.</p>
                </div>
            @else
                <ol class="timeline">
                    @foreach ($auditEvents as $event)
                        <li class="timeline-item">
                            <span class="timeline-dot" aria-hidden="true"></span>
                            <div class="timeline-content">
                                <div class="timeline-heading">
                                    <strong>{{ $eventLabels[$event->event_type] ?? str_replace('_', ' ', $event->event_type) }}</strong>
                                    <time datetime="{{ $event->occurred_at?->toISOString() }}">{{ $event->occurred_at?->format('d-m-Y H:i:s') ?? 'Waktu tidak tersedia' }}</time>
                                </div>
                                <p class="timeline-meta">
                                    Aktor: {{ $event->actor_role ?: '—' }}
                                    @if ($event->actor_user_id)
                                        · ID pengguna #{{ $event->actor_user_id }}
                                    @endif
                                </p>
                                @if ($event->reason)
                                    <p class="timeline-meta">Alasan: {{ $event->reason }}</p>
                                @elseif ($event->after_data['decision_reason'] ?? null)
                                    <p class="timeline-meta">Alasan: {{ $event->after_data['decision_reason'] }}</p>
                                @endif
                                @if ($event->event_type === 'REQUEST_RECORDED' && ($event->after_data['request_number'] ?? null))
                                    <p class="timeline-meta">Nomor resmi: <strong>{{ $event->after_data['request_number'] }}</strong></p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    @if ($brief->status === 'RECORDED')
        <script>
            document.getElementById('copy-request-number')?.addEventListener('click', async () => {
                const requestNumber = document.getElementById('official-request-number')?.textContent?.trim();
                const feedback = document.getElementById('copy-request-feedback');

                if (!requestNumber || !feedback) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(requestNumber);
                    feedback.textContent = 'Nomor resmi berhasil disalin.';
                } catch {
                    feedback.textContent = 'Nomor resmi tidak dapat disalin. Silakan salin secara manual.';
                }
            });
        </script>
    @endif
@endsection
