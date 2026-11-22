@extends('layouts.app')

@section('title', $record->name.' | Detail Material')
@section('content')
<style>
.mmd-page{max-width:1180px;margin:0 auto;padding:28px;color:#172033}.mmd-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}.mmd-title{margin:0;font-size:26px}.mmd-muted{color:#64748b;font-size:13px}.mmd-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:19px;margin-top:16px}.mmd-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.mmd-item{padding:11px;background:#f8fafc;border-radius:9px}.mmd-label{font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase}.mmd-value{font-size:14px;font-weight:600;margin-top:4px}.mmd-table{width:100%;border-collapse:collapse}.mmd-table th,.mmd-table td{padding:10px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:top}.mmd-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mmd-btn{display:inline-block;padding:9px 13px;border-radius:8px;background:#1e3a5f;color:#fff;text-decoration:none;font-weight:700;font-size:13px}.mmd-btn.secondary{background:#e8eef5;color:#1e3a5f}.mmd-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700;background:#e8f6ee;color:#207044}.mmd-status.off{background:#f1f3f6;color:#667085}.mmd-section-title{font-size:16px;margin:0 0 13px}.mmd-empty{padding:18px;color:#64748b;font-size:13px;background:#f8fafc;border-radius:9px}.mmd-edit-form{margin-top:14px;padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}@media(max-width:650px){.mmd-page{padding:16px}.mmd-grid{grid-template-columns:1fr}.mmd-table-wrap{overflow:auto}.mmd-table{min-width:650px}}

/* MM05_COMPACT_UI_2026: tampilan lebih ringkas, tanpa perubahan penyimpanan */
.mmd-page{max-width:1100px;padding:20px 22px 40px}
.mmd-panel{padding:17px 18px;margin-top:12px}
.mmd-title{font-size:24px}
.mmd-section-title{margin-bottom:10px}
.mmd-grid{gap:9px}
.mmd-item{padding:10px 12px}
.mmd-edit-form{margin-top:10px;padding:12px}
.mmd-edit-form .mmd-item{padding:6px;background:transparent}
.mmd-edit-form input,.mmd-edit-form select,.mmd-edit-form textarea{box-sizing:border-box;max-width:100%}
.mmd-table th,.mmd-table td{padding:9px 10px}
.mmd-fold{margin-top:12px;border:1px solid #dbe4ef;border-radius:10px;background:#f8fafc;overflow:hidden}
.mmd-fold>summary{cursor:pointer;padding:12px 14px;font-weight:700;color:#1e3a5f;list-style-position:inside}
.mmd-fold[open]>summary{border-bottom:1px solid #e2e8f0}
.mmd-fold>.mmd-edit-form{border:0;border-radius:0;margin:0;background:transparent}
.mmd-legacy{margin-top:12px}
.mmd-legacy>summary{cursor:pointer;color:#475569;font-weight:700;font-size:14px;padding:10px 0}
.mmd-hint{font-size:12px;color:#64748b;margin:8px 0 0}
@media(max-width:650px){.mmd-page{padding:12px}.mmd-panel{padding:13px}.mmd-grid{grid-template-columns:1fr}.mmd-title{font-size:21px}}
/* Hidden must override display:grid on .mmd-item */
.mmd-attr-field[hidden],.mmd-price-editor[hidden]{display:none!important}

/* MM05_EDIT_UI_V2: form identitas dan harga ringkas */
.mmd-identity-form{padding:16px;margin:0;display:grid;gap:13px}
.mmd-identity-fields{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:14px}
.mmd-identity-label{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700;color:#334155;min-width:0}
.mmd-identity-label input,.mmd-identity-label select,.mmd-identity-label textarea{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:white;font:inherit;font-size:14px;color:#172033}
.mmd-identity-description{margin-top:0}
.mmd-identity-note{margin:0;font-size:12px;color:#64748b;line-height:1.5}
.mmd-identity-actions{display:flex;justify-content:flex-end;padding-top:4px;border-top:1px solid #e2e8f0}
.mmd-money-input{display:flex;align-items:center;background:#fff;border:1px solid #cbd5e1;border-radius:8px;overflow:hidden}
.mmd-money-input span{padding:10px 12px;background:#e8eef5;color:#1e3a5f;font-weight:700;border-right:1px solid #cbd5e1}
.mmd-money-input input{width:100%;min-width:0;border:0!important;outline:0;padding:10px 12px!important;font-size:15px;font-weight:650;background:transparent}
.mmd-money-input:focus-within{outline:2px solid #93c5fd;outline-offset:1px}
@media(max-width:650px){.mmd-identity-fields{grid-template-columns:1fr}.mmd-identity-actions{justify-content:stretch}.mmd-identity-actions button{width:100%}}
</style>
<div class="mmd-page">
    <div class="mmd-head">
        <div>
            <p class="mmd-muted" style="margin:0 0 5px">Database Material / Detail</p>
            <h1 class="mmd-title">{{ $record->name }}</h1>
            <p class="mmd-muted" style="margin:6px 0 0">{{ $record->code }} · {{ $record->category?->name ?? 'Tanpa kategori' }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            @if(($canManage ?? false) && !$record->is_active)
                <form method="POST" action="{{ route('mm05.materials.activate', $record->id) }}" onsubmit="return confirm('Aktifkan kembali material ini?');" style="margin:0">
                    @csrf
                    <button type="submit" class="mmd-btn" style="border:0;cursor:pointer;background:#166534">Aktifkan Material</button>
                </form>
            @endif
            <a class="mmd-btn secondary" href="{{ route('mm05.materials.index') }}">Kembali ke daftar material</a>
        </div>
    </div>

    @if(session('message'))<div class="mmd-panel" style="border-color:#b7e4c7;background:#f0fdf4">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mmd-panel" style="border-color:#fecaca;background:#fff7f7;color:#991b1b"><strong>Periksa kembali data:</strong><ul style="margin:8px 0 0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <nav aria-label="Bagian detail material" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
        <a class="mmd-btn secondary" href="#mmd-identitas">Identitas</a>
        <a class="mmd-btn secondary" href="#mmd-spesifikasi">Spesifikasi</a>
        <a class="mmd-btn secondary" href="#mmd-harga">Harga Supplier</a>
        <a class="mmd-btn secondary" href="#mmd-riwayat">Riwayat Harga</a>
    </nav>
    <section id="mmd-identitas" class="mmd-panel">
        <h2 class="mmd-section-title">Identitas Material</h2>
        <div class="mmd-grid">
            <div class="mmd-item"><div class="mmd-label">Kode Material</div><div class="mmd-value">{{ $record->code }}</div></div>
            <div class="mmd-item"><div class="mmd-label">Nama Material</div><div class="mmd-value">{{ $record->name }}</div></div>
            <div class="mmd-item"><div class="mmd-label">Kategori</div><div class="mmd-value">{{ $record->category?->name ?? '—' }}</div></div>
            <div class="mmd-item"><div class="mmd-label">Status</div><div class="mmd-value"><span class="mmd-status {{ $record->is_active ? '' : 'off' }}">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</span></div></div>
        </div>
{{-- MM05_EDIT_IDENTITAS_2026 --}}
@if($canManage)
<details id="edit-identitas" class="mmd-fold" @if($errors->has('name') || $errors->has('category_id') || $errors->has('description')) open @endif>
    <summary style="cursor:pointer;font-weight:700;color:#1e3a5f">✎ Edit Identitas Material</summary>
    <form method="POST" action="{{ route('mm05.materials.update', $record->id) }}" class="mmd-identity-form">
        @csrf
        @method('PUT')
        <div class="mmd-identity-fields">
            <label class="mmd-identity-label">Nama Material
                <input name="name" type="text" maxlength="255" required value="{{ old('name', $record->name) }}" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;background:white">
            </label>
            <label class="mmd-identity-label">Kategori
                <select name="category_id" required style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;background:white">
                    @foreach($editCategories as $editCategory)
                    <option value="{{ $editCategory->id }}" @selected((string) old('category_id', $record->category_id) === (string) $editCategory->id)>{{ $editCategory->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <label class="mmd-identity-label mmd-identity-description">Deskripsi (opsional)
            <textarea name="description" rows="2" style="width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;background:white">{{ old('description', $record->description) }}</textarea>
        </label>
        <p class="mmd-identity-note">Kode {{ $record->code }} tetap. Kategori tidak dapat diganti jika varian sudah memiliki spesifikasi.</p>
        <div class="mmd-identity-actions"><button type="submit" class="mmd-btn" style="border:0;cursor:pointer">Simpan Identitas</button></div>
    </form>
</details>
@endif

        @if($record->description)<p class="mmd-muted" style="margin:12px 0 0">{{ $record->description }}</p>@endif
    </section>

    <section id="mmd-spesifikasi" class="mmd-panel">
        <h2 class="mmd-section-title">Spesifikasi dan Varian</h2>
        @forelse($record->variants as $variant)
            <div style="padding:14px 0;border-top:1px solid #edf1f5">
                <div class="mmd-head" style="align-items:center">
                    <div><strong>{{ $variant->variant_name }}</strong><div class="mmd-muted">SKU: {{ $variant->sku }} · Satuan: {{ $variant->baseUnit?->name ?? '—' }}</div></div>
                    <span class="mmd-status {{ $variant->is_active ? '' : 'off' }}">{{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                @if($variant->specification_note)<p class="mmd-muted">{{ $variant->specification_note }}</p>@endif
                @if($variant->attributes->isNotEmpty())
                    <div class="mmd-grid" style="margin-top:10px">
                        @foreach($variant->attributes as $attribute)
                            <div class="mmd-item"><div class="mmd-label">{{ $attribute->definition?->label ?? 'Spesifikasi' }}</div><div class="mmd-value">@if(!is_null($attribute->value_text)){{ $attribute->value_text }}@elseif(!is_null($attribute->value_number)){{ $attribute->value_number }}@elseif(!is_null($attribute->value_boolean)){{ $attribute->value_boolean ? 'Ya' : 'Tidak' }}@else — @endif {{ $attribute->definition?->unit?->name }}</div></div>
                        @endforeach
                    </div>
                @elseif($definitions->isNotEmpty())
                    <p class="mmd-muted">Belum ada nilai spesifikasi untuk varian ini.</p>
                @endif

                @if($canManage && $definitions->isNotEmpty() && $variant->is_active)
                    @php($attributeValues = $variant->attributes->keyBy('attribute_definition_id'))
                    <details class="mmd-fold" @if($errors->has('attributes')) open @endif>
                        <summary>✎ Edit Spesifikasi</summary>
                    <form method="POST" action="{{ route('mm05.materials.variants.attributes.update', [$record->id, $variant->id]) }}" class="mmd-edit-form">
                        @csrf
                        @method('PUT')
                        {{-- MM05_SMART_FORMS_V1: hanya atribut tersimpan/required yang tampil --}}
                        <p class="mmd-hint">Ubah atribut yang sudah digunakan. Gunakan Tambah Atribut bila diperlukan.</p>
                        <div class="mmd-grid" id="mmd-attr-grid-{{ $variant->id }}">
                            @foreach($definitions as $index => $definition)
                                @php($saved = $attributeValues->get($definition->id))
                                @php($initiallyVisible = $saved !== null || $definition->is_required)
                                <div class="mmd-item mmd-attr-field" data-attr-id="{{ $definition->id }}" data-attr-label="{{ $definition->label }}" @if(!$initiallyVisible) hidden @endif>
                                    <label class="mmd-label" for="spec-{{ $variant->id }}-{{ $definition->id }}">{{ $definition->label }} @if($definition->is_required)<span style="color:#b91c1c">*</span>@endif</label>
                                    <input type="hidden" name="attributes[{{ $index }}][attribute_definition_id]" value="{{ $definition->id }}" @if(!$initiallyVisible) disabled @endif>
                                    @if($definition->data_type === 'boolean')
                                        <select id="spec-{{ $variant->id }}-{{ $definition->id }}" name="attributes[{{ $index }}][value_boolean]" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px;background:white" @if(!$initiallyVisible) disabled @endif {{ $definition->is_required ? 'required' : '' }}>
                                            <option value="">Pilih</option>
                                            <option value="1" @selected(old("attributes.$index.value_boolean", is_null($saved?->value_boolean) ? '' : (int) $saved->value_boolean) === '1' || old("attributes.$index.value_boolean", is_null($saved?->value_boolean) ? '' : (int) $saved->value_boolean) === 1)>Ya</option>
                                            <option value="0" @selected(old("attributes.$index.value_boolean", is_null($saved?->value_boolean) ? '' : (int) $saved->value_boolean) === '0' || old("attributes.$index.value_boolean", is_null($saved?->value_boolean) ? '' : (int) $saved->value_boolean) === 0)>Tidak</option>
                                        </select>
                                    @elseif($definition->data_type === 'number')
                                        <input id="spec-{{ $variant->id }}-{{ $definition->id }}" type="number" step="any" name="attributes[{{ $index }}][value_number]" value="{{ old("attributes.$index.value_number", $saved?->value_number) }}" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px" @if(!$initiallyVisible) disabled @endif {{ $definition->is_required ? 'required' : '' }}>
                                        @if($definition->unit)<small class="mmd-muted">Satuan: {{ $definition->unit->name }}</small>@endif
                                    @else
                                        <input id="spec-{{ $variant->id }}-{{ $definition->id }}" type="text" name="attributes[{{ $index }}][value_text]" value="{{ old("attributes.$index.value_text", $saved?->value_text) }}" maxlength="500" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px" @if(!$initiallyVisible) disabled @endif {{ $definition->is_required ? 'required' : '' }}>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="mmd-attr-add" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px">
                            <select aria-label="Pilih atribut tambahan" style="min-width:190px;max-width:320px;padding:9px;border:1px solid #cbd5e1;border-radius:7px;background:white">
                                <option value="">Pilih atribut tambahan</option>
                                @foreach($definitions as $definition)
                                    @if(!$attributeValues->has($definition->id) && !$definition->is_required)
                                        <option value="{{ $definition->id }}">{{ $definition->label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button" class="mmd-btn secondary mmd-attr-add-button" style="border:0;cursor:pointer">+ Tambah Atribut</button>
                        </div>
                        <button type="submit" class="mmd-btn" style="border:0;cursor:pointer;margin-top:12px">Simpan Spesifikasi</button>
                    </form>
                    </details>
                @endif
            </div>
        @empty
            <div class="mmd-empty">Belum ada varian atau spesifikasi untuk material ini.</div>
        @endforelse
    </section>

    <section id="mmd-harga" class="mmd-panel">
        <h2 class="mmd-section-title">Harga Supplier</h2>
        <p class="mmd-muted">Input harga terbaru per supplier. Saat harga diperbarui, harga sebelumnya otomatis disimpan sebagai riwayat.</p>
        {{-- Pendaftaran supplier dilakukan melalui menu Supplier. --}}
        @foreach($record->variants as $variant)
            <div style="padding:14px 0;border-top:1px solid #edf1f5">
                <strong>{{ $variant->variant_name }}</strong>
                <div class="mmd-muted">SKU: {{ $variant->sku }}</div>
                @php($variantPrices = $variant->prices->filter(fn ($price) => is_null($price->effective_until))->sortByDesc('effective_from'))
                @if($variantPrices->isEmpty())
                    <div class="mmd-empty" style="margin-top:10px">Belum ada harga supplier yang dicatat.</div>
                @else
                    <div class="mmd-table-wrap" style="margin-top:10px">
                        <table class="mmd-table">
                            <thead><tr><th>Supplier</th><th>Harga</th><th>Berlaku mulai</th><th>Berlaku sampai</th><th>Catatan</th><th>Aksi</th></tr></thead>
                            <tbody>
                            @foreach($variantPrices as $price)
                                <tr>
                                    <td>{{ $price->supplier?->name ?? $price->sourceQuote?->supplier?->name ?? $price->source_name ?? '—' }}</td>
                                    <td>{{ $price->currency }} {{ number_format((float) $price->amount, 2, ',', '.') }} / {{ $price->priceUnit?->name ?? '—' }}</td>
                                    <td>{{ $price->effective_from?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $price->effective_until?->format('d/m/Y') ?? 'Sekarang' }}</td>
                                    <td>{{ $price->change_reason ?? '—' }}</td>
                                    <td>
                                        @if($canManage && $variant->is_active && $price->supplier_id && !$price->effective_until && $price->price_unit_id)
                                            <button type="button" class="mmd-btn secondary mmd-price-edit-toggle" data-target="mmd-price-edit-{{ $variant->id }}-{{ $price->id }}" style="border:0;cursor:pointer">Edit Harga</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($canManage && $variant->is_active)
                        @foreach($variantPrices as $price)
                            @if($price->supplier_id && !$price->effective_until && $price->price_unit_id)
                                <div id="mmd-price-edit-{{ $variant->id }}-{{ $price->id }}" class="mmd-edit-form mmd-price-editor" hidden>
                                    <strong>Edit Harga — {{ $price->supplier?->name ?? $price->source_name }}</strong>
                                    <p class="mmd-hint">{{ $price->currency }} / {{ $price->priceUnit?->name }}. Perubahan disimpan melalui mekanisme harga yang sudah tersedia.</p>
                                    <form method="POST" action="{{ route('mm05.materials.variants.prices.update', [$record->id, $variant->id]) }}">
                                        @csrf
                                        <input type="hidden" name="supplier_id" value="{{ $price->supplier_id }}">
                                        <input type="hidden" name="price_unit_id" value="{{ $price->price_unit_id }}">
                                        <input type="hidden" name="currency" value="{{ $price->currency }}">
                                        <div class="mmd-grid" style="margin-top:12px">
                                            <div class="mmd-item"><label class="mmd-label">Harga Baru ({{ $price->currency }}) / {{ $price->priceUnit?->name }}</label>
                                                <div class="mmd-money-input"><span aria-hidden="true">Rp</span><input class="mmd-price-display" type="text" inputmode="decimal" autocomplete="off" required aria-label="Harga baru dalam rupiah" value="{{ number_format((float) $price->amount, 0, ',', '.') }}" data-raw="{{ $price->amount }}" placeholder="Contoh: 163.600"></div>
                                                <input type="hidden" name="amount" class="mmd-price-raw" value="{{ $price->amount }}">
                                                <small class="mmd-muted">Gunakan titik untuk ribuan, contoh: 163.600</small></div>
                                            <div class="mmd-item"><label class="mmd-label">Tanggal Berlaku</label><input name="effective_from" type="date" required value="{{ now()->toDateString() }}" class="mmd-price-date" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px"><small class="mmd-muted">Otomatis hari ini, dapat diubah.</small></div>
                                            <div class="mmd-item" style="grid-column:1/-1"><label class="mmd-label">Catatan</label><input name="change_reason" maxlength="2000" value="{{ $price->change_reason }}" style="width:100%;padding:9px;border:1px solid #cbd5e1;border-radius:7px"></div>
                                        </div>
                                        <button type="submit" class="mmd-btn" style="border:0;cursor:pointer;margin-top:12px">Simpan Harga</button>
                                    </form>
                                </div>
                            @endif
                        @endforeach
                    @endif
                @endif

            </div>
        @endforeach
    </section>


    <section id="mmd-riwayat" class="mmd-panel">
        <h2 class="mmd-section-title">Riwayat Harga</h2>
        @php($prices = $record->variants->flatMap->prices->sortByDesc('effective_from'))
        @if($prices->isEmpty())
            <div class="mmd-empty">Belum ada riwayat harga yang tercatat.</div>
        @else
            <div class="mmd-table-wrap"><table class="mmd-table"><thead><tr><th>Varian</th><th>Harga</th><th>Berlaku mulai</th><th>Berlaku sampai</th><th>Sumber</th></tr></thead><tbody>
            @foreach($record->variants as $variant) @foreach($variant->prices->sortByDesc('effective_from') as $price)
                <tr><td>{{ $variant->variant_name }}</td><td>{{ $price->currency }} {{ number_format((float) $price->amount, 2, ',', '.') }} / {{ $price->priceUnit?->name }}</td><td>{{ $price->effective_from?->format('d/m/Y') ?? '—' }}</td><td>{{ $price->effective_until?->format('d/m/Y') ?? 'Sekarang' }}</td><td>{{ $price->source_name ?? '—' }}</td></tr>
            @endforeach @endforeach
            </tbody></table></div>
        @endif
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.mmd-attr-add-button').forEach(function(button) {
    button.addEventListener('click', function() {
      const wrapper = button.closest('.mmd-attr-add');
      const select = wrapper.querySelector('select');
      const form = button.closest('form');
      const field = Array.from(form.querySelectorAll('.mmd-attr-field')).find(function(item) { return item.dataset.attrId === select.value; });
      if (!field) return;
      field.hidden = false;
      field.querySelectorAll('input,select,textarea').forEach(function(control) { control.disabled = false; });
      const option = select.options[select.selectedIndex];
      if (option) option.remove();
      select.value = '';
      field.querySelector('input:not([type="hidden"]),select,textarea')?.focus();
    });
  });
  document.querySelectorAll('.mmd-price-edit-toggle').forEach(function(button) {
    button.addEventListener('click', function() {
      const editor = document.getElementById(button.dataset.target);
      if (!editor) return;
      editor.hidden = !editor.hidden;
      if (!editor.hidden) {
        // Tanggal lokal saat editor dibuka, bukan tanggal harga sebelumnya.
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        const date = editor.querySelector('.mmd-price-date');
        if (date) date.value = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate());
        editor.scrollIntoView({behavior:'smooth',block:'nearest'});
      }
    });
  });
  // Nilai display boleh memakai pemisah ribuan Indonesia; yang dikirim tetap angka desimal.
  document.querySelectorAll('.mmd-price-editor form').forEach(function(form) {
    const display = form.querySelector('.mmd-price-display');
    const raw = form.querySelector('.mmd-price-raw');
    if (!display || !raw) return;
    const format = new Intl.NumberFormat('id-ID', {maximumFractionDigits:4});
    function parsePrice(text) {
      let s = String(text).trim().replace(/\s/g, '').replace(/^Rp/i, '');
      if (!s || !/^[0-9.,]+$/.test(s)) return null;
      // Format Indonesia: 163.600 atau 163.600,50. Titik ribuan, koma desimal.
      if (s.includes(',')) {
        if (!/^\d{1,3}(?:\.\d{3})*(?:,\d{1,4})?$|^\d+(?:,\d{1,4})?$/.test(s)) return null;
        s = s.replace(/\./g, '').replace(',', '.');
      } else if (s.includes('.')) {
        if (/^\d{1,3}(?:\.\d{3})+$/.test(s)) s = s.replace(/\./g, '');
        else if (!/^\d+\.\d{1,4}$/.test(s)) return null;
      }
      const n = Number(s);
      return Number.isFinite(n) && n > 0 ? n : null;
    }
    // Inisialisasi menggunakan nilai mentah agar presisi lama tidak hilang.
    const initial = Number(display.dataset.raw);
    if (Number.isFinite(initial)) display.value = format.format(initial);
    display.addEventListener('blur', function() {
      const n = parsePrice(display.value);
      if (n !== null) display.value = format.format(n);
    });
    form.addEventListener('submit', function(event) {
      const n = parsePrice(display.value);
      if (n === null) {
        event.preventDefault();
        display.setCustomValidity('Masukkan harga valid, misalnya 163.600');
        display.reportValidity();
        return;
      }
      display.setCustomValidity('');
      raw.value = n.toFixed(4);
    });
    display.addEventListener('input', function() { display.setCustomValidity(''); });
  });

});
</script>
@endsection
