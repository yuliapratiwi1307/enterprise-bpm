@extends('layouts.app')
@section('title', 'Referensi Harga Material | Enterprise BPM')
@section('content')
<style>
.mm-page{max-width:1450px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select,.mm-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-field textarea{min-height:40px;resize:vertical}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:1050px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:top}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-note{font-size:12px;color:#64748b;margin:8px 0 0}@media(max-width:850px){.mm-page{padding:16px}.mm-form{grid-template-columns:1fr 1fr}}@media(max-width:560px){.mm-form{grid-template-columns:1fr}}
</style>
<div class="mm-page">
    <div class="mm-head">
        <div><h1>Referensi Harga Material</h1><p class="mm-muted">Kelola harga material per supplier. Setiap perubahan menyimpan harga sebelumnya sebagai riwayat.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    <nav class="mm-nav" aria-label="Master Material">
        <a href="{{ route('mm05.materials.index') }}">Material</a>
        <a href="{{ route('mm05.suppliers.index') }}">Supplier</a>
        <a class="active" href="{{ route('mm05.prices.index') }}">Referensi Harga</a>
    </nav>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($canManage)
    <section class="mm-panel">
        <h2 style="font-size:16px;margin:0 0 14px">Catat Harga Supplier</h2>
        @if($eligibleQuotes->isEmpty())
            <p class="mm-muted">Belum ada penawaran yang aktif, disetujui, belum kedaluwarsa, dan berasal dari supplier aktif.</p>
        @else
        <form class="mm-form" method="POST" action="{{ route('mm05.prices.store') }}">
            @csrf
            <div class="mm-field">
                <label for="source_quote_id">Penawaran supplier</label>
                <select id="source_quote_id" name="source_quote_id" required>
                    <option value="">Pilih penawaran yang disetujui</option>
                    @foreach($eligibleQuotes as $quote)
                    <option value="{{ $quote->id }}" @selected(old('source_quote_id') == $quote->id)>
                        {{ $quote->variant->material->code }} / {{ $quote->variant->sku }}
                        — {{ $quote->supplier->name }}
                        — {{ $quote->currency }} {{ number_format((float) $quote->amount, 4, ',', '.') }}
                        — {{ $quote->quoted_on->format('d/m/Y') }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mm-field"><label for="effective_from">Mulai berlaku</label><input id="effective_from" name="effective_from" type="date" value="{{ old('effective_from', today()->toDateString()) }}" required></div>
            <div class="mm-field"><label for="effective_until">Akhir berlaku (opsional)</label><input id="effective_until" name="effective_until" type="date" value="{{ old('effective_until') }}"></div>
            <div class="mm-field"><label for="change_reason">Alasan / catatan</label><input id="change_reason" name="change_reason" maxlength="2000" value="{{ old('change_reason') }}"></div>
            <button class="mm-btn" type="submit">Catat Harga</button>
        </form>
        <p class="mm-note">Nominal, mata uang, varian, satuan, dan supplier diambil langsung dari penawaran terkunci; input browser tidak dapat menggantinya.</p>
        @endif
    </section>
    @endif

    @if($canManage)
    <section class="mm-panel">
        <h2 style="font-size:16px;margin:0 0 8px">Input / Perbarui harga supplier</h2>
        <p class="mm-note">Pilih material dan supplier. Jika satu material memiliki beberapa varian, pilih varian secara eksplisit. Faktor konversi boleh dikosongkan bila belum diketahui; HPP tidak akan menormalisasi harga tersebut.</p>
        <form method="POST" action="{{ route('mm05.prices.direct.store') }}" class="mm-form" style="grid-template-columns:repeat(3,minmax(0,1fr))">
            @csrf
            <div class="mm-field"><label for="direct_material_id">Material</label>
                <select id="direct_material_id" name="material_id" required>
                    <option value="">Pilih material</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}" @selected(old('material_id') == $material->id)>{{ $material->code }} — {{ $material->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($hasMultipleVariants)
            <div class="mm-field"><label for="direct_variant_id">Varian</label>
                <select id="direct_variant_id" name="material_variant_id">
                    <option value="">Pilih varian material</option>
                    @foreach($variants as $variant)
                        <option value="{{ $variant->id }}" data-material="{{ $variant->material_id }}" @selected(old('material_variant_id') == $variant->id)>{{ $variant->material->code }} / {{ $variant->sku }} — {{ $variant->variant_name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="mm-field"><label for="direct_supplier_id">Supplier</label>
                <select id="direct_supplier_id" name="supplier_id" required><option value="">Pilih supplier</option>
                    @foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach
                </select>
            </div>
            <div class="mm-field"><label for="direct_price_unit_id">Satuan harga</label>
                <select id="direct_price_unit_id" name="price_unit_id" required><option value="">Pilih satuan</option>
                    @foreach($units as $unit)<option value="{{ $unit->id }}" data-usage="{{ $unit->usage_group }}" @selected(old('price_unit_id') == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach
                </select>
            </div>
            <div class="mm-field"><label for="direct_conversion_factor">Faktor konversi ke satuan dasar</label><input id="direct_conversion_factor" name="conversion_factor" type="number" min="0.00000001" step="any" value="{{ old('conversion_factor') }}" placeholder="Contoh: 100"></div>
            <div class="mm-field"><label for="direct_amount">Harga</label><input id="direct_amount" name="amount" type="number" min="0.0001" step="any" value="{{ old('amount') }}" required></div>
            <div class="mm-field"><label for="direct_currency">Mata uang</label><input id="direct_currency" name="currency" maxlength="3" value="{{ old('currency', 'IDR') }}" required></div>
            <div class="mm-field"><label for="direct_effective_from">Mulai berlaku</label><input id="direct_effective_from" name="effective_from" type="date" value="{{ old('effective_from', today()->toDateString()) }}" required></div>
            <div class="mm-field"><label for="direct_communication_method">Konfirmasi melalui</label><select id="direct_communication_method" name="communication_method" required><option value="WhatsApp">WhatsApp</option><option value="Email">Email</option><option value="Telepon">Telepon</option></select></div>
            <div class="mm-field"><label for="direct_communication_note">Catatan</label><input id="direct_communication_note" name="communication_note" maxlength="1800" value="{{ old('communication_note') }}"></div>
            <div><button class="mm-btn" type="submit">Simpan harga</button></div>
        </form>
    </section>
    @endif

    <section class="mm-panel">
        <form method="GET" action="{{ route('mm05.prices.index') }}" class="mm-form">
            <div class="mm-field"><label for="q">Cari material, SKU, atau supplier</label><input id="q" name="q" type="search" maxlength="120" value="{{ request('q') }}"></div>
            <div class="mm-field"><label for="currency">Mata uang</label><input id="currency" name="currency" maxlength="3" value="{{ request('currency') }}"></div>
            <button class="mm-btn" type="submit">Cari</button>
            <a class="mm-btn secondary" href="{{ route('mm05.prices.index') }}">Reset</a>
        </form>
    </section>

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><div><h2 style="font-size:16px;margin:0">Harga terbaru</h2><p class="mm-muted" style="margin:4px 0 0">Riwayat harga tersimpan untuk setiap material dan supplier.</p></div><span class="mm-muted">{{ $records->total() }} data</span></div>
        <div class="mm-table-wrap">
            <table class="mm-table">
                <thead><tr><th>Berlaku</th><th>Material / SKU</th><th>Kategori</th><th>Supplier / Referensi</th><th>Harga</th><th>Masa berlaku</th><th>Dicatat</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td>{{ $record->effective_from->format('d/m/Y') }}</td>
                        <td>{{ $record->variant->material->code }} — {{ $record->variant->material->name }}<br><strong>{{ $record->variant->sku }}</strong> — {{ $record->variant->variant_name }}</td>
                        <td>{{ $record->variant->material->category?->name ?? '—' }}</td>
                        <td>{{ $record->supplier?->name ?? $record->sourceQuote?->supplier?->name ?? $record->source_name ?? '—' }}<br><span class="mm-muted">{{ $record->sourceQuote?->supplier_reference ?? 'Tanpa referensi' }}</span></td>
                        <td><strong>{{ $record->currency }} {{ number_format((float) $record->amount, 4, ',', '.') }}</strong><br><span class="mm-muted">per {{ $record->priceUnit->code }} — {{ $record->priceUnit->name }}</span></td>
                        <td>{{ $record->effective_until?->format('d/m/Y') ?? 'Tanpa batas' }}</td>
                        <td>{{ $record->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $record->change_reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;padding:26px;color:#64748b">Belum ada histori referensi harga.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding-top:14px">{{ $records->links() }}</div>
    </section>
</div>
<script>
(() => {
  const material = document.getElementById('direct_material_id');
  const variant = document.getElementById('direct_variant_id');
  if (!material || !variant) return;
  const filter = () => {
    const id = material.value;
    [...variant.options].forEach((option, index) => {
      if (index === 0) return;
      option.hidden = !!id && option.dataset.material !== id;
      if (option.hidden && option.selected) variant.value = '';
    });
  };
  material.addEventListener('change', filter);
  filter();
})();
</script>
@endsection
