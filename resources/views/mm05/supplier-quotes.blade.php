@extends('layouts.app')
@section('title', 'Penawaran Harga Supplier | Enterprise BPM')
@section('content')
<style>
.mm-page{max-width:1450px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select,.mm-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-field textarea{min-height:40px;resize:vertical}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}.mm-filter{display:grid;grid-template-columns:minmax(220px,2fr) repeat(3,minmax(150px,1fr)) auto auto;gap:10px;align-items:end}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:1300px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:top}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-status.expired{background:#fff4df;color:#8a5b00}.mm-rowform{display:grid;grid-template-columns:repeat(3,minmax(110px,1fr)) auto;gap:7px;align-items:end}.mm-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.mm-actions input,.mm-actions textarea{border:1px solid #cbd5e1;border-radius:8px;padding:8px;font:inherit;font-size:12px}.mm-actions textarea{min-width:180px;min-height:40px}.mm-pagination{padding-top:14px}.mm-note{font-size:12px;color:#64748b;margin:8px 0 0}@media(max-width:950px){.mm-page{padding:16px}.mm-form,.mm-filter{grid-template-columns:1fr 1fr}.mm-rowform{grid-template-columns:1fr 1fr}}@media(max-width:560px){.mm-form,.mm-filter,.mm-rowform{grid-template-columns:1fr}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Penawaran Harga Supplier</h1><p class="mm-muted">Catat penawaran per varian material. Data baru menambah histori dan tidak menggantikan penawaran sebelumnya.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    <nav class="mm-nav" aria-label="Master Material">
        <a href="{{ route('mm05.materials.index') }}">Material</a>
        <a href="{{ route('mm05.categories.index') }}">Kategori</a>
        <a href="{{ route('mm05.units.index') }}">Satuan</a>
        <a href="{{ route('mm05.specifications.index') }}">Spesifikasi</a>
        <a href="{{ route('mm05.variants.index') }}">Varian</a>
        <a href="{{ route('mm05.suppliers.index') }}">Supplier</a>
        <a class="active" href="{{ route('mm05.quotes.index') }}">Penawaran Harga</a>
        <a href="{{ route('mm05.prices.index') }}">Referensi Harga</a>
    </nav>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($canManage)
        <section class="mm-panel">
            <h2 style="font-size:16px;margin:0 0 14px">Tambah Penawaran</h2>
            <form class="mm-form" method="POST" action="{{ route('mm05.quotes.store') }}">
                @csrf
                <div class="mm-field"><label for="new-variant">Varian / SKU</label><select id="new-variant" name="material_variant_id" required><option value="">Pilih varian aktif</option>@foreach($variants as $variant)<option value="{{ $variant->id }}" @selected(old('material_variant_id') == $variant->id)>{{ $variant->material->code }} — {{ $variant->material->name }} · {{ $variant->sku }} — {{ $variant->variant_name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-supplier">Supplier aktif</label><select id="new-supplier" name="supplier_id" required><option value="">Pilih supplier aktif</option>@foreach($activeSuppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-amount">Harga</label><input id="new-amount" name="amount" type="number" min="0.0001" step="0.0001" value="{{ old('amount') }}" required></div>
                <div class="mm-field"><label for="new-unit">Satuan harga</label><select id="new-unit" name="price_unit_id" required><option value="">Pilih satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('price_unit_id') == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-currency">Mata uang</label><input id="new-currency" name="currency" maxlength="3" value="{{ old('currency', 'IDR') }}" required></div>
                <div class="mm-field"><label for="new-quoted-on">Tanggal penawaran</label><input id="new-quoted-on" name="quoted_on" type="date" value="{{ old('quoted_on', today()->toDateString()) }}" required></div>
                <div class="mm-field"><label for="new-valid-until">Berlaku sampai</label><input id="new-valid-until" name="valid_until" type="date" value="{{ old('valid_until') }}"></div>
                <div class="mm-field"><label for="new-reference">Referensi supplier</label><input id="new-reference" name="supplier_reference" maxlength="120" value="{{ old('supplier_reference') }}"></div>
                <div class="mm-field"><label for="new-document">Path dokumen sumber</label><input id="new-document" name="source_document_path" maxlength="500" value="{{ old('source_document_path') }}"></div>
                <div class="mm-field"><label for="new-notes">Catatan</label><textarea id="new-notes" name="notes">{{ old('notes') }}</textarea></div>
                <button class="mm-btn" type="submit">Simpan Penawaran</button>
            </form>
            @if($variants->isEmpty() || $activeSuppliers->isEmpty() || $units->isEmpty())
                <p class="mm-note">Penawaran memerlukan varian, supplier, dan satuan yang aktif.</p>
            @endif
        </section>
    @endif

    <section class="mm-panel">
        <form class="mm-filter" method="GET" action="{{ route('mm05.quotes.index') }}">
            <div class="mm-field"><label for="q">Cari material, SKU, supplier, atau referensi</label><input id="q" name="q" type="search" maxlength="120" value="{{ request('q') }}"></div>
            <div class="mm-field"><label for="material_id">Material</label><select id="material_id" name="material_id"><option value="">Semua material</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(request('material_id') == $material->id)>{{ $material->code }} — {{ $material->name }}</option>@endforeach</select></div>
            <div class="mm-field"><label for="supplier_id">Supplier</label><select id="supplier_id" name="supplier_id"><option value="">Semua supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>@endforeach</select></div>
            <div class="mm-field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua status</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option><option value="expired" @selected(request('status') === 'expired')>Kedaluwarsa</option></select></div>
            <button class="mm-btn" type="submit">Terapkan</button>
            <a class="mm-btn secondary" href="{{ route('mm05.quotes.index') }}">Reset</a>
        </form>
    </section>

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><h2 style="font-size:16px;margin:0">Daftar Penawaran</h2><span class="mm-muted">{{ $records->total() }} data</span></div>
        <div class="mm-table-wrap">
            <table class="mm-table">
                <thead><tr><th>Tanggal / Supplier</th><th>Material / Varian</th><th>Harga</th><th>Masa berlaku</th><th>Status</th><th>Persetujuan</th><th>Edit / Aksi</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    @php($expired = $record->valid_until !== null && $record->valid_until->lt(today()))
                    <tr>
                        <td><strong>{{ $record->supplier->code }} — {{ $record->supplier->name }}</strong><br>{{ $record->quoted_on->format('d/m/Y') }}<br><span class="mm-muted">{{ $record->supplier_reference ?: 'Tanpa nomor referensi' }}</span></td>
                        <td>{{ $record->variant->material->code }} — {{ $record->variant->material->name }}<br><strong>{{ $record->variant->sku }}</strong> — {{ $record->variant->variant_name }}</td>
                        <td>{{ $record->currency }} {{ number_format((float) $record->amount, 4, ',', '.') }} / {{ $record->priceUnit->code }} — {{ $record->priceUnit->name }}</td>
                        <td>{{ $record->valid_until?->format('d/m/Y') ?? 'Tanpa batas waktu' }}</td>
                        <td>
                            @if(! $record->is_active)
                                <span class="mm-status off">Nonaktif</span>
                            @elseif($expired)
                                <span class="mm-status expired">Kedaluwarsa</span>
                            @else
                                <span class="mm-status on">Aktif</span>
                            @endif
                        </td>
                        <td>
                            @if($record->approval_status === \App\Models\MmSupplierQuote::STATUS_APPROVED)
                                <span class="mm-status on">Disetujui</span><br>
                                <span class="mm-muted">{{ $record->approvedBy?->name ?? 'Pengguna tidak tersedia' }} · {{ $record->approved_at?->format('d/m/Y H:i') }}</span>
                            @elseif($record->approval_status === \App\Models\MmSupplierQuote::STATUS_REJECTED)
                                <span class="mm-status off">Ditolak</span><br>
                                <span class="mm-muted">{{ $record->rejectedBy?->name ?? 'Pengguna tidak tersedia' }} · {{ $record->rejected_at?->format('d/m/Y H:i') }}</span>
                                @if($record->rejection_reason)<p class="mm-note">{{ $record->rejection_reason }}</p>@endif
                            @else
                                <span class="mm-status expired">Menunggu persetujuan</span>
                            @endif
                        </td>
                        <td>
                            @if($canManage)
                                @if($record->approval_status === \App\Models\MmSupplierQuote::STATUS_PENDING)
                                    <form class="mm-rowform" method="POST" action="{{ route('mm05.quotes.update', $record) }}">
                                        @csrf @method('PUT')
                                        <div class="mm-field"><label>Satuan harga</label><select name="price_unit_id" required>@if(! $units->contains('id', $record->price_unit_id))<option value="{{ $record->price_unit_id }}" selected>{{ $record->priceUnit->code }} (nonaktif; dipertahankan)</option>@endif @foreach($units as $unit)<option value="{{ $unit->id }}" @selected($record->price_unit_id == $unit->id)>{{ $unit->code }}</option>@endforeach</select></div>
                                        <div class="mm-field"><label>Harga</label><input name="amount" type="number" min="0.0001" step="0.0001" value="{{ $record->amount }}" required></div>
                                        <div class="mm-field"><label>Mata uang</label><input name="currency" maxlength="3" value="{{ $record->currency }}" required></div>
                                        <div class="mm-field"><label>Tanggal penawaran</label><input name="quoted_on" type="date" value="{{ $record->quoted_on->toDateString() }}" required></div>
                                        <div class="mm-field"><label>Berlaku sampai</label><input name="valid_until" type="date" value="{{ $record->valid_until?->toDateString() }}"></div>
                                        <div class="mm-field"><label>Referensi supplier</label><input name="supplier_reference" maxlength="120" value="{{ $record->supplier_reference }}"></div>
                                        <div class="mm-field"><label>Path dokumen</label><input name="source_document_path" maxlength="500" value="{{ $record->source_document_path }}"></div>
                                        <div class="mm-field"><label>Catatan</label><textarea name="notes">{{ $record->notes }}</textarea></div>
                                        <button class="mm-btn secondary" type="submit">Update</button>
                                    </form>
                                @else
                                    <span class="mm-muted">Penawaran yang sudah diputuskan tidak dapat diedit.</span>
                                @endif
                                @if($record->is_active)
                                    <form class="mm-actions" method="POST" action="{{ route('mm05.quotes.deactivate', $record) }}" onsubmit="return confirm('Nonaktifkan penawaran? Histori tetap tersimpan.');">
                                        @csrf<button class="mm-btn danger" type="submit">Nonaktifkan</button>
                                    </form>
                                @else
                                    <form class="mm-actions" method="POST" action="{{ route('mm05.quotes.activate', $record) }}">
                                        @csrf<button class="mm-btn secondary" type="submit">Aktifkan</button>
                                    </form>
                                @endif
                            @endif
                            @if($canApproveQuotes && $record->approval_status === \App\Models\MmSupplierQuote::STATUS_PENDING && $record->is_active)
                                <form class="mm-actions" method="POST" action="{{ route('mm05.quotes.approve', $record) }}">
                                    @csrf
                                    <input name="reason" maxlength="2000" placeholder="Catatan persetujuan (opsional)" aria-label="Catatan persetujuan">
                                    <button class="mm-btn" type="submit">Setujui</button>
                                </form>
                                <form class="mm-actions" method="POST" action="{{ route('mm05.quotes.reject', $record) }}">
                                    @csrf
                                    <textarea name="reason" maxlength="2000" required placeholder="Alasan penolakan" aria-label="Alasan penolakan"></textarea>
                                    <button class="mm-btn danger" type="submit">Tolak</button>
                                </form>
                            @elseif($canApproveQuotes && $record->approval_status === \App\Models\MmSupplierQuote::STATUS_PENDING)
                                <span class="mm-muted">Penawaran nonaktif tidak dapat diputuskan.</span>
                            @elseif(! $canManage && ! $canApproveQuotes)
                                <span class="mm-muted">Hanya baca.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:26px;color:#64748b">Belum ada penawaran yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mm-pagination">{{ $records->links() }}</div>
    </section>
</div>
@endsection
