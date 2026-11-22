@extends('layouts.app')

@section('title', 'Distribusi Brief Marketing · Enterprise BPM')
@section('eyebrow', 'Production · PROD-04')
@section('page-title', 'Distribusi Brief Marketing')
@section('page-description', 'Snapshot resmi, penerima, dan status Acknowledge per versi.')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if (session('warning'))<div class="notice notice-warning" role="alert">{{ session('warning') }}</div>@endif

        <section class="card">
            <div class="section-heading">
                <div><h2>Versi Brief</h2><p>Distribusi selesai hanya setelah akses kedua penerima dan tugas Asst Manager tersedia.</p></div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Nomor / Revisi</th><th>Client Brief</th><th>Status distribusi</th><th>Penerima</th><th>Waktu Submit</th></tr></thead>
                    <tbody>
                    @forelse ($versions as $version)
                        <tr>
                            <td><a href="{{ route('prod04.show', $version) }}"><strong>{{ $version->document_number }}</strong> · Rev.{{ str_pad((string) $version->revision, 2, '0', STR_PAD_LEFT) }}</a></td>
                            <td>{{ $version->marketingBrief->clientBrief->request_number }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $version->distribution_status)) }}">{{ str_replace('_', ' ', $version->distribution_status) }}</span>
                                @if ($version->slaTracking?->overdue_detected_at)<span class="status-badge status-cancelled">ACK OVERDUE</span>@endif
                                @if ($version->delivery_retrying)<span class="status-badge status-review">DELIVERY RETRYING</span>@endif
                            </td>
                            <td>{{ $version->recipients->where('delivery_status', 'DELIVERED')->count() }} / {{ $version->recipients->count() }} tersedia</td>
                            <td>{{ $version->submitted_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><h3>Belum ada distribusi</h3><p>Brief yang memenuhi readiness dapat dikirim oleh Marketing melalui halaman detail Brief.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $versions->links() }}
        </section>
    </div>
@endsection
