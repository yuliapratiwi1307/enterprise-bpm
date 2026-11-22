@extends('layouts.app')

@section('title', 'Detail Design Request · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Design Request #' . $designRequest->id)
@section('page-description', $designRequest->title)

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if ($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <section class="card">
            <div class="section-heading"><div><h2>{{ $designRequest->title }}</h2><p>Brief Marketing #{{ $designRequest->marketing_brief_id }} · {{ $designRequest->marketingBrief->clientBrief->request_number }}</p></div><span class="status-badge status-{{ strtolower(str_replace('_', '-', $designRequest->status)) }}">{{ str_replace('_', ' ', $designRequest->status) }}</span></div>
            <p>{{ $designRequest->description }}</p>
            <dl class="detail-grid">
                <div class="detail-item"><dt>Klasifikasi final</dt><dd>{{ $designRequest->classification }} <span class="muted">· rekomendasi sistem {{ $designRequest->recommended_classification ?? 'belum tersedia' }}</span></dd></div>
                <div class="detail-item"><dt>PIC aktif</dt><dd>{{ $designRequest->currentPic?->name ?? 'Belum diambil' }}</dd></div>
                <div class="detail-item"><dt>Deadline</dt><dd>{{ $designRequest->deadline_at?->format('d-m-Y H:i') ?? '—' }} @if($designRequest->is_overdue)<span class="status-badge status-cancelled">OVERDUE</span>@endif</dd></div>
                <div class="detail-item"><dt>Dampak</dt><dd>{{ $designRequest->impact }}</dd></div>
                @if ($designRequest->replacement_information)
                    <div class="detail-item detail-item-wide"><dt>Informasi pengganti dari klien</dt><dd>{{ $designRequest->replacement_information }} <span class="muted">· {{ $designRequest->replacement_source }}</span></dd></div>
                @endif
                <div class="detail-item detail-item-wide"><dt>Alasan klasifikasi</dt><dd>{{ $designRequest->classification_reason }}</dd></div>
            </dl>
        </section>

        @if ($canReview)
            <section class="card stack">
                @if (!in_array($designRequest->status, ['COMPLETED', 'CANCELLED'], true))
                    <form method="POST" action="{{ route('prod03.design-requests.classification', $designRequest) }}" class="field-grid">
                        @csrf
                        <div class="field"><label for="classification">Ubah klasifikasi</label><select id="classification" name="classification"><option value="CRITICAL">Kritis</option><option value="NONCRITICAL">Nonkritis</option></select></div>
                        <div class="field"><label for="classification-reason">Alasan perubahan</label><input id="classification-reason" name="reason" required></div>
                        <div class="actions"><button class="button button-secondary" type="submit">Catat perubahan klasifikasi</button></div>
                    </form>
                    @if ($designRequest->status === 'SUBMITTED_FOR_REVIEW')
                        <div class="actions">
                            <form method="POST" action="{{ route('prod03.task-pool.review', $designRequest) }}">@csrf<input type="hidden" name="action" value="accept"><button class="button" type="submit">Accept Result</button></form>
                            <form method="POST" action="{{ route('prod03.task-pool.review', $designRequest) }}">@csrf<input type="hidden" name="action" value="revision"><label class="field"><span>Catatan revisi</span><input name="reason" required></label><button class="button button-secondary" type="submit">Request Revision</button></form>
                        </div>
                    @endif
                    @if ($pendingDeadlineChange)
                        <form method="POST" action="{{ route('prod03.design-requests.deadline-decision', [$designRequest, $pendingDeadlineChange]) }}" class="actions">
                            @csrf
                            <span>Usulan deadline: {{ $pendingDeadlineChange->requested_deadline_at->format('d-m-Y H:i') }} · {{ $pendingDeadlineChange->request_reason }}</span>
                            <input type="hidden" name="decision_reason" value="Ditinjau Marketing">
                            <button class="button" name="decision" value="approve" type="submit">Setujui</button>
                            <button class="button button-secondary" name="decision" value="reject" type="submit">Tolak</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('prod03.design-requests.cancel', $designRequest) }}" class="field-grid">@csrf<label class="field"><span>Alasan pembatalan</span><input name="reason" required></label>@if($designRequest->classification === 'CRITICAL')<label class="field"><span>Informasi pengganti dari klien (bila tersedia)</span><textarea name="replacement_information"></textarea></label><label class="field"><span>Sumber informasi pengganti</span><input name="replacement_source"></label>@endif<button class="button button-secondary" type="submit">Batalkan Design Request</button></form>
                @endif
            </section>
        @endif

        @if ($isAssignedDesigner)
            <section class="card stack">
                @if ($designRequest->status === 'CANCELLATION_PENDING_ACK')
                    <div class="notice notice-warning">Instruksi penghentian telah diterbitkan. Hentikan pekerjaan dan konfirmasi penerimaan; ini bukan keputusan approval.</div>
                    <form method="POST" action="{{ route('prod03.task-pool.acknowledge-cancellation', $designRequest) }}">@csrf<button class="button" type="submit">Konfirmasi penerimaan instruksi</button></form>
                @elseif (in_array($designRequest->status, ['IN_PROGRESS', 'REVISION_REQUIRED'], true))
                    @if ($designRequest->status === 'REVISION_REQUIRED')<div class="notice notice-warning">Revisi ditujukan kepada PIC sebelumnya. Anda dapat mengirim hasil revisi atau melepas tugas.</div>@endif
                    <form method="POST" enctype="multipart/form-data" action="{{ route('prod03.task-pool.submit-result', $designRequest) }}" class="actions">@csrf<label class="field"><span>Lampiran hasil versi berikutnya</span><input type="file" name="attachment" required></label><button class="button" type="submit">Kirim hasil</button></form>
                    <form method="POST" action="{{ route('prod03.task-pool.release', $designRequest) }}" class="actions">@csrf<label class="field"><span>Alasan release task</span><input name="reason" required></label><button class="button button-secondary" type="submit">Release Task</button></form>
                    <form method="POST" action="{{ route('prod03.task-pool.deadline-change', $designRequest) }}" class="field-grid">@csrf<div class="field"><label for="new-deadline">Usulan deadline baru</label><input id="new-deadline" type="datetime-local" name="requested_deadline_at" required></div><div class="field"><label for="deadline-reason">Alasan perubahan deadline</label><input id="deadline-reason" name="reason" required></div><button class="button button-secondary" type="submit">Ajukan perubahan deadline</button></form>
                @endif
            </section>
        @endif

        <section class="card">
            <div class="section-heading"><div><h2>Versi hasil</h2><p>Lampiran lama tetap tersimpan saat revisi, release, atau pembatalan.</p></div></div>
            <ol class="timeline">
                @forelse ($designRequest->attachments as $attachment)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>Versi {{ $attachment->version }} · {{ $attachment->original_name }}</strong><time datetime="{{ $attachment->created_at->toIso8601String() }}">{{ $attachment->created_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">{{ number_format($attachment->size_bytes / 1024, 1) }} KB · {{ $attachment->uploader->name }} · <a href="{{ route('prod03.design-requests.attachments.download', $attachment) }}">Unduh lampiran</a></p></div></li>
                @empty<li class="small muted">Belum ada lampiran hasil.</li>@endforelse
            </ol>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Riwayat assignment dan aktivitas</h2><p>Riwayat tidak dihapus ketika task dilepas atau dibatalkan.</p></div></div>
            <ol class="timeline">
                @foreach ($designRequest->assignments as $assignment)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>{{ $assignment->designer->name }}</strong><time>{{ $assignment->assigned_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">PIC assignment @if($assignment->released_at) · dilepas {{ $assignment->released_at->format('d-m-Y H:i:s') }} · {{ $assignment->release_reason }}@else · aktif @endif</p></div></li>
                @endforeach
                @foreach ($designRequest->events()->with('actor')->latest('occurred_at')->get() as $event)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>{{ str_replace('_', ' ', $event->event_type) }}</strong><time>{{ $event->occurred_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">{{ $event->actor->name }} · {{ $event->actor_role }} @if($event->reason) · {{ $event->reason }}@endif</p></div></li>
                @endforeach
            </ol>
        </section>
    </div>
@endsection
