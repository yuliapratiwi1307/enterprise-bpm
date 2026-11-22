@extends('layouts.app')

@section('title', 'Detail Brief Marketing · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Brief Marketing #' . $brief->id)
@section('page-description', $brief->clientBrief->request_number . ' · ' . e($brief->clientBrief->client_name))

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if (session('warning'))<div class="notice notice-warning" role="status">{{ session('warning') }}</div>@endif
        @if ($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <section class="card">
            <div class="section-heading">
                <div><h2>Ringkasan Brief</h2><p>Nomor resmi sumber: <strong>{{ $brief->clientBrief->request_number }}</strong></p></div>
                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $brief->status)) }}">{{ str_replace('_', ' ', $brief->status) }}</span>
            </div>
            <dl class="detail-grid">
                @foreach (\App\Services\MarketingBriefReadiness::COMPONENTS as $key => $label)
                    @php($component = $brief->components->firstWhere('component', $key))
                    <div class="detail-item"><dt>{{ $label }}</dt><dd>{{ $component?->value ?: 'Belum dilengkapi' }} @if($component?->is_provisional)<span class="status-badge status-review">PROVISIONAL</span><p class="small muted">Dasar: {{ $component->information_basis ?: 'Belum dicatat' }}</p>@endif</dd></div>
                @endforeach
            </dl>
            <div class="actions" style="margin-top:18px">
                @if (in_array($brief->status, ['DRAFT', 'READY_FOR_SUBMISSION', 'READY_WITH_NOTES'], true))
                    <a class="button button-secondary" href="{{ route('prod03.briefs.edit', $brief) }}">Edit Draft</a>
                    <form method="POST" action="{{ route('prod03.briefs.validate-readiness', $brief) }}">@csrf<button class="button" type="submit">Validasi kesiapan</button></form>
                    <a class="button button-secondary" href="{{ route('prod03.design-requests.create', $brief) }}">Buat Design Request</a>
                    @if (in_array($brief->status, ['READY_FOR_SUBMISSION', 'READY_WITH_NOTES'], true))
                        <form method="POST" action="{{ route('prod04.submit', $brief) }}">
                            @csrf
                            <button class="button" type="submit">Submit &amp; Distribute · PROD-04</button>
                        </form>
                    @endif
                @endif
                <a class="button button-secondary" href="{{ route('prod03.briefs.index') }}">Daftar Brief</a>
                <a class="button button-secondary" href="{{ route('prod04.index') }}">Distribusi Brief</a>
            </div>
        </section>

        @if ($brief->readiness_snapshot)
            <section class="card card-subtle">
                <h2 class="card-title">Hasil validasi kesiapan</h2>
                @if ($brief->status === 'DRAFT')
                    <p class="small">Belum siap. Komponen atau persyaratan yang perlu dilengkapi:</p>
                    <ul>@foreach ($brief->readiness_snapshot['missing_components'] ?? [] as $missing)<li>{{ $missing }}</li>@endforeach</ul>
                    @if (!empty($brief->readiness_snapshot['critical_outstanding']))<p class="small">Design Request kritis belum selesai: {{ implode(', ', $brief->readiness_snapshot['critical_outstanding']) }}</p>@endif
                @else
                    <p class="small">Status kesiapan: <strong>{{ str_replace('_', ' ', $brief->status) }}</strong></p>
                    @foreach ($brief->readiness_snapshot['notes'] ?? [] as $note)
                        <p class="small">Design Request #{{ $note['design_request_id'] }} · PIC #{{ $note['pic_user_id'] }} · deadline {{ $note['deadline_at'] }} · dampak: {{ $note['impact'] }}</p>
                    @endforeach
                @endif
            </section>
        @endif

        <section class="card">
            <div class="section-heading"><div><h2>Design Request</h2><p>Tugas desain terpisah dari status Brief Marketing.</p></div></div>
            <div class="table-wrap"><table class="data-table">
                <thead><tr><th>Tugas</th><th>Klasifikasi</th><th>Status</th><th>PIC</th><th>Deadline</th></tr></thead>
                <tbody>
                @forelse ($brief->designRequests as $task)
                    <tr><td><a href="{{ route('prod03.design-requests.show', $task) }}">#{{ $task->id }} · {{ $task->title }}</a></td><td>{{ $task->classification ?: 'Belum ditetapkan' }}</td><td><span class="status-badge status-{{ strtolower(str_replace('_', '-', $task->status)) }}">{{ str_replace('_', ' ', $task->status) }}</span> @if($task->is_overdue)<span class="status-badge status-cancelled">OVERDUE</span>@endif</td><td>{{ $task->currentPic?->name ?? '—' }}</td><td>{{ $task->deadline_at?->format('d-m-Y H:i') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><h3>Belum ada Design Request</h3><p>Marketing dapat membuat beberapa tugas desain untuk Brief ini.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Riwayat aktivitas</h2><p>Audit PROD-03 tersimpan append-only.</p></div></div>
            <ol class="timeline">
                @forelse ($events as $event)
                    <li class="timeline-item"><span class="timeline-dot" aria-hidden="true"></span><div class="timeline-content"><div class="timeline-heading"><strong>{{ str_replace('_', ' ', $event->event_type) }}</strong><time datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->format('d-m-Y H:i:s') }}</time></div><p class="timeline-meta">{{ $event->actor?->name }} · {{ $event->actor_role }} @if($event->design_request_id) · Design Request #{{ $event->design_request_id }}@endif @if($event->reason) · {{ $event->reason }}@endif</p></div></li>
                @empty
                    <li class="small muted">Belum ada aktivitas.</li>
                @endforelse
            </ol>
        </section>
    </div>
@endsection
