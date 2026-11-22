@extends('layouts.app')

@section('title', 'Detail Distribusi Brief · Enterprise BPM')
@section('eyebrow', 'Production · PROD-04')
@section('page-title', $version->document_number.' · Rev.'.str_pad((string) $version->revision, 2, '0', STR_PAD_LEFT))
@section('page-description', $version->marketingBrief->clientBrief->request_number.' · snapshot resmi terkunci')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if (session('warning'))<div class="notice notice-warning" role="alert">{{ session('warning') }}</div>@endif

        <section class="card">
            <div class="section-heading">
                <div><h2>Status distribusi</h2><p>Submit readiness: {{ str_replace('_', ' ', $version->readiness_status) }} · {{ $version->submitted_at->format('d-m-Y H:i:s') }}</p></div>
                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $version->distribution_status)) }}">{{ str_replace('_', ' ', $version->distribution_status) }}</span>
            </div>
            @if ($version->delivery_retrying)<p class="notice notice-warning" role="status">DELIVERY_RETRYING · pemulihan distribusi sedang berlangsung.</p>@endif
            @if ($version->slaTracking?->overdue_detected_at)<p class="notice notice-warning" role="status">ACK_OVERDUE · {{ $version->slaTracking->overdue_detected_at->format('d-m-Y H:i:s') }}</p>@endif
            <dl class="detail-grid">
                <div class="detail-item"><dt>Nomor resmi dan revisi</dt><dd>{{ $version->document_number }} · Rev.{{ str_pad((string) $version->revision, 2, '0', STR_PAD_LEFT) }}</dd></div>
                <div class="detail-item"><dt>Pengirim</dt><dd>{{ $version->submitter->name }}</dd></div>
                <div class="detail-item"><dt>Berhasil tersedia</dt><dd>{{ $version->delivered_at?->format('d-m-Y H:i:s') ?? 'Belum lengkap' }}</dd></div>
                <div class="detail-item"><dt>Acknowledge</dt><dd>
                    @if ($version->acknowledgement)
                        {{ $version->acknowledgement->actor->name }} · {{ $version->acknowledgement->method }} · {{ $version->acknowledgement->acknowledged_at->format('d-m-Y H:i:s') }}
                    @else
                        Belum dikonfirmasi
                    @endif
                </dd></div>
                <div class="detail-item"><dt>Tugas validasi PROD-05</dt><dd>{{ $version->validationTask?->status ?? 'Belum tersedia' }} @if($version->validationTask) · {{ $version->validationTask->assignee->name }}@endif</dd></div>
                <div class="detail-item"><dt>Indikator SLA</dt><dd>
                    @if ($version->slaTracking)
                        Dimulai {{ $version->slaTracking->available_at->format('d-m-Y H:i:s') }}
                        @if ($version->slaTracking->reminder_sent_at) · Pengingat {{ $version->slaTracking->reminder_sent_at->format('d-m-Y H:i:s') }}@endif
                    @else
                        Belum dimulai
                    @endif
                </dd></div>
            </dl>
            <div class="actions" style="margin-top:18px">
                @if ($version->distribution_status === 'DELIVERY_FAILED' && $version->marketingBrief->marketing_user_id === auth()->id())
                    <form method="POST" action="{{ route('prod04.retry', $version) }}">@csrf<button class="button" type="submit">Retry penerima gagal</button></form>
                @endif
                @if (in_array($version->distribution_status, ['PENDING_ACKNOWLEDGEMENT', 'DELIVERY_FAILED'], true) && $version->recipients->contains(fn ($recipient) => $recipient->user_id === auth()->id() && $recipient->obligation_type === 'PRIMARY' && $recipient->delivery_status === 'DELIVERED'))
                    <form method="POST" action="{{ route('prod04.acknowledge', $version) }}">@csrf<button class="button" type="submit">Acknowledge penerimaan</button></form>
                @endif
                <a class="button button-secondary" href="{{ route('prod04.index') }}">Daftar distribusi</a>
            </div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Snapshot dokumen resmi</h2><p>Snapshot versi ini tidak dapat ditimpa oleh perubahan Draft berikutnya.</p></div></div>
            @foreach ($version->snapshot['marketing_brief']['components'] ?? [] as $component)
                <div class="detail-item"><dt>{{ str_replace('_', ' ', $component['component']) }}</dt><dd>{{ $component['value'] ?: '—' }} @if($component['is_provisional'])<span class="status-badge status-review">PROVISIONAL</span><p class="small muted">Dasar: {{ $component['information_basis'] ?: '—' }}</p>@endif</dd></div>
            @endforeach
            <h3 class="card-title">Design Request pada saat Submit</h3>
            @forelse ($version->snapshot['design_requests'] ?? [] as $designRequest)
                <p class="small">#{{ $designRequest['id'] }} · {{ $designRequest['title'] }} · {{ $designRequest['classification'] }} · {{ $designRequest['status'] }}</p>
            @empty
                <p class="small muted">Tidak ada Design Request pada snapshot.</p>
            @endforelse
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Penerima dan akses</h2><p>Distribusi sukses hanya setelah akses Asst Manager, akses Admin, dan tugas validasi tersedia.</p></div></div>
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Penerima</th><th>Kewajiban</th><th>Status akses</th><th>Notifikasi internal</th><th>Percobaan</th><th>Error terakhir</th></tr></thead>
                <tbody>
                @foreach ($version->recipients as $recipient)
                    <tr>
                        <td>{{ $recipient->user->name }} · {{ $recipient->role_code }}</td>
                        <td>{{ $recipient->obligation_type }}</td>
                        <td>{{ $recipient->delivery_status }} @if($recipient->delivered_at) · {{ $recipient->delivered_at->format('d-m-Y H:i:s') }}@endif</td>
                        <td>{{ $recipient->notified_at?->format('d-m-Y H:i:s') ?? 'Belum tercatat' }}</td>
                        <td>{{ $recipient->attempt_count }}</td>
                        <td>{{ $recipient->last_error ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Audit PROD-04</h2><p>Riwayat distribusi dan Acknowledge bersifat append-only.</p></div></div>
            <ol class="timeline">
                @forelse ($version->events->sortByDesc('occurred_at') as $event)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>{{ str_replace('_', ' ', $event->event_type) }}</strong><time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">{{ $event->actor?->name ?? 'BPM' }} · {{ $event->actor_role }} @if($event->reason) · {{ $event->reason }}@endif</p></div></li>
                @empty
                    <li class="small muted">Belum ada aktivitas.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
