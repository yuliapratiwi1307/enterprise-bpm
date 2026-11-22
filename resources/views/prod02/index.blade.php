@extends('layouts.app')

@section('title', 'Permintaan Klien — PROD-02')
@section('eyebrow', 'Production · PROD-02')
@section('page-title', 'Permintaan Klien')
@section('page-description', 'Pantau dan kelola permintaan klien dalam satu workspace.')

@section('content')
    @php
        $pageBriefs = $briefs->getCollection();
        $draftCount = $pageBriefs->where('status', 'DRAFT')->count();
        $recordedCount = $pageBriefs->where('status', 'RECORDED')->count();
        $duplicateCount = $pageBriefs->where('status', 'DUPLICATE')->count();
        $cancelledCount = $pageBriefs->where('status', 'CANCELLED')->count();
        $availableStatuses = $pageBriefs->pluck('status')->unique()->sort()->values();
    @endphp

    <div class="stack ui03-requests">
        @if (session('message'))
            <div class="notice notice-success" role="status">{{ session('message') }}</div>
        @endif

        @php
            $ui03PageCount = max(1, $pageBriefs->count());
            $ui03Stats = [
                ['label' => 'Draft', 'count' => $draftCount, 'detail' => 'Perlu dilengkapi atau dilanjutkan', 'tone' => 'draft', 'icon' => 'edit'],
                ['label' => 'Tercatat', 'count' => $recordedCount, 'detail' => 'Nomor permintaan resmi terbit', 'tone' => 'recorded', 'icon' => 'check'],
                ['label' => 'Duplikasi', 'count' => $duplicateCount, 'detail' => 'Perlu perhatian Marketing', 'tone' => 'duplicate', 'icon' => 'alert'],
                ['label' => 'Dibatalkan', 'count' => $cancelledCount, 'detail' => 'Permintaan berstatus akhir', 'tone' => 'cancelled', 'icon' => 'cancel'],
            ];
        @endphp
        <section class="summary-grid ui03-request-summary" aria-label="Ringkasan status permintaan pada halaman ini">
            @foreach ($ui03Stats as $summary)
                <article class="summary-card ui03-status-card" data-summary-tone="{{ $summary['tone'] }}">
                    <div class="ui03-status-card-top">
                        <span class="ui03-status-card-icon" aria-hidden="true">
                            @if ($summary['icon'] === 'edit')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16 4l4 4L9 19H5v-4L16 4z"/></svg>
                            @elseif ($summary['icon'] === 'check')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                            @elseif ($summary['icon'] === 'alert')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3z"/><path d="M12 9v5m0 3h.01"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg>
                            @endif
                        </span>
                        <strong class="ui03-status-card-value">{{ $summary['count'] }}</strong>
                    </div>
                    <h3 class="ui03-status-card-name">{{ $summary['label'] }}</h3>
                    <p class="ui03-status-card-description">{{ $summary['detail'] }}</p>
                    <div class="ui03-status-card-track" aria-hidden="true"><span style="width:{{ round($summary['count'] * 100 / $ui03PageCount) }}%"></span></div>
                </article>
            @endforeach
        </section>

        <section class="card ui03-request-list" aria-labelledby="request-list-heading">
            <div class="section-heading ui03-request-list-heading">
                <div>
                    <h2 id="request-list-heading">Daftar permintaan</h2>
                    <p>Data milik akun Marketing Anda · halaman {{ $briefs->currentPage() }} dari {{ $briefs->lastPage() }}</p>
                </div>
                <span class="ui03-record-count">{{ $briefs->total() }} total</span>
            </div>

            @if ($pageBriefs->isEmpty())
                <div class="empty-state ui03-request-empty">
                    <span class="empty-icon" aria-hidden="true">P</span>
                    <h3>Belum ada permintaan klien</h3>
                    <p>Buat permintaan baru untuk mulai mencatat kebutuhan klien.</p>
                    <a class="button" href="{{ route('prod02.create') }}">Buat permintaan pertama</a>
                </div>
            @else
                <div class="ui03-request-filters" role="search" aria-label="Cari dan filter permintaan pada halaman ini">
                    <label class="ui03-search-field">
                        <span class="sr-only">Cari nomor, klien, atau pekerjaan</span>
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none">
                            <circle cx="8.8" cy="8.8" r="5.8" stroke="currentColor" stroke-width="1.6"/>
                            <path d="m13.2 13.2 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        <input id="request-search" type="search" autocomplete="off" placeholder="Cari nomor, klien, atau pekerjaan">
                    </label>
                    <label class="ui03-status-filter">
                        <span class="sr-only">Filter berdasarkan status</span>
                        <select id="request-status">
                            <option value="">Semua status</option>
                            @foreach ($availableStatuses as $status)
                                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="ui03-filter-result" id="request-result-count" aria-live="polite">
                        Menampilkan {{ $pageBriefs->count() }} permintaan
                    </p>
                </div>

                <div class="table-wrap ui03-request-table-wrap" role="region" aria-label="Tabel permintaan klien" tabindex="0">
                    <table class="data-table ui03-request-table">
                        <thead>
                            <tr>
                                <th scope="col">Nomor permintaan</th>
                                <th scope="col">Klien</th>
                                <th scope="col">Pekerjaan</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody id="request-table-body">
                            @foreach ($pageBriefs as $brief)
                                @php
                                    $requestNumber = $brief->request_number ?: 'Draft #'.$brief->id;
                                    $jobDescription = $brief->description;
                                    $searchText = implode(' ', [
                                        $requestNumber,
                                        $brief->client_name,
                                        $brief->requirement_type,
                                        $brief->description,
                                        $brief->status,
                                    ]);
                                @endphp
                                <tr data-search="{{ $searchText }}" data-status="{{ $brief->status }}">
                                    <td>
                                        @if ($brief->request_number)
                                            <span class="ui03-request-number">{{ $brief->request_number }}</span>
                                        @else
                                            <span class="ui03-request-number ui03-draft-number">Draft #{{ $brief->id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="table-primary">{{ $brief->client_name ?: 'Belum diisi' }}</span>
                                        @if ($brief->communication_source)
                                            <span class="table-secondary">{{ $brief->communication_source }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="table-primary">{{ $brief->requirement_type ?: 'Belum ditentukan' }}</span>
                                        @if ($jobDescription)
                                            <span class="table-secondary ui03-job-description">{{ $jobDescription }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ strtolower($brief->status) }}">
                                            {{ str_replace('_', ' ', $brief->status) }}
                                        </span>
                                        @if ($brief->status === 'DUPLICATE' && filled($brief->duplicate_reason))
                                            <span class="table-secondary">{{ $brief->duplicate_reason }}</span>
                                        @endif
                                    </td>
                                    <td class="actions-cell">
                                        @if ($brief->status === 'DRAFT')
                                            <a class="button button-secondary btn-inline ui03-detail-action" href="{{ route('prod02.edit', $brief) }}">
                                                Lanjutkan
                                                <span aria-hidden="true">→</span>
                                            </a>
                                        @else
                                            <a class="button button-secondary btn-inline ui03-detail-action" href="{{ route('prod02.show', $brief) }}">
                                                Detail
                                                <span aria-hidden="true">→</span>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="ui03-no-results" id="request-no-results" hidden>
                    <strong>Tidak ada permintaan yang cocok</strong>
                    <span>Coba ubah kata pencarian atau filter status.</span>
                </div>

                @if ($briefs->hasPages())
                    <nav class="ui03-request-pagination" aria-label="Navigasi halaman permintaan">
                        <div class="ui03-pagination-summary">Menampilkan <strong>{{ $briefs->firstItem() }}–{{ $briefs->lastItem() }}</strong> dari <strong>{{ $briefs->total() }}</strong> permintaan</div>
                        <div class="ui03-pagination-actions">
                            @if ($briefs->onFirstPage())
                                <span class="ui03-page-control is-disabled" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>
                            @else
                                <a class="ui03-page-control" href="{{ $briefs->previousPageUrl() }}" aria-label="Halaman sebelumnya">‹</a>
                            @endif
                            @foreach (range(max(1, $briefs->currentPage() - 2), min($briefs->lastPage(), $briefs->currentPage() + 2)) as $pageNumber)
                                @if ($pageNumber === $briefs->currentPage())
                                    <span class="ui03-page-control is-current" aria-current="page">{{ $pageNumber }}</span>
                                @else
                                    <a class="ui03-page-control" href="{{ $briefs->url($pageNumber) }}" aria-label="Halaman {{ $pageNumber }}">{{ $pageNumber }}</a>
                                @endif
                            @endforeach
                            @if ($briefs->hasMorePages())
                                <a class="ui03-page-control" href="{{ $briefs->nextPageUrl() }}" aria-label="Halaman berikutnya">›</a>
                            @else
                                <span class="ui03-page-control is-disabled" aria-disabled="true" aria-label="Halaman berikutnya">›</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>

    @if ($pageBriefs->isNotEmpty())
        <script>
            (() => {
                const searchInput = document.getElementById('request-search');
                const statusFilter = document.getElementById('request-status');
                const rows = Array.from(document.querySelectorAll('#request-table-body tr'));
                const resultCount = document.getElementById('request-result-count');
                const noResults = document.getElementById('request-no-results');

                const normalize = (value) => value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLocaleLowerCase('id')
                    .trim();

                const filterRequests = () => {
                    const query = normalize(searchInput.value);
                    const selectedStatus = statusFilter.value;
                    let visibleCount = 0;

                    rows.forEach((row) => {
                        const matchesSearch = normalize(row.dataset.search || '').includes(query);
                        const matchesStatus = !selectedStatus || row.dataset.status === selectedStatus;
                        const isVisible = matchesSearch && matchesStatus;

                        row.hidden = !isVisible;
                        visibleCount += Number(isVisible);
                    });

                    resultCount.textContent = `Menampilkan ${visibleCount} dari ${rows.length} permintaan di halaman ini`;
                    noResults.hidden = visibleCount !== 0;
                };

                searchInput.addEventListener('input', filterRequests);
                statusFilter.addEventListener('change', filterRequests);
            })();
        </script>
    @endif
@endsection
