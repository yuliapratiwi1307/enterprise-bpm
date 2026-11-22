@extends('layouts.app')
@section('title','Tambah Material Baru | Enterprise BPM')
@section('content')
<style>
.mm-wizard{max-width:1080px;margin:28px auto;padding:0 20px;color:#172033}
.mm-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.mm-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:22px;margin-top:16px}
.mm-card h2{font-size:18px;margin:0 0 5px}.mm-help{color:#64748b;font-size:13px;margin:0 0 15px}
.mm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mm-field label{display:block;font-size:13px;font-weight:650;margin:0 0 6px}
.mm-field input,.mm-field select,.mm-field textarea{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:9px;background:#fff}
.mm-field input:focus,.mm-field select:focus,.mm-field textarea:focus{outline:2px solid #93c5fd;border-color:#3b82f6}
.mm-section{border-top:1px solid #e2e8f0;padding-top:20px;margin-top:22px}
.mm-muted{color:#64748b;font-size:12px}.mm-error{color:#b91c1c;font-size:12px}
.mm-variant{border:1px solid #dbe4ef;background:#f8fafc;border-radius:12px;padding:15px;margin-top:12px}
.mm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.mm-btn{border:0;border-radius:9px;padding:10px 16px;font-weight:700;text-decoration:none;cursor:pointer;display:inline-block}
.mm-primary{background:#1d4ed8;color:white}.mm-secondary{background:#f1f5f9;color:#334155}.mm-danger{background:#fee2e2;color:#991b1b}
.mm-choice{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0}
.mm-choice label{display:flex;align-items:center;gap:7px;border:1px solid #dbe4ef;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;cursor:pointer}
.mm-choice input{width:auto}
.mm-hidden{display:none!important}
@media(max-width:700px){.mm-grid{grid-template-columns:1fr}.mm-card{padding:16px}}
</style>

<div class="mm-wizard">
 <div class="mm-head">
  <div>
   <h1 style="font-size:27px;margin:0">Tambah Material Baru</h1>
   <p class="mm-muted" style="font-size:14px;margin:5px 0 0">Isi data material yang diperlukan. Supplier dan harga dapat ditambahkan sekarang atau nanti.</p>
  </div>
  <a class="mm-btn mm-secondary" href="{{ route('mm05.materials.index') }}">Kembali ke daftar</a>
 </div>

 @if($errors->any())
  <div class="mm-card" style="border-color:#fecaca;background:#fff7f7">
   <b>Periksa kembali data berikut:</b>
   <ul>@foreach($errors->all() as $error)<li class="mm-error">{{ $error }}</li>@endforeach</ul>
  </div>
 @endif

 <form method="POST" action="{{ route('mm05.materials.wizard.store') }}" id="materialWizard">
 @csrf
 <div class="mm-card">
  <section>
   <h2>1. Identitas Material</h2>
   <p class="mm-help">Masukkan nama dan kategori material. Kode material dibuat otomatis oleh sistem.</p>
   <div class="mm-grid">
    <div class="mm-field"><label>Nama Material *</label><input name="name" value="{{ old('name') }}" required placeholder="Contoh: Stainless Steel SS304"></div>
    <div class="mm-field"><label>Kategori *</label>
     <select name="category_id" id="category_id" required>
      <option value="">Pilih kategori</option>
      @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('category_id')==$cat->id)>{{ $cat->name }}</option>@endforeach
     </select>
    </div>
    <div class="mm-field" style="grid-column:1/-1"><label>Deskripsi (opsional)</label><textarea name="description" rows="2" placeholder="Keterangan tambahan">{{ old('description') }}</textarea></div>
   </div>
  </section>

  <section class="mm-section">
   <h2>2. Spesifikasi Material</h2>
   <p class="mm-help">Pilih kategori terlebih dahulu. Kolom spesifikasi yang sesuai akan muncul otomatis.</p>
   @foreach($definitions as $categoryId=>$defs)
    <div class="mm-grid category-defs mm-hidden" data-category="{{ $categoryId }}">
     @foreach($defs as $d)
      <div class="mm-field">
       <label>{{ $d->label }} @if($d->is_required)*@endif @if($d->unit)({{ $d->unit->name }})@endif</label>
       @if($d->data_type==='boolean')
        <select name="attributes[{{ $d->id }}]"><option value="">Pilih</option><option value="1">Ya</option><option value="0">Tidak</option></select>
       @elseif($d->data_type==='number')
        <input type="number" step="any" name="attributes[{{ $d->id }}]" value="{{ old('attributes.'.$d->id) }}">
       @else
        <input name="attributes[{{ $d->id }}]" value="{{ old('attributes.'.$d->id) }}">
       @endif
      </div>
     @endforeach
    </div>
   @endforeach
   <p class="mm-muted" id="noAttributes">Belum ada atribut spesifikasi yang dikonfigurasi untuk kategori ini. Anda dapat melanjutkan.</p>
  </section>

  <section class="mm-section">
   <h2>3. Varian Material</h2>
   <p class="mm-help">Satu varian utama disediakan. Tambahkan varian lain hanya jika material memiliki ukuran atau tipe berbeda.</p>
   <div id="variantRows">
    <div class="mm-variant" data-variant-row>
     <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><b>Varian Utama</b><span class="mm-muted">Wajib</span></div>
     <div class="mm-grid">
      <div class="mm-field"><label>Nama Varian *</label><input name="variants[0][variant_name]" value="{{ old('variants.0.variant_name','Varian Utama') }}" required></div>
      <div class="mm-field"><label>SKU *</label><input name="variants[0][sku]" value="{{ old('variants.0.sku') }}" required placeholder="Contoh: SS304-2MM"></div>
      <div class="mm-field"><label>Satuan Dasar *</label><select name="variants[0][base_unit_id]" required><option value="">Pilih satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('variants.0.base_unit_id')==$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach</select></div>
      <div class="mm-field"><label>Catatan Varian (opsional)</label><input name="variants[0][specification_note]" value="{{ old('variants.0.specification_note') }}" placeholder="Contoh: Tebal 2 mm"></div>
     </div>
    </div>
   </div>
   <button type="button" class="mm-btn mm-secondary" id="addVariant" style="margin-top:12px">+ Tambah Varian</button>
   <p class="mm-muted" style="margin-top:8px">Spesifikasi di bagian 2 berlaku untuk semua varian yang dibuat dalam form ini. Perbedaan khusus tiap varian dapat ditulis pada Catatan Varian.</p>
  </section>

  <section class="mm-section">
   <h2>4. Supplier dan Harga</h2>
   <p class="mm-help">Opsional. Harga dicatat langsung berdasarkan konfirmasi Procurement melalui WhatsApp, telepon, email, atau media lain.</p>
   <div class="mm-choice">
    <label><input type="radio" name="supplier_mode" value="existing" @checked(old("supplier_mode", "existing") === "existing")> Pilih supplier terdaftar</label>
    <label><input type="radio" name="supplier_mode" value="new" @checked(old("supplier_mode") === "new")> Daftarkan supplier baru</label>
    <label><input type="radio" name="supplier_mode" value="none" @checked(old("supplier_mode") === "none")> Belum menentukan supplier</label>
   </div>

   <div id="existingSupplier" class="mm-field">
    <label>Supplier Terdaftar</label>
    <select name="supplier_id" id="supplier_id"><option value="">Pilih supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->code }} — {{ $s->name }}</option>@endforeach</select>
   </div>

   <div id="newSupplier" class="mm-hidden">
    <div class="mm-grid">
     <div class="mm-field"><label>Nama Supplier *</label><input name="new_supplier_name" value="{{ old('new_supplier_name') }}" placeholder="Nama perusahaan supplier"></div>
     <div class="mm-field"><label>Nama PIC (opsional)</label><input name="new_supplier_contact" value="{{ old('new_supplier_contact') }}" placeholder="Nama orang yang dapat dihubungi"></div>
    </div>
    <div class="mm-field" style="margin-top:12px">
     <label>Nomor Telepon / WhatsApp (opsional)</label>
     <div id="phoneRows">
      <div class="phone-row" style="display:flex;gap:8px;margin-top:7px"><input name="new_supplier_phones[]" placeholder="Nomor telepon / WhatsApp" maxlength="60"><button type="button" class="mm-btn mm-secondary remove-phone mm-hidden">Hapus</button></div>
     </div>
     <button type="button" class="mm-btn mm-secondary" id="addPhone" style="margin-top:8px">+ Tambah nomor</button>
    </div>
   </div>

   <div id="priceFields" class="mm-section">
    <h2 style="font-size:16px">Harga Supplier (opsional)</h2>
    <p class="mm-muted">Harga awal akan dicatat untuk varian utama. Harga varian lain dapat diperbarui dari detail material.</p>
    <div class="mm-grid" style="margin-top:12px">
     <div class="mm-field"><label>Harga</label><input type="number" min="0.0001" step="0.0001" name="price_amount" value="{{ old('price_amount') }}" placeholder="Contoh: 150000"></div>
     <div class="mm-field"><label>Satuan Harga</label><select name="price_unit_id"><option value="">Pilih satuan harga</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('price_unit_id')==$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach</select></div>
     <div class="mm-field"><label>Mata Uang</label><select name="currency"><option value="IDR" @selected(old('currency','IDR')==='IDR')>IDR — Rupiah</option><option value="USD" @selected(old('currency')==='USD')>USD — Dollar AS</option></select></div>
     <div class="mm-field"><label>Berlaku Mulai</label><input type="date" name="effective_from" value="{{ old('effective_from', now()->format('Y-m-d')) }}"></div>
     <div class="mm-field"><label>Media Konfirmasi</label><select name="communication_method"><option>WhatsApp</option><option>Telepon</option><option>Email</option><option>Meeting</option><option>Lainnya</option></select></div>
     <div class="mm-field"><label>Catatan Konfirmasi</label><input name="communication_note" value="{{ old('communication_note') }}" placeholder="Nama PIC / ringkasan konfirmasi"></div>
    </div>
   </div>
  </section>

  <section class="mm-section">
   <h2>5. Periksa dan Simpan</h2>
   <p class="mm-help">Pastikan nama material, spesifikasi, varian, supplier, dan harga sudah benar.</p>
   <label style="display:flex;gap:9px;align-items:center;font-size:13px"><input type="checkbox" required style="width:auto"> Saya sudah memeriksa data yang dimasukkan.</label>
  </section>
  <div class="mm-actions"><a class="mm-btn mm-secondary" href="{{ route('mm05.materials.index') }}">Batal</a><button class="mm-btn mm-primary" type="submit">Simpan Material</button></div>
 </div>
 </form>
</div>

<script>
(function(){
 const category=document.getElementById('category_id');
 const blocks=[...document.querySelectorAll('.category-defs')];
 const empty=document.getElementById('noAttributes');
 function syncCategory(){
  let found=false;
  blocks.forEach(block=>{
   const active=block.dataset.category===category.value;
   block.classList.toggle('mm-hidden',!active);
   block.querySelectorAll('input,select').forEach(el=>el.disabled=!active);
   if(active) found=true;
  });
  empty.classList.toggle('mm-hidden',!category.value||found);
 }
 category.addEventListener('change',syncCategory);syncCategory();

 const variantRows=document.getElementById('variantRows');
 let variantIndex=1;
 document.getElementById('addVariant').addEventListener('click',()=>{
  const row=document.createElement('div');row.className='mm-variant';row.dataset.variantRow='';
  row.innerHTML=`<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px"><b>Varian Tambahan</b><button type="button" class="mm-btn mm-danger remove-variant">Hapus</button></div>
   <div class="mm-grid">
    <div class="mm-field"><label>Nama Varian *</label><input name="variants[${variantIndex}][variant_name]" required></div>
    <div class="mm-field"><label>SKU *</label><input name="variants[${variantIndex}][sku]" required placeholder="Contoh: SS304-3MM"></div>
    <div class="mm-field"><label>Satuan Dasar *</label><select name="variants[${variantIndex}][base_unit_id]" required><option value="">Pilih satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>@endforeach</select></div>
    <div class="mm-field"><label>Catatan Varian (opsional)</label><input name="variants[${variantIndex}][specification_note]"></div>
   </div>`;
  variantRows.appendChild(row);variantIndex++;
 });
 variantRows.addEventListener('click',e=>{
  if(e.target.classList.contains('remove-variant')) e.target.closest('[data-variant-row]').remove();
 });

 const modeInputs=[...document.querySelectorAll('input[name="supplier_mode"]')];
 const existing=document.getElementById('existingSupplier');
 const fresh=document.getElementById('newSupplier');
 const price=document.getElementById('priceFields');
 const supplierSelect=document.getElementById('supplier_id');
 function syncSupplier(){
  const mode=modeInputs.find(x=>x.checked)?.value||'existing';
  existing.classList.toggle('mm-hidden',mode!=='existing');
  fresh.classList.toggle('mm-hidden',mode!=='new');
  price.classList.toggle('mm-hidden',mode==='none');
  supplierSelect.disabled=mode!=='existing';
  fresh.querySelectorAll('input').forEach(el=>el.disabled=mode!=='new');
  price.querySelectorAll('input,select').forEach(el=>el.disabled=mode==='none');
 }
 modeInputs.forEach(x=>x.addEventListener('change',syncSupplier));syncSupplier();

 const phoneRows=document.getElementById('phoneRows');
 function refreshPhoneButtons(){
  const rows=[...phoneRows.querySelectorAll('.phone-row')];
  rows.forEach(row=>row.querySelector('.remove-phone').classList.toggle('mm-hidden',rows.length===1));
 }
 document.getElementById('addPhone').addEventListener('click',()=>{
  const row=document.createElement('div');row.className='phone-row';row.style='display:flex;gap:8px;margin-top:7px';
  row.innerHTML='<input name="new_supplier_phones[]" placeholder="Nomor telepon / WhatsApp" maxlength="60"><button type="button" class="mm-btn mm-secondary remove-phone">Hapus</button>';
  phoneRows.appendChild(row);refreshPhoneButtons();
 });
 phoneRows.addEventListener('click',e=>{
  if(e.target.classList.contains('remove-phone')){e.target.closest('.phone-row').remove();refreshPhoneButtons();}
 });
})();
</script>
@endsection
