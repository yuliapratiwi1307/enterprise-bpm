@extends('layouts.app')

@section('title', 'Validasi Brief Marketing · Enterprise BPM')
@section('eyebrow', 'Production · PROD-05')
@section('page-title', 'Validasi Brief Marketing')
@section('page-description', 'Tugas validasi oleh Asst Manager Produksi dan keputusan Manager Produksi.')

@section('content')
    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if (session('warning'))<div class="notice notice-warning" role="alert">{{ session('warning') }}</div>@endif

        <section class="card">
            <div class="section-heading">
                <div><h2>Tugas Validasi</h2><p>Asst Manager Produksi memeriksa kecukupan informasi brief. Manager Produksi mengambil keputusan bersyarat.</p></div>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nomor / Revisi</th>
                            <th>Client Brief</th>
                            <th>Status validasi</th>
                            <th>Klasifikasi dampak</th>
                            <th>Pemeriksa</th>
                            <th>Waktu dimulai</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($validations as $validation)
                        <tr>
                            <td>
                                <a href="{{ route('prod05.show', $validation) }}" class="table-primary">
                                    {{ $validation->briefVersion->document_number }} · Rev.{{ str_pad((string) $validation->briefVersion->revision, 2, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="table-secondary">{{ $validation->briefVersion->marketingBrief->marketing->name }}</div>
                            </td>
                            <td>{{ $validation->briefVersion->marketingBrief->clientBrief->request_number }}</td>
                            <td>
                                <span class="status-badge status-{{ strtolower(str_replace('_', '-', $validation->status)) }}">{{ str_replace('_', ' ', $validation->status) }}</span>
                            </td>
                            <td>
                                @if ($validation->impact_classification)
                                    <span class="status-badge status-{{ strtolower($validation->impact_classification) }}">{{ $validation->impact_classification }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $validation->validator?->name ?? '—' }}</td>
                            <td>{{ $validation->created_at->format('d-m-Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><h3>Belum ada tugas validasi</h3><p>Tugas validasi tersedia setelah distribusi Brief Marketing berhasil di Tahap 4.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $validations->links() }}
        </section>

        <section class="card">
            <div class="section-heading"><div><h2>Status yang disetujui</h2><p>DRAFT · SUBMITTED · UNDER_REVIEW · RETURNED · VALIDATED · VALIDATED_WITH_NOTES · PENDING_MANAGER · APPROVED_FOR_REVIEW · APPROVED_CONDITIONAL · ON_HOLD</p></div></div>
        </section>
    </div>
@endsection
