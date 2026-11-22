@extends('layouts.app')

@section('title', 'Data Material | Enterprise BPM')
@section('content')
<style>
.mm-page{position:relative;left:50%;transform:translateX(-50%);box-sizing:border-box;width:calc(100vw - 234px - 48px);max-width:none;margin:0;padding:24px;color:#172033}
.mm-hero-card{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;padding:24px 26px;border:1px solid #dce6f2;border-radius:16px;background:linear-gradient(120deg,#f8fbff 0%,#eef4fc 100%);box-shadow:0 5px 18px rgba(15,35,65,.045);margin:0 0 18px}
.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;width:100%}
.mm-head h1{margin:0;font-size:27px;letter-spacing:-.025em}
.mm-muted{color:#64748b;font-size:13px}
.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:13px;padding:17px;margin-top:16px;box-shadow:0 3px 12px rgba(15,35,65,.035)}
.mm-form{display:grid;grid-template-columns:minmax(120px,.9fr) minmax(140px,1fr) minmax(150px,1fr) minmax(180px,1.7fr) auto;gap:10px;align-items:end}
.mm-field label{display:block;font-size:11px;font-weight:750;letter-spacing:.035em;text-transform:uppercase;color:#526176;margin-bottom:6px}
.mm-field input,.mm-field select,.mm-field textarea{width:100%;min-height:38px;border:1px solid #d5deea;border-radius:7px;padding:8px 10px;font:inherit;font-size:12px;background:#fff;color:#26364d}
.mm-btn{border:0;border-radius:7px;padding:10px 14px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-flex;align-items:center;justify-content:center;min-height:38px;white-space:nowrap}
.mm-btn.secondary{background:#edf2f8;color:#234367}
.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}
.mm-error{color:#b42318;font-size:12px;margin-top:4px}
.mm-filter{display:grid;grid-template-columns:minmax(280px,1fr) 190px auto auto;gap:10px;align-items:center}.mm-filter-panel{padding:0;background:transparent;border:0;box-shadow:none;margin:0 0 14px}.mm-filter .mm-field label{display:none}.mm-filter .mm-field input,.mm-filter .mm-field select{min-height:40px}.mm-filter .mm-btn{min-width:82px;min-height:40px}.mm-search-wrap{position:relative}.mm-search-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);width:17px;height:17px;color:#8290a3;pointer-events:none}.mm-search-wrap input{padding-left:39px!important}.mm-page .mm-head{margin-bottom:0}.mm-page .mm-dashboard-grid{grid-template-columns:minmax(0,1fr) 300px;gap:18px}
.mm-dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:17px;align-items:start;margin-top:16px}
.mm-main-column{min-width:0}
.mm-side-column{display:grid;gap:14px;position:sticky;top:18px}
.mm-side-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;box-shadow:0 3px 12px rgba(15,35,65,.035)}
.mm-side-title{font-size:13px;font-weight:750;color:#25364d;margin:0 0 13px}
.mm-stat{display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid #eef2f6}
.mm-stat:last-child{border-bottom:0;padding-bottom:0}
.mm-stat-icon{width:34px;height:34px;flex:0 0 34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#eef4ff;color:#315f9a}
.mm-stat-icon svg{width:17px;height:17px}
.mm-stat-label{font-size:11px;color:#64748b;line-height:1.35}
.mm-stat-value{font-size:17px;font-weight:750;color:#172b49;line-height:1.3}
.mm-side-note{font-size:12px;line-height:1.6;color:#64748b;margin:0}
.mm-side-link{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #eef2f6;color:#344b68;text-decoration:none;font-size:12px;font-weight:650}
.mm-side-link:last-child{border-bottom:0;padding-bottom:0}
.mm-table-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
.mm-table-head h2{font-size:15px;margin:0;color:#172b49}
.mm-table-wrap{overflow:auto;border:1px solid #e2e8f0;border-radius:10px;overscroll-behavior-inline:contain}
.mm-material-table{width:100%;min-width:0;border-collapse:separate;border-spacing:0;table-layout:fixed}
.mm-material-table th,.mm-material-table td{padding:13px 12px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:middle;font-size:12px;line-height:1.45}
.mm-material-table th{background:#f8fafc;color:#64748b;font-size:10px;font-weight:750;letter-spacing:.055em;text-transform:uppercase;white-space:nowrap}
.mm-material-table tbody tr:last-child td{border-bottom:0}
.mm-material-table tbody tr:hover{background:#fbfdff}
.mm-material-table .mm-code-link{color:#234b7a;text-decoration:none;font-weight:750;overflow-wrap:anywhere}
.mm-material-table .mm-code-link:hover{text-decoration:underline}
.mm-material-name{display:block;font-weight:750;color:#172b49;margin-bottom:7px;font-size:12px}
.mm-spec-grid{display:grid;grid-auto-flow:column;grid-template-rows:repeat(2,minmax(0,auto));grid-auto-columns:max-content;gap:3px 18px;max-width:100%;overflow-x:auto;padding:1px 3px 4px 0;overscroll-behavior-inline:contain}
.mm-spec-item{margin:0;white-space:nowrap;color:#526176;font-size:11px;line-height:1.5}
.mm-spec-item strong{font-weight:700;color:#34445b}
.mm-spec-empty{color:#94a3b8;font-size:11px}
.mm-status{display:inline-flex;align-items:center;padding:4px 8px;border-radius:20px;font-size:10px;font-weight:750;white-space:nowrap}
.mm-status.on{background:#e8f6ee;color:#207044}
.mm-status.off{background:#f1f3f6;color:#667085}
.mm-price-date{white-space:nowrap;color:#526176;font-size:11px}
.mm-price-value{color:#172b49;font-weight:750;white-space:nowrap;font-size:11px}
.mm-detail-action{display:inline-flex;width:32px;height:32px;align-items:center;justify-content:center;border:1px solid #dbe3ec;border-radius:8px;background:#fff;color:#234b7a;text-decoration:none;transition:background .15s,border-color .15s}
.mm-detail-action:hover{background:#edf4fb;border-color:#b8cbe0}
.mm-detail-action svg{width:16px;height:16px}
.mm-material-empty{padding:34px 18px!important;text-align:center!important;color:#64748b}
.mm-pagination{padding-top:14px}
@media(max-width:1200px){.mm-page .mm-dashboard-grid{grid-template-columns:minmax(0,1fr) 260px;gap:14px}.mm-page{padding:20px}.mm-material-table th,.mm-material-table td{padding:11px 9px}.mm-hero-card{padding:20px}}
@media(max-width:900px){.mm-page{left:auto;transform:none;width:100%;padding:16px}.mm-page .mm-dashboard-grid{grid-template-columns:1fr}.mm-side-column{position:static;grid-template-columns:repeat(2,minmax(0,1fr))}.mm-filter{grid-template-columns:minmax(0,1fr) 160px auto auto}.mm-material-table{min-width:760px}}
@media(max-width:640px){.mm-side-column{grid-template-columns:1fr}.mm-filter{grid-template-columns:1fr 1fr}.mm-filter .mm-field:first-child{grid-column:1/-1}.mm-filter .mm-btn{width:100%}.mm-panel{padding:13px}.mm-head h1{font-size:23px}.mm-hero-card{padding:17px}.mm-hero-card .mm-head>div:last-child{width:100%}.mm-hero-card .mm-btn{flex:1}}

/* MM-05: compact top spacing */
.mm-page {
    margin-top: 0 !important;
    padding-top: 12px !important;
}


/* Restored agreed MM-05 Material list layout */
.mm-page{position:relative;left:50%;transform:translateX(-50%);width:calc(100vw - 250px - 40px);max-width:none;margin:0;padding:18px 16px 28px;box-sizing:border-box}
.mm-page .mm-dashboard-grid{grid-template-columns:minmax(0,1fr);gap:0}
.mm-side-column{display:none}
.mm-material-table{min-width:980px;table-layout:fixed}
.mm-material-table th,.mm-material-table td{overflow-wrap:anywhere}
.mm-material-table th:last-child,.mm-material-table td:last-child{text-align:center}
.mm-material-name{margin-bottom:4px}
.mm-material-table .mm-code-link{font-size:11px;font-weight:500;color:#64748b}
.mm-spec-grid{grid-auto-flow:row;grid-template-rows:none;grid-template-columns:repeat(2,minmax(0,1fr));gap:3px 12px;overflow:visible}
.mm-spec-item{white-space:normal}
.mm-table-wrap{max-width:100%}
.mm-pagination{font-size:12px}
@media(max-width:900px){.mm-page{left:auto;transform:none;width:100%;padding:16px}.mm-material-table{min-width:900px}}
.mm-material-table th{white-space:normal;line-height:1.3;overflow-wrap:normal}
.mm-material-table td{overflow-wrap:break-word}
.mm-pagination{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;padding:16px 2px 4px;font-size:12px}
.mm-pagination-actions{display:flex;align-items:center;gap:8px}
.mm-pagination-actions a,.mm-pagination-actions span{display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border:1px solid #dce5f0;border-radius:7px;text-decoration:none;color:#234367;background:#fff;font-size:12px}
.mm-pagination-actions span{color:#94a3b8;background:#f8fafc}
.mm-pagination-actions a:hover{background:#edf4fb}
</style>
<div class="mm-page">
    <section class="mm-hero-card">
        <div class="mm-head">
            <div>
                <div style="font-size:10px;font-weight:800;letter-spacing:.13em;color:#52709a;text-transform:uppercase;margin-bottom:7px">Enterprise BPM · Procurement</div>
                <h1>Data Material</h1>
                <p class="mm-muted" style="margin:7px 0 0">Database material, spesifikasi, supplier, dan informasi harga terbaru.</p>
            </div>
            <div style="display:flex;gap:9px;align-items:center;flex-wrap:wrap">
                <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
                @if($canManage)<button type="button" class="mm-btn" id="openMaterialModal"><span aria-hidden="true" style="font-size:16px;line-height:1;margin-right:6px">＋</span>Tambah Material</button>@endif
            </div>
        </div>
    </section>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())
        <div class="mm-alert" style="background:#fff1f0;color:#9f3030">
            <strong>Periksa kembali data:</strong>
            <ul style="margin:6px 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @php
        $pageMaterials = $records->getCollection();
        $pageActiveCount = $pageMaterials->where('is_active', true)->count();
        $pageInactiveCount = $pageMaterials->where('is_active', false)->count();
        $pageWithPriceCount = $pageMaterials->filter(fn ($material) => $material->list_latest_price !== null)->count();
        $pageWithoutPriceCount = $pageMaterials->count() - $pageWithPriceCount;
    @endphp

    <div class="mm-dashboard-grid">
        <main class="mm-main-column">
            <section class="mm-filter-panel">
                <form class="mm-filter" method="GET" action="{{ route('mm05.materials.index') }}">
                    <div class="mm-field">
                        <label for="q">Cari material</label>
                        <div class="mm-search-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
                            <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari semua kolom material">
                        </div>
                    </div>
                    <div class="mm-field"><label for="status">Status material</label><select id="status" name="status"><option value="">Semua status</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select></div>
                    <button class="mm-btn" type="submit">Cari</button>
                    <a class="mm-btn secondary" href="{{ route('mm05.materials.index') }}">Reset</a>
                </form>
            </section>

            <section class="mm-panel" id="mm-material-results" aria-live="polite">
                <div class="mm-table-head">
                    <h2>Daftar Material</h2>
                    <span class="mm-muted">{{ $records->total() }} material</span>
                </div>
                <div class="mm-table-wrap" tabindex="0" aria-label="Daftar material, geser horizontal untuk melihat seluruh kolom">
                    <table class="mm-material-table">
                        <colgroup>
                            <col style="width:19%"><col style="width:25%"><col style="width:14%"><col style="width:8%"><col style="width:12%"><col style="width:13%"><col style="width:9%">
                        </colgroup>
                        <thead><tr><th>Nama Material</th><th>Spesifikasi Material</th><th>Supplier</th><th>Status</th><th>Update Harga</th><th>Harga</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($records as $record)
                            @php
                                $latestPrice = $record->list_latest_price;
                                $specificationVariant = $record->list_specification_variant;
                                $specifications = collect();
                                foreach ($specificationVariant?->attributes ?? collect() as $attribute) {
                                    $definition = $attribute->definition;
                                    if (! $definition) continue;
                                    $value = $attribute->value_text;
                                    if ($attribute->value_number !== null) {
                                        $value = rtrim(rtrim(number_format((float) $attribute->value_number, 6, ',', '.'), '0'), ',');
                                    } elseif ($attribute->value_boolean !== null) {
                                        $value = $attribute->value_boolean ? 'Ya' : 'Tidak';
                                    }
                                    if ($value === null || $value === '') continue;
                                    $specifications->push((object) ['label' => $definition->label, 'value' => $value, 'unit' => $definition->unit?->name, 'sort_order' => $definition->sort_order]);
                                }
                                $specifications = $specifications->sortBy(fn ($specification) => [$specification->sort_order, $specification->label])->values();
                                $visibleSpecifications = $specifications->take(4);
                                $remainingSpecificationCount = max(0, $specifications->count() - $visibleSpecifications->count());
                                $formattedAmount = $latestPrice ? rtrim(rtrim(number_format((float) $latestPrice->amount, 2, ',', '.'), '0'), ',') : null;
                            @endphp
                            <tr>
                                <td><span class="mm-material-name">{{ $record->name }}</span><a class="mm-code-link" href="{{ route('mm05.materials.show', $record->id) }}">{{ $record->code }}</a></td>
                                <td>
                                    @if($specifications->isEmpty())
                                        <span class="mm-spec-empty">Belum ada spesifikasi</span>
                                    @else
                                        <div class="mm-spec-grid" aria-label="Spesifikasi material">
                                            @foreach($visibleSpecifications as $specification)
                                                <p class="mm-spec-item"><strong>{{ $specification->label }}:</strong> {{ $specification->value }}@if($specification->unit) {{ $specification->unit }}@endif</p>
                                            @endforeach
                                            @if($remainingSpecificationCount > 0)
                                                <p class="mm-spec-item"><strong>+ {{ $remainingSpecificationCount }} lainnya</strong></p>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $latestPrice?->supplier?->name ?? '-' }}</td>
                                <td><span class="mm-status {{ $record->is_active ? 'on' : 'off' }}">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="mm-price-date">{{ $latestPrice?->effective_from?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    @if($latestPrice)
                                        <span class="mm-price-value">{{ $latestPrice->currency === 'IDR' ? 'Rp' : $latestPrice->currency.' ' }}{{ $formattedAmount }}@if($latestPrice->priceUnit) / {{ $latestPrice->priceUnit->name }}@endif</span>
                                    @else
                                        <span class="mm-muted">Belum ada harga</span>
                                    @endif
                                </td>
                                <td><div class="mm-material-actions"><a class="mm-detail-action" href="{{ route('mm05.materials.show', $record->id) }}" aria-label="Detail material: {{ $record->name }}" title="Detail material"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></a>
                                    @if($canManage)
                                    <a class="mm-detail-action" href="{{ route('mm05.materials.show', $record->id) }}#edit-identitas" title="Edit material" aria-label="Edit material: {{ $record->name }}" style="margin-left:5px"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/></svg></a>
                                    @endif
                                    @if($canManage && $record->is_active)
                                    <form method="POST" action="{{ route('mm05.materials.deactivate', $record->id) }}" style="display:inline-flex;margin-left:5px" onsubmit="return confirm('Nonaktifkan material ini? Data tidak akan dihapus.');">
                                        @csrf
                                        <button type="submit" class="mm-detail-action" title="Nonaktifkan material" aria-label="Nonaktifkan material: {{ $record->name }}" style="color:#dc2626;cursor:pointer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M10 8v8M14 8v8"/></svg></button>
                                    </form>
                                    @elseif($canManage && !$record->is_active)
                                    <form method="POST" action="{{ route('mm05.materials.activate', $record->id) }}" style="display:inline-flex;margin-left:5px" onsubmit="return confirm('Aktifkan kembali material ini?');">
                                        @csrf
                                        <button type="submit" class="mm-detail-action" title="Aktifkan material" aria-label="Aktifkan material: {{ $record->name }}" style="color:#15803d;cursor:pointer;font-size:17px;font-weight:700">&#8635;</button>
                                    </form>
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="mm-material-empty">Belum ada data material yang cocok.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <nav class="mm-pagination" aria-label="Navigasi halaman material">
                    <span class="mm-muted">Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }} material</span>
                    <div class="mm-pagination-actions">
                        @if($records->onFirstPage())<span>‹ Sebelumnya</span>@else<a href="{{ $records->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a>@endif
                        <span>Halaman {{ $records->currentPage() }} / {{ $records->lastPage() }}</span>
                        @if($records->hasMorePages())<a href="{{ $records->nextPageUrl() }}" rel="next">Berikutnya ›</a>@else<span>Berikutnya ›</span>@endif
                    </div>
                </nav>
            </section>
        </main>

        <aside class="mm-side-column" aria-label="Informasi ringkasan material">
            <section class="mm-side-card">
                <h2 class="mm-side-title">Ringkasan Material</h2>
                <div class="mm-stat">
                    <span class="mm-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5"/></svg></span>
                    <div><div class="mm-stat-label">Total material</div><div class="mm-stat-value">{{ number_format($records->total(), 0, ',', '.') }}</div></div>
                </div>
                <div class="mm-stat">
                    <span class="mm-stat-icon" style="background:#eaf7ef;color:#258052"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/></svg></span>
                    <div><div class="mm-stat-label">Aktif di halaman ini</div><div class="mm-stat-value">{{ $pageActiveCount }}</div></div>
                </div>
                <div class="mm-stat">
                    <span class="mm-stat-icon" style="background:#f1f3f6;color:#748094"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M8.5 8.5 15.5 15.5"/></svg></span>
                    <div><div class="mm-stat-label">Nonaktif di halaman ini</div><div class="mm-stat-value">{{ $pageInactiveCount }}</div></div>
                </div>
            </section>

            <section class="mm-side-card">
                <h2 class="mm-side-title">Informasi Harga</h2>
                <div class="mm-stat">
                    <span class="mm-stat-icon" style="background:#f2edff;color:#7654bd"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v18M17 7H9.5a3 3 0 0 0 0 6H14a3 3 0 0 1 0 6H6"/></svg></span>
                    <div><div class="mm-stat-label">Memiliki catatan harga</div><div class="mm-stat-value">{{ $pageWithPriceCount }}</div></div>
                </div>
                <div class="mm-stat">
                    <span class="mm-stat-icon" style="background:#fff5e8;color:#b7791f"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <div><div class="mm-stat-label">Belum memiliki harga</div><div class="mm-stat-value">{{ $pageWithoutPriceCount }}</div></div>
                </div>
                <p class="mm-side-note" style="margin-top:12px">Ringkasan harga mengikuti material yang tampil pada halaman ini. Buka detail material untuk melihat seluruh supplier dan riwayat harga.</p>
            </section>

            <section class="mm-side-card">
                <h2 class="mm-side-title">Informasi Pengelolaan</h2>
                <p class="mm-side-note">Gunakan tombol detail pada setiap baris untuk melihat spesifikasi lengkap, supplier, harga, dan riwayat material.</p>
                <a class="mm-side-link" href="{{ route('mm05.workspace') }}"><span>Kembali ke Workspace</span><span>→</span></a>
            </section>
        </aside>
    </div>
</div>

@if($canManage)
<style>
#mmMaterialModal {
    width: min(1180px, 96vw);
    height: min(900px, 94vh);
    max-width: none;
    max-height: none;
    padding: 0;
    border: 0;
    border-radius: 15px;
    overflow: hidden;
    background: white;
    box-shadow: 0 20px 70px rgba(0,0,0,.25);
}
#mmMaterialModal::backdrop {
    background: rgba(15,23,42,.62);
    backdrop-filter: blur(3px);
}
.mm-modal-header {
    height: 55px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}
.mm-modal-close {
    border: 0;
    background: #f1f5f9;
    color: #334155;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    font-size: 22px;
    cursor: pointer;
}
#mmMaterialFrame {
    width: 100%;
    height: calc(100% - 55px);
    border: 0;
    display: block;
    background: #f8fafc;
}
@media(max-width:600px) {
    #mmMaterialModal {
        width: 100vw;
        height: 100dvh;
        border-radius: 0;
    }
}
</style>

<dialog id="mmMaterialModal">
    <div class="mm-modal-header">
        <strong>Tambah Material Baru</strong>
        <button type="button" class="mm-modal-close"
                id="closeMaterialModal" aria-label="Tutup">&times;</button>
    </div>
    <iframe id="mmMaterialFrame"
        title="Form Tambah Material Baru"></iframe>
</dialog>

<script>
(function () {
    const modal = document.getElementById('mmMaterialModal');
    const open = document.getElementById('openMaterialModal');
    const close = document.getElementById('closeMaterialModal');
    const frame = document.getElementById('mmMaterialFrame');

    if (!modal || !open || !close || !frame) return;

    const formUrl = @json(route('mm05.materials.wizard.create', ['modal' => 1]));

    open.addEventListener('click', function () {
        frame.src = formUrl;
        modal.showModal();
    });

    function closeModal() {
        if (modal.open) modal.close();
        frame.src = 'about:blank';
    }

    close.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    frame.addEventListener('load', function () {
        try {
            const path = frame.contentWindow.location.pathname;

            // Setelah penyimpanan berhasil, controller mengarahkan
            // iframe ke halaman detail material.
            if (/^\/material-master\/materials\/\d+\/?$/.test(path)) {
                closeModal();
                window.location.reload();
            }
        } catch (error) {
            // Abaikan jika halaman iframe belum dapat diperiksa.
        }
    });
})();
</script>
@endif


<script>
/* MM05 AJAX LIVE SEARCH v2 */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.mm-filter-panel form.mm-filter');
    if (!form) return;
    const search = form.querySelector('input[name="q"]');
    const status = form.querySelector('select[name="status"]');
    const panelSelector = '#mm-material-results';
    if (!search || !document.querySelector(panelSelector)) return;
    let timer = null;
    let controller = null;
    let requestId = 0;
    const buildUrl = (pageUrl) => {
        const url = new URL(pageUrl || form.action || location.href, location.href);
        url.searchParams.delete('page');
        const query = search.value.trim();
        if (query) url.searchParams.set('q', query);
        else url.searchParams.delete('q');
        if (status && status.value) url.searchParams.set('status', status.value);
        else url.searchParams.delete('status');
        return url;
    };
    async function update(url, push) {
        if (controller) controller.abort();
        controller = new AbortController();
        const current = ++requestId;
        const panel = document.querySelector(panelSelector);
        panel.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url.toString(), {
                signal: controller.signal,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'},
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const html = await response.text();
            if (current !== requestId) return;
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const replacement = parsed.querySelector(panelSelector);
            if (!replacement) throw new Error('Tabel material tidak ditemukan pada respons');
            document.querySelector(panelSelector).replaceWith(replacement);
            const oldAside = document.querySelector('.mm-side-column');
            const newAside = parsed.querySelector('.mm-side-column');
            if (oldAside && newAside) oldAside.replaceWith(newAside);
            if (push) history.pushState({}, '', url);
            else history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('MM05 Live Search:', error);
                const active = document.querySelector(panelSelector);
                if (active) active.setAttribute('data-search-error', 'true');
            }
        } finally {
            if (current === requestId) {
                const active = document.querySelector(panelSelector);
                if (active) active.removeAttribute('aria-busy');
            }
        }
    }
    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(() => update(buildUrl(), false), 300);
    };
    search.addEventListener('input', schedule);
    if (status) status.addEventListener('change', () => {
        clearTimeout(timer);
        update(buildUrl(), false);
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(timer);
        update(buildUrl(), true);
    });
    document.addEventListener('click', (event) => {
        const link = event.target.closest('#mm-material-results .mm-pagination a[href]');
        if (!link) return;
        event.preventDefault();
        clearTimeout(timer);
        const url = new URL(link.href, location.href);
        update(url, true);
    });
    window.addEventListener('popstate', () => {
        const url = new URL(location.href);
        search.value = url.searchParams.get('q') || '';
        if (status) status.value = url.searchParams.get('status') || '';
        update(url, false);
    });
});
</script>


<style>
/* MM05 ACTION BUTTONS HORIZONTAL */
.mm-material-actions {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    flex-wrap: nowrap !important;
}
.mm-material-actions form {
    display: inline-flex !important;
    margin: 0 !important;
}
.mm-material-actions button,
.mm-material-actions a {
    flex-shrink: 0;
}

/* MM05 FIX: spacing card material */
@media (min-width: 901px) {
  .mm-page { margin-top: -28px !important; }
}
@media (max-width: 900px) {
  .mm-page { margin-top: 0 !important; }
}
</style>

@endsection
