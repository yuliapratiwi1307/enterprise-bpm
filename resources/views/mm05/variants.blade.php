@extends('layouts.app')
@section('title', 'Varian Material | Enterprise BPM')
@section('content')
<style>
.mm-page{max-width:1400px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:1.3fr 1fr 1.2fr 1fr 2fr auto;gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select,.mm-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}.mm-filter{display:grid;grid-template-columns:minmax(260px,2fr) minmax(160px,1fr) minmax(180px,1fr) auto auto;gap:10px;align-items:end}.mm-variant{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:14px}.mm-variant-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}.mm-variant-head h2{margin:0;font-size:17px;color:#1e3a5f}.mm-spec-values{margin-top:14px;padding:12px 14px;background:#f8fafc;border-radius:10px}.mm-spec-values h3{margin:0 0 8px;font-size:13px;color:#334155}.mm-spec-values ul{margin:0;padding-left:18px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px 20px}.mm-rowform{display:grid;grid-template-columns:1.2fr 1fr 1.2fr 1fr 2fr auto;gap:8px;align-items:end}.mm-spec-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:10px;align-items:end;margin-top:14px}.mm-spec-form input,.mm-spec-form select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.mm-note{font-size:12px;color:#64748b;margin:12px 0 0}.mm-pagination{padding-top:14px}@media(max-width:900px){.mm-page{padding:16px}.mm-form,.mm-rowform,.mm-filter{grid-template-columns:1fr 1fr}.mm-spec-form{grid-template-columns:1fr 1fr}.mm-spec-values ul{grid-template-columns:1fr 1fr}}@media(max-width:560px){.mm-form,.mm-rowform,.mm-filter,.mm-spec-form,.mm-spec-values ul{grid-template-columns:1fr}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Varian Material</h1><p class="mm-muted">Cari material atau varian berdasarkan kode, nama, SKU, dan nilai spesifikasi.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    <nav class="mm-nav" aria-label="Master Material">
        <a href="{{ route('mm05.materials.index') }}">Material</a>
        <a href="{{ route('mm05.categories.index') }}">Kategori</a>
        <a href="{{ route('mm05.units.index') }}">Satuan</a>
        <a href="{{ route('mm05.specifications.index') }}">Spesifikasi</a>
        <a class="active" href="{{ route('mm05.variants.index') }}">Varian</a>
        <a href="{{ route('mm05.quotes.index') }}">Penawaran Harga</a>
    </nav>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())
        <div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="mm-panel">
        <form class="mm-filter" method="GET" action="{{ route('mm05.variants.index') }}">
            <div class="mm-field"><label for="q">Cari material, SKU, varian, atau spesifikasi</label><input id="q" name="q" type="search" maxlength="120" value="{{ request('q') }}" placeholder="Kode/nama material, SKU, nama varian, nilai spesifikasi"></div>
            <div class="mm-field"><label for="category_id">Kategori</label><select id="category_id" name="category_id"><option value="">Semua kategori aktif</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="mm-field"><label for="material_id">Material</label><select id="material_id" name="material_id"><option value="">Semua material aktif</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(request('material_id') == $material->id)>{{ $material->code }} — {{ $material->name }}</option>@endforeach</select></div>
            <button class="mm-btn" type="submit">Cari</button>
            <a class="mm-btn secondary" href="{{ route('mm05.variants.index') }}">Reset</a>
        </form>
    </section>

    @if($canManage)
        <section class="mm-panel">
            <h2 style="font-size:16px;margin:0 0 14px">Tambah Varian</h2>
            <form class="mm-form" method="POST" action="{{ route('mm05.variants.store') }}">
                @csrf
                <div class="mm-field"><label for="new-material">Material induk</label><select id="new-material" name="material_id" required><option value="">Pilih material aktif</option>@foreach($materials as $material)<option value="{{ $material->id }}" @selected(old('material_id') == $material->id)>{{ $material->code }} — {{ $material->name }} ({{ $material->category?->name }})</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-sku">SKU / kode varian</label><input id="new-sku" name="sku" maxlength="90" value="{{ old('sku') }}" required></div>
                <div class="mm-field"><label for="new-variant-name">Nama varian</label><input id="new-variant-name" name="variant_name" maxlength="255" value="{{ old('variant_name') }}" required></div>
                <div class="mm-field"><label for="new-base-unit">Satuan dasar</label><select id="new-base-unit" name="base_unit_id" required><option value="">Pilih satuan aktif</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('base_unit_id') == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-note">Catatan spesifikasi</label><input id="new-note" name="specification_note" value="{{ old('specification_note') }}"></div>
                <button class="mm-btn" type="submit">Simpan</button>
            </form>
            @if($materials->isEmpty())<p class="mm-note">Belum ada material aktif. Tambahkan atau aktifkan material terlebih dahulu.</p>@endif
            @if($units->isEmpty())<p class="mm-note">Belum ada satuan aktif. Tambahkan atau aktifkan satuan terlebih dahulu.</p>@endif
        </section>
    @endif

    <section class="mm-panel">
        <div class="mm-head"><h2 style="font-size:16px;margin:0">{{ $canManage ? 'Daftar Varian (termasuk nonaktif)' : 'Katalog Material Aktif' }}</h2><span class="mm-muted">{{ $variants->total() }} data</span></div>
        @forelse($variants as $variant)
            <article class="mm-variant">
                <div class="mm-variant-head">
                    <div><h2>{{ $variant->sku }} — {{ $variant->variant_name }}</h2><p class="mm-note">{{ $variant->material?->code }} — {{ $variant->material?->name }} · Kategori: {{ $variant->material?->category?->name ?? '—' }} · Satuan dasar: {{ $variant->baseUnit?->code ?? '—' }}</p></div>
                    <span class="mm-status {{ $variant->is_active ? 'on' : 'off' }}">{{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>

                @if($variant->attributes->isNotEmpty())
                    <div class="mm-spec-values">
                        <h3>Spesifikasi tersimpan</h3>
                        <ul>
                            @foreach($variant->attributes as $value)
                                <li>
                                    <strong>{{ $value->definition?->label ?? $value->definition?->code ?? 'Atribut' }}:</strong>
                                    {{ $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? '—' : ($value->value_boolean ? 'Ya' : 'Tidak')) }}
                                    @if($value->definition?->unit)<span class="mm-muted">({{ $value->definition->unit->code }})</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                @endif

                @php($referencePriceGroups = $referencePricesByVariant->get($variant->id, collect()))
                <section class="mm-spec-values" aria-label="Harga referensi supplier">
                    <h3>Harga Referensi Supplier</h3>
                    @forelse($referencePriceGroups as $prices)
                        @php($groupPrice = $prices->first())
                        <div class="mm-price-group">
                            <h4 class="mm-note">
                                {{ $groupPrice->currency }} / {{ $groupPrice->priceUnit->code }} — Tersedia
                            </h4>
                            @foreach($prices as $price)
                                <p class="mm-note">
                                    {{ $price->sourceQuote->supplier->code }} — {{ $price->sourceQuote->supplier->name }}:
                                    {{ $price->currency }} {{ number_format((float) $price->amount, 4, ',', '.') }}
                                    / {{ $price->priceUnit->code }} — {{ $price->priceUnit->name }}
                                    · Efektif mulai {{ $price->effective_from->format('d/m/Y') }}
                                    · Efektif sampai {{ $price->effective_until?->format('d/m/Y') ?? 'Tanpa batas akhir' }}
                                </p>
                            @endforeach
                        </div>
                    @empty
                        <p class="mm-note">Harga belum tersedia</p>
                    @endforelse
                </section>

                @if($canManage && $variant->is_active)
                    <form class="mm-rowform" method="POST" action="{{ route('mm05.variants.update', $variant) }}">
                        @csrf @method('PUT')
                        <div class="mm-field"><label>Material induk</label><select name="material_id" required>@if($variant->material && ! $variant->material->is_active)<option value="{{ $variant->material_id }}" selected>{{ $variant->material->code }} — {{ $variant->material->name }} (nonaktif; dipertahankan)</option>@endif @foreach($materials as $material)<option value="{{ $material->id }}" @selected($variant->material_id == $material->id)>{{ $material->code }} — {{ $material->name }}</option>@endforeach</select></div>
                        <div class="mm-field"><label>SKU</label><input name="sku" maxlength="90" value="{{ $variant->sku }}" required></div>
                        <div class="mm-field"><label>Nama varian</label><input name="variant_name" maxlength="255" value="{{ $variant->variant_name }}" required></div>
                        <div class="mm-field"><label>Satuan dasar</label><select name="base_unit_id" required>@if($variant->baseUnit && ! $variant->baseUnit->is_active)<option value="{{ $variant->base_unit_id }}" selected>{{ $variant->baseUnit->code }} — {{ $variant->baseUnit->name }} (nonaktif; dipertahankan)</option>@endif @foreach($units as $unit)<option value="{{ $unit->id }}" @selected($variant->base_unit_id == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach</select></div>
                        <div class="mm-field"><label>Catatan spesifikasi</label><input name="specification_note" value="{{ $variant->specification_note }}"></div>
                        <button class="mm-btn secondary" type="submit">Update</button>
                    </form>
                    <div class="mm-actions">
                        <form method="POST" action="{{ route('mm05.variants.deactivate', $variant) }}" onsubmit="return confirm('Nonaktifkan varian ini? Data dan nilai spesifikasi tetap tersimpan sebagai referensi.');">
                            @csrf<button class="mm-btn danger" type="submit">Nonaktifkan</button>
                        </form>
                    </div>

                    @if($variant->material?->is_active)
                        @php($categoryDefinitions = $definitionsByCategory->get($variant->material?->category_id, collect()))
                        @if($categoryDefinitions->isNotEmpty())
                            <form class="mm-spec-form" method="POST" action="{{ route('mm05.variants.attributes.update', $variant) }}">
                                @csrf @method('PUT')
                                @foreach($categoryDefinitions as $definition)
                                    @php($currentValue = $variant->attributes->firstWhere('attribute_definition_id', $definition->id))
                                    <input type="hidden" name="attributes[{{ $loop->index }}][attribute_definition_id]" value="{{ $definition->id }}">
                                    <div class="mm-field">
                                        <label for="variant-{{ $variant->id }}-attribute-{{ $definition->id }}">{{ $definition->label }}@if($definition->is_required) * @endif @if($definition->unit) ({{ $definition->unit->code }}) @endif</label>
                                        @if($definition->data_type === 'number')
                                            <input id="variant-{{ $variant->id }}-attribute-{{ $definition->id }}" type="number" step="any" name="attributes[{{ $loop->index }}][value_number]" value="{{ old('attributes.'.$loop->index.'.value_number', $currentValue?->value_number) }}" @required($definition->is_required)>
                                        @elseif($definition->data_type === 'boolean')
                                            <select id="variant-{{ $variant->id }}-attribute-{{ $definition->id }}" name="attributes[{{ $loop->index }}][value_boolean]" @required($definition->is_required)>
                                                <option value="">Belum diisi</option>
                                                <option value="1" @selected(old('attributes.'.$loop->index.'.value_boolean', $currentValue?->value_boolean === null ? '' : ($currentValue->value_boolean ? '1' : '0')) === '1')>Ya</option>
                                                <option value="0" @selected(old('attributes.'.$loop->index.'.value_boolean', $currentValue?->value_boolean === null ? '' : ($currentValue->value_boolean ? '1' : '0')) === '0')>Tidak</option>
                                            </select>
                                        @else
                                            <input id="variant-{{ $variant->id }}-attribute-{{ $definition->id }}" name="attributes[{{ $loop->index }}][value_text]" maxlength="500" value="{{ old('attributes.'.$loop->index.'.value_text', $currentValue?->value_text) }}" @required($definition->is_required)>
                                        @endif
                                    </div>
                                @endforeach
                                <button class="mm-btn" type="submit">Simpan Nilai Spesifikasi</button>
                            </form>
                        @else
                            <p class="mm-note">Belum ada definisi atribut aktif untuk kategori material ini.</p>
                        @endif
                    @else
                        <p class="mm-note">Material induk nonaktif; perubahan nilai spesifikasi ditangguhkan.</p>
                    @endif
                @elseif(! $canManage)
                    <p class="mm-note">Katalog hanya dapat dilihat.</p>
                @else
                    <p class="mm-note">Varian nonaktif — tersimpan sebagai referensi dan tidak dapat diubah.</p>
                @endif
            </article>
        @empty
            <p class="mm-note">Belum ada varian material.</p>
        @endforelse
        <div class="mm-pagination">{{ $variants->links() }}</div>
    </section>
</div>
@endsection
