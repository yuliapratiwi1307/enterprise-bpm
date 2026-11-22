@extends(request()->boolean('modal') ? 'layouts.mm-modal' : 'layouts.app')
@section('title','Tambah Material Baru | Enterprise BPM')
@section('content')

@php
    $duplicateSource = $duplicateSource ?? null;
    $duplicateVariant = $duplicateVariant ?? null;
    $baseUnits = $baseUnits ?? $units ?? collect();
    $priceUnits = $priceUnits ?? $units ?? collect();
@endphp

<style>
.mm-wizard{width:min(1440px,calc(100% - 48px));max-width:none;margin:28px auto;padding:0;color:#172033}
.mm-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
.mm-card{background:#fff;border:1px solid #e2e8f0;border-radius:15px;padding:24px;margin-top:16px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:24px;align-items:start}
.mm-card h2{font-size:18px;margin:0 0 5px}.mm-help{color:#64748b;font-size:13px;margin:0 0 15px}
.mm-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.mm-field label{display:block;font-size:13px;font-weight:650;margin:0 0 6px}
.mm-field input,.mm-field select,.mm-field textarea{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:9px;background:#fff}
.mm-field input:focus,.mm-field select:focus,.mm-field textarea:focus{outline:2px solid #93c5fd;border-color:#3b82f6}
.mm-section{border:1px solid #e2e8f0;border-radius:13px;padding:20px;margin:0;min-width:0}
.mm-muted{color:#64748b;font-size:12px}.mm-error{color:#b91c1c;font-size:12px}
.mm-variant{border:1px solid #dbe4ef;background:#f8fafc;border-radius:12px;padding:15px;margin-top:12px}
.mm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.mm-btn{border:0;border-radius:9px;padding:10px 16px;font-weight:700;text-decoration:none;cursor:pointer;display:inline-block}
.mm-primary{background:#1d4ed8;color:white}.mm-secondary{background:#f1f5f9;color:#334155}.mm-danger{background:#fee2e2;color:#991b1b}
.mm-choice{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0}
.mm-choice label{display:flex;align-items:center;gap:7px;border:1px solid #dbe4ef;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600;cursor:pointer}
.mm-choice input{width:auto}
.mm-hidden{display:none!important}
@media(max-width:900px){.mm-card{grid-template-columns:1fr}.mm-wizard{width:calc(100% - 28px)}}
@media(max-width:700px){.mm-grid{grid-template-columns:1fr}.mm-card{padding:14px}.mm-section{padding:15px}}
.mm-wizard{width:min(1180px,calc(100% - 40px));margin:24px auto 42px;color:#1b2d47}
.mm-wizard>.mm-head{padding:21px 24px;background:#fff;border:1px solid #dfe7f0;border-radius:14px;margin-bottom:16px}
.mm-wizard>.mm-head h1{font-size:27px!important;color:#1b2d47;letter-spacing:-.025em}
.mm-wizard>.mm-head .mm-muted{color:#66768c}
.mm-wizard>.mm-card{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:0;background:transparent;border:0;border-radius:0}
.mm-wizard>.mm-card>section{padding:19px 20px;background:#fff;border:1px solid #e0e7ef;border-radius:13px;box-shadow:0 2px 8px rgba(20,40,70,.025)}
.mm-wizard>.mm-card>section h2{color:#203b60;font-size:16px;font-weight:750;letter-spacing:-.01em}
.mm-wizard .mm-help{color:#68798f;line-height:1.55}
.mm-wizard .mm-field label{color:#40536b;font-size:12px;font-weight:650;text-transform:none;letter-spacing:0}
.mm-wizard .mm-field input,.mm-wizard .mm-field select,.mm-wizard .mm-field textarea{border-color:#d3dce7;border-radius:8px;min-height:40px;font:inherit;font-size:13px;color:#1d304a}
.mm-wizard .mm-section{border-color:#e0e7ef;border-radius:13px;background:#fff}
.mm-wizard .mm-section#priceFields{margin-top:18px;padding:0;border:0;border-radius:0;background:transparent;box-shadow:none}
.mm-wizard .mm-section#priceFields h2{font-size:15px!important}
.mm-wizard .mm-variant{background:#f8fafd;border-color:#e2e9f1;border-radius:10px}
.mm-wizard .mm-btn{min-height:36px;padding:8px 13px;border-radius:8px;font-size:12px;font-weight:700}
.mm-wizard .mm-primary{background:#234a78;border-color:#234a78}.mm-wizard .mm-primary:hover{background:#315f91}
.mm-wizard .mm-secondary{background:#edf3f9;border:1px solid #d8e2ed;color:#294b72}
.mm-wizard .mm-choice label{border-color:#dfe7ef;border-radius:8px;background:#fafcfe;font-weight:600}
.mm-wizard .mm-spec-requirement{display:inline-flex;margin-left:5px;padding:3px 7px;border-radius:999px;background:#edf3f9;color:#31567e;font-size:10px;font-weight:700;vertical-align:1px}
.mm-wizard .mm-spec-unit-note{display:block;margin-top:5px;color:#718198;font-size:11px}
.mm-wizard .mm-spec-fields{gap:13px 15px}
.mm-wizard .mm-actions{grid-column:1/-1;padding:14px 0 0}
@media(max-width:900px){.mm-wizard{width:calc(100% - 28px)}.mm-wizard>.mm-card{grid-template-columns:1fr}}
@media(max-width:600px){.mm-wizard{width:calc(100% - 24px);margin-top:14px}.mm-wizard>.mm-head{padding:16px}.mm-wizard>.mm-card>section{padding:15px}}

/* MM05: penyederhanaan form modal */
.mm-wizard > .mm-head {
    display: none !important;
}

.mm-wizard .mm-card > .mm-identity-section {
    order: 1;
}

.mm-wizard .mm-card > .mm-supplier-section {
    order: 2;
}

.mm-wizard .mm-card > .mm-specification-section {
    order: 3;
}

.mm-wizard .mm-card > .mm-actions {
    order: 4;
}


/* MM05 GRID FIX - Layout dua kolom */
.mm-wizard form#materialWizard > .mm-card {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) !important;
    gap: 16px !important;
    align-items: stretch !important;
}

.mm-wizard form#materialWizard > .mm-card > .mm-identity-section {
    grid-column: 1;
    grid-row: 1;
}

.mm-wizard form#materialWizard > .mm-card > .mm-specification-section {
    grid-column: 1;
    grid-row: 2;
}

.mm-wizard form#materialWizard > .mm-card > .mm-supplier-section {
    grid-column: 2;
    grid-row: 1 / span 2;
}

.mm-wizard form#materialWizard > .mm-card > .mm-actions {
    grid-column: 1 / -1;
    grid-row: 3;
}

@media(max-width: 900px) {
    .mm-wizard form#materialWizard > .mm-card {
        grid-template-columns: 1fr !important;
    }

    .mm-wizard form#materialWizard > .mm-card > section {
        grid-column: 1 !important;
        grid-row: auto !important;
    }

    .mm-wizard form#materialWizard > .mm-card > .mm-actions {
        grid-column: 1 !important;
        grid-row: auto !important;
    }
}

</style>

<div class="mm-wizard">
 <div class="mm-head">
  <div>
   <h1 style="font-size:27px;margin:0">{{ $duplicateSource ? 'Duplikasi Material' : 'Tambah Material Baru' }}</h1>
   <p class="mm-muted" style="font-size:14px;margin:5px 0 0">Kode baru dibuat otomatis. Supplier dan harga dapat ditentukan kembali.</p>
  </div>
  <a class="mm-btn mm-secondary" href="{{ route('mm05.materials.index') }}">Kembali ke daftar</a>
 </div>

 @if($errors->any())
  <div class="mm-card" style="grid-template-columns:1fr;border-color:#fecaca;background:#fff7f7">
   <b>Periksa kembali data berikut:</b>
   <ul>@foreach($errors->all() as $error)<li class="mm-error">{{ $error }}</li>@endforeach</ul>
  </div>
 @endif

 @if($duplicateSource)
  <div class="mm-section" style="background:#f8fafc">Sumber duplikasi: <strong>{{ $duplicateSource->code }} · {{ $duplicateSource->name }}</strong>. Nilai yang ditampilkan dapat diubah sebelum disimpan.</div>
 @endif

 <form method="POST" action="{{ route('mm05.materials.wizard.store', request()->boolean('modal') ? ['modal' => 1] : []) }}" id="materialWizard">
 @csrf
 <div class="mm-card">
  <section class="mm-identity-section">
   <h2>Identitas Material</h2>
   <p class="mm-help">Masukkan nama dan kategori material. Kode material dibuat otomatis oleh sistem.</p>
   <div class="mm-grid">
    <div class="mm-field"><label>Nama Material *</label><input name="name" value="{{ old('name', $duplicateSource?->name) }}" required placeholder="Contoh: Stainless Steel SS304"></div>
    <div class="mm-field"><label>Kategori *</label>
     <select name="category_id" id="category_id" required>
      <option value="">Pilih kategori</option>
      @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('category_id', $duplicateSource?->category_id)==$cat->id)>{{ $cat->name }}</option>@endforeach
     </select>
    </div>
    <div class="mm-field"><label for="specification_template_id">Template Spesifikasi</label>
     <select name="specification_template_id" id="specification_template_id">
      <option value="">Pilih template</option>
      @foreach($templates as $template)
        <option value="{{ $template->id }}" data-category="{{ $template->category_id }}" @selected(old('specification_template_id', $duplicateSource?->specification_template_id) == $template->id)>{{ $template->name }} ({{ $template->code }})</option>
      @endforeach
     </select>
    </div>
    <div class="mm-field"><label>Satuan Dasar *</label><select name="base_unit_id" required><option value="">Pilih satuan</option>@foreach($baseUnits as $unit)<option value="{{ $unit->id }}" @selected(old('base_unit_id', $duplicateVariant?->base_unit_id)==$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach</select></div>
    <div class="mm-field"><label>Satuan Harga</label><select name="price_unit_id"><option value="">Pilih satuan harga</option>@foreach($priceUnits as $unit)<option value="{{ $unit->id }}" @selected(old('price_unit_id')==$unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>@endforeach</select></div>
    <div class="mm-field"><label>Konversi ke satuan dasar</label><input type="number" min="0.00000001" step="0.00000001" name="conversion_factor" value="{{ old('conversion_factor') }}" placeholder="Contoh: 100"><span class="mm-muted">Jumlah satuan dasar dalam 1 satuan harga. Kosongkan jika belum diketahui.</span></div>
    <div class="mm-field" style="grid-column:1/-1"><label>Deskripsi (opsional)</label><textarea name="description" rows="2" placeholder="Keterangan tambahan">{{ old('description', $duplicateSource?->description) }}</textarea></div>
   </div>
  </section>

  <section class="mm-section mm-specification-section">
   <h2>Spesifikasi Material</h2>
   <p class="mm-help">Atribut spesifikasi mengikuti template yang dipilih. Isian bertanda Wajib harus dilengkapi sebelum material dapat disimpan.</p>
   <div id="templateAttributeFields" class="mm-grid mm-spec-fields" aria-live="polite"></div>
   <p class="mm-muted" id="templateAttributeStatus" role="status">Pilih kategori dan template untuk memuat atribut spesifikasi.</p>
   <p class="mm-error" id="templateAttributeError" role="alert" hidden></p>
   <div id="customAttributePanel" class="mm-hidden">
    <p class="mm-muted">Kategori ini belum memiliki template aktif. Spesifikasi manual dapat ditambahkan.</p>
    <div id="customAttributeRows">
     @foreach(old('custom_attributes', []) as $index => $customAttribute)
      <div class="mm-grid custom-attribute-row" style="margin-top:10px">
       <div class="mm-field"><label>Nama spesifikasi</label><input name="custom_attributes[{{ $index }}][name]" maxlength="150" value="{{ $customAttribute['name'] ?? '' }}" placeholder="Contoh: Finishing"></div>
       <div class="mm-field"><label>Nilai</label><div style="display:flex;gap:8px"><input name="custom_attributes[{{ $index }}][value]" maxlength="500" value="{{ $customAttribute['value'] ?? '' }}" placeholder="Contoh: 2B"><button type="button" class="mm-btn mm-danger remove-custom-attribute">Hapus</button></div></div>
      </div>
     @endforeach
    </div>
    <button type="button" class="mm-btn mm-secondary" id="addCustomAttribute" style="margin-top:10px">+ Tambah Spesifikasi</button>
   </div>
  </section>

  <section class="mm-section mm-supplier-section">
   <h2>Supplier</h2>
   <p class="mm-help">Opsional. Harga dicatat langsung berdasarkan konfirmasi Procurement melalui WhatsApp, telepon, email, atau media lain.</p>
   <div class="mm-choice">
    <label><input type="radio" name="supplier_mode" value="existing" @checked(old('supplier_mode', $duplicateSource ? 'none' : 'existing') === 'existing')> Pilih supplier terdaftar</label>
    <label><input type="radio" name="supplier_mode" value="new" @checked(old("supplier_mode") === "new")> Daftarkan supplier baru</label>
    <label><input type="radio" name="supplier_mode" value="none" @checked(old('supplier_mode', $duplicateSource ? 'none' : 'existing') === 'none')> Belum menentukan supplier</label>
   </div>

   <div id="existingSupplier" class="mm-field">
    <label>Supplier Terdaftar</label>
    <select name="supplier_id" id="supplier_id"><option value="">Pilih supplier</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" data-address="{{ e($s->address ?? '') }}" data-contact="{{ e($s->contact_name ?? '') }}" data-phone="{{ e($s->phone ?? '') }}" @selected(old('supplier_id')==$s->id)>{{ $s->code }} — {{ $s->name }}</option>@endforeach</select>
    <div id="existingSupplierAddress" class="mm-muted" style="margin-top:8px"></div>
   </div>

   <div id="newSupplier" class="mm-hidden">
    <div class="mm-grid">
     <div class="mm-field"><label>Nama Supplier *</label><input name="new_supplier_name" value="{{ old('new_supplier_name') }}" placeholder="Nama perusahaan supplier"></div>
     <div class="mm-field"><label>Nama PIC (opsional)</label><input name="new_supplier_contact" value="{{ old('new_supplier_contact') }}" placeholder="Nama orang yang dapat dihubungi"></div>
     <div class="mm-field" style="grid-column:1/-1"><label>Alamat Supplier (opsional)</label><textarea name="new_supplier_address" rows="3" placeholder="Alamat kantor / gudang supplier">{{ old('new_supplier_address') }}</textarea></div>
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
    <p class="mm-muted">Harga pembelian awal bersifat opsional dan dicatat untuk material ini.</p>
    <div class="mm-grid" style="margin-top:12px">
     <div class="mm-field"><label>Harga</label><input type="number" min="0.0001" step="0.0001" name="price_amount" value="{{ old('price_amount') }}" placeholder="Contoh: 150000"></div>
     <div class="mm-field"><label>Mata Uang</label><select name="currency"><option value="IDR" @selected(old('currency','IDR')==='IDR')>IDR — Rupiah</option><option value="USD" @selected(old('currency')==='USD')>USD — Dollar AS</option></select></div>
     <div class="mm-field"><label>Berlaku Mulai</label><input type="date" name="effective_from" value="{{ old('effective_from', now()->format('Y-m-d')) }}"></div>
     <div class="mm-field"><label>Media Konfirmasi</label><select name="communication_method"><option>WhatsApp</option><option>Telepon</option><option>Email</option><option>Meeting</option><option>Lainnya</option></select></div>
     <div class="mm-field"><label>Catatan Konfirmasi</label><input name="communication_note" value="{{ old('communication_note') }}" placeholder="Nama PIC / ringkasan konfirmasi"></div>
    </div>
   </div>
  </section>

  
  <div class="mm-actions" style="grid-column:1/-1"><a class="mm-btn mm-secondary" href="{{ route('mm05.materials.index') }}">Batal</a><button class="mm-btn mm-primary" type="submit">Simpan Material</button></div>
 </div>
 </form>
</div>

<script>
(function(){
 const supplierSelect=document.getElementById('supplier_id');
 const supplierAddress=document.getElementById('existingSupplierAddress');
 function syncSupplierAddress(){
  if(!supplierSelect || !supplierAddress) return;
  const opt=supplierSelect.options[supplierSelect.selectedIndex];
  const address=opt ? (opt.dataset.address || '') : '';
  const details=[address ? 'Alamat: '+address : 'Alamat belum tersedia.',opt?.dataset.contact ? 'Kontak: '+opt.dataset.contact : '',opt?.dataset.phone ? 'Telepon: '+opt.dataset.phone : ''].filter(Boolean);
  supplierAddress.textContent=supplierSelect.value ? details.join(' · ') : '';
 }
 if(supplierSelect){supplierSelect.addEventListener('change',syncSupplierAddress);syncSupplierAddress();}
 const category=document.getElementById('category_id');
 const templateSelect=document.getElementById('specification_template_id');
 const templateFields=document.getElementById('templateAttributeFields');
 const templateStatus=document.getElementById('templateAttributeStatus');
 const templateError=document.getElementById('templateAttributeError');
 const customAttributePanel=document.getElementById('customAttributePanel');
 const customAttributeRows=document.getElementById('customAttributeRows');
 const materialForm=document.getElementById('materialWizard');
 const oldAttributeValues=@json(old('attributes', $duplicateAttributes));
 const templateAttributesUrlPrefix=@json(route('mm05.templates.index'));
 let loadedTemplateId=null;
 let requestController=null;
 let requestSequence=0;
 let customAttributeIndex={{ count(old('custom_attributes', [])) }};

 function clearTemplateFields(){
  templateFields.replaceChildren();
  loadedTemplateId=null;
 }
 function showTemplateError(message){
  templateError.textContent=message;
  templateError.hidden=false;
 }
 function setCustomAttributesEnabled(enabled){
  customAttributePanel.classList.toggle('mm-hidden',!enabled);
  customAttributePanel.querySelectorAll('input,button').forEach(element=>{
   element.disabled=!enabled;
  });
 }
 function visibleTemplates(){
  return Array.from(templateSelect.options).filter(option=>option.value!==''&&!option.hidden);
 }
 function syncCategory(initial=false){
  requestController?.abort();
  requestSequence++;
  clearTemplateFields();
  templateError.hidden=true;
  templateError.textContent='';

  Array.from(templateSelect.options).forEach(option=>{
   const matches=option.value!==''&&option.dataset.category===category.value;
   option.hidden=option.value!==''&&Boolean(category.value)&&!matches;
   option.disabled=option.value!==''&&Boolean(category.value)&&!matches;
  });

  const hasTemplates=visibleTemplates().length>0;
  templateSelect.required=Boolean(category.value)&&hasTemplates;
  setCustomAttributesEnabled(Boolean(category.value)&&!hasTemplates);

  if(!initial||!category.value||templateSelect.selectedOptions[0]?.dataset.category!==category.value){
   templateSelect.value='';
  }
  if(templateSelect.value){
   loadTemplateAttributes(true);
  }else if(!category.value){
   templateStatus.textContent='Pilih kategori dan template untuk memuat atribut spesifikasi.';
  }else if(hasTemplates){
   templateStatus.textContent='Pilih template spesifikasi untuk memuat atribut.';
  }else{
   templateStatus.textContent='Kategori ini belum memiliki template aktif.';
  }
 }

 function addTemplateField(attribute, initialValue){
  const wrapper=document.createElement('div');
  wrapper.className='mm-field';
  const label=document.createElement('label');
  const inputId='attribute-'+attribute.id;
  label.htmlFor=inputId;
  label.append(document.createTextNode(attribute.label+' '));
  const requirement=document.createElement('span');
  requirement.className='mm-spec-requirement';
  requirement.textContent=attribute.is_required?'Wajib':'Opsional';
  label.append(requirement);
  wrapper.append(label);

  let input;
  if(attribute.input_type==='boolean'||(attribute.input_type==='text'&&attribute.option_values.length>0)){
   input=document.createElement('select');
   const placeholder=document.createElement('option');
   placeholder.value='';
   placeholder.textContent='Pilih jawaban';
   input.append(placeholder);
   const options=attribute.input_type==='boolean'
    ? [{value:'1',label:'Ya'},{value:'0',label:'Tidak'}]
    : attribute.option_values.map(value=>({value:String(value),label:String(value)}));
   options.forEach(optionData=>{
    const option=document.createElement('option');
    option.value=optionData.value;
    option.textContent=optionData.label;
    input.append(option);
   });
  }else{
   input=document.createElement('input');
   input.type=attribute.input_type==='number'?'number':'text';
   if(attribute.input_type==='number'){
    input.step='0.000001';
    input.min='-999999999999.999999';
    input.max='999999999999.999999';
   }else{
    input.maxLength=500;
   }
  }
  input.id=inputId;
  input.name=`attributes[${attribute.id}]`;
  input.required=attribute.is_required;
  input.value=initialValue??'';
  wrapper.append(input);

  if(attribute.unit){
   const unit=document.createElement('small');
   unit.className='mm-spec-unit-note';
   unit.textContent='Satuan: '+attribute.unit;
   wrapper.append(unit);
  }
  templateFields.append(wrapper);
 }

 async function loadTemplateAttributes(restoreValues=false){
  requestController?.abort();
  const sequence=++requestSequence;
  clearTemplateFields();
  templateError.hidden=true;
  templateError.textContent='';
  const templateId=templateSelect.value;
  if(!templateId){
   templateStatus.textContent=category.value?'Pilih template spesifikasi untuk memuat atribut.':'Pilih kategori dan template untuk memuat atribut spesifikasi.';
   return;
  }

  requestController=new AbortController();
  const controller=requestController;
  const url=new URL(`${templateAttributesUrlPrefix}/${encodeURIComponent(templateId)}/attributes`,window.location.origin);
  url.searchParams.set('category_id',category.value);
  templateStatus.textContent='Memuat atribut template...';

  try{
   const response=await fetch(url.toString(),{
    headers:{Accept:'application/json'},
    cache:'no-store',
    credentials:'same-origin',
    signal:controller.signal
   });
   const contentType=response.headers.get('content-type')||'';
   const payload=contentType.includes('application/json')?await response.json():null;
   if(!response.ok){
    throw new Error(payload?.message||`Server menolak permintaan (HTTP ${response.status}).`);
   }
   if(!payload||!Array.isArray(payload.attributes)){
    throw new Error('Server tidak mengembalikan daftar atribut template yang valid.');
   }
   if(sequence!==requestSequence||templateSelect.value!==templateId){
    return;
   }
   if(String(payload.template_id)!==String(templateId)||String(payload.category_id)!==String(category.value)){
    throw new Error('Respons atribut tidak sesuai dengan template atau kategori yang dipilih.');
   }

   payload.attributes.forEach(attribute=>{
    const oldValue=restoreValues?oldAttributeValues[String(attribute.id)]??'':'';
    addTemplateField(attribute,oldValue);
   });
   loadedTemplateId=templateId;
   templateStatus.textContent=payload.attributes.length?'':'Template ini belum memiliki atribut spesifikasi.';
  }catch(error){
   if(error.name==='AbortError'||sequence!==requestSequence){
    return;
   }
   templateStatus.textContent='';
   showTemplateError(`Atribut template gagal dimuat: ${error.message}`);
  }
 }

 category.addEventListener('change',()=>{
  customAttributeRows.replaceChildren();
  customAttributeIndex=0;
  syncCategory();
 });
 templateSelect.addEventListener('change',()=>loadTemplateAttributes(false));
 materialForm.addEventListener('submit',event=>{
  if(templateSelect.value&&loadedTemplateId!==templateSelect.value){
   event.preventDefault();
   showTemplateError('Atribut template belum berhasil dimuat. Periksa pesan di atas sebelum menyimpan material.');
  }
 });
 syncCategory(true);

 document.getElementById('addCustomAttribute').addEventListener('click',()=>{
  const row=document.createElement('div');row.className='mm-grid custom-attribute-row';row.style.marginTop='10px';
  row.innerHTML=`<div class="mm-field"><label>Nama spesifikasi</label><input name="custom_attributes[${customAttributeIndex}][name]" maxlength="150" placeholder="Contoh: Finishing"></div>
   <div class="mm-field"><label>Nilai</label><div style="display:flex;gap:8px"><input name="custom_attributes[${customAttributeIndex}][value]" maxlength="500" placeholder="Contoh: 2B"><button type="button" class="mm-btn mm-danger remove-custom-attribute">Hapus</button></div></div>`;
  customAttributeRows.appendChild(row);customAttributeIndex++;
 });
 customAttributeRows.addEventListener('click',e=>{
  if(e.target.classList.contains('remove-custom-attribute')) e.target.closest('.custom-attribute-row').remove();
 });

 const modeInputs=[...document.querySelectorAll('input[name="supplier_mode"]')];
 const existing=document.getElementById('existingSupplier');
 const fresh=document.getElementById('newSupplier');
 const price=document.getElementById('priceFields');
 function syncSupplier(){
  const mode=modeInputs.find(x=>x.checked)?.value||'existing';
  existing.classList.toggle('mm-hidden',mode!=='existing');
  fresh.classList.toggle('mm-hidden',mode!=='new');
  price.classList.toggle('mm-hidden',mode==='none');
  supplierSelect.disabled=mode!=='existing';
  fresh.querySelectorAll('input,textarea').forEach(el=>el.disabled=mode!=='new');
  price.querySelectorAll('input,select').forEach(el=>el.disabled=mode==='none');
 }
 let currentMode=modeInputs.find(x=>x.checked)?.value||'existing';
 function changeSupplierMode(){
  const nextMode=modeInputs.find(x=>x.checked)?.value||'existing';
  if(currentMode!==nextMode){
   supplierSelect.value='';
   if(currentMode==='new') fresh.querySelectorAll('input,textarea').forEach(el=>el.value='');
   if(nextMode==='none') price.querySelectorAll('input').forEach(el=>el.value='');
   currentMode=nextMode;
   syncSupplierAddress();
  }
  syncSupplier();
 }
 modeInputs.forEach(x=>x.addEventListener('change',changeSupplierMode));syncSupplier();

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
