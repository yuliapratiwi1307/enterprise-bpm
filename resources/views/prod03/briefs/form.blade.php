@extends('layouts.app')

@section('title', 'Form Brief Marketing · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', $brief->exists ? 'Edit Brief Marketing' : 'Susun Brief Marketing')
@section('page-description', 'Client Brief ' . $clientBrief->request_number . ' · ' . e($clientBrief->client_name))

@section('content')
<style>
/* PROD-03 Brief Marketing form-only styles. No shared CSS or layout changes. */
.prod03-editor-card { max-width: 1100px; }
.prod03-component-list { display: grid; gap: 12px; margin-top: 18px; }
.prod03-component { border: 1px solid #dce5f1; border-radius: 12px; background: #fff; overflow: hidden; }
.prod03-component-summary { display:flex; align-items:center; gap:14px; padding:16px 18px; cursor:pointer; list-style:none; color:#183153; }
.prod03-component-summary::-webkit-details-marker { display:none; }
.prod03-component-summary:focus-visible { outline: 3px solid #80b1ff; outline-offset:-3px; }
.prod03-component[open] .prod03-component-summary { background:#f5f8fd; border-bottom:1px solid #e5edf6; }
.prod03-component-number { display:grid; place-items:center; flex:none; width:32px; height:32px; border-radius:8px; background:#eaf2ff; color:#2057a2; font-size:12px; font-weight:800; }
.prod03-component-label { flex:1; font-size:14px; font-weight:700; }
.prod03-component-state { font-size:12px; color:#63748d; }
.prod03-component-chevron { font-size:22px; line-height:1; color:#63748d; }
.prod03-component[open] .prod03-component-chevron { transform:rotate(180deg); }
.prod03-component-body { padding:20px 22px; display:grid; gap:18px; }
.prod03-component-body .field { width:100%; min-width:0; }
.prod03-component-body textarea { min-height:76px; }
.prod03-provisional { padding:16px; border:1px solid #e4ebf4; background:#f9fbff; border-radius:10px; }
.prod03-provisional-checkbox { display:inline-flex !important; align-items:center; gap:10px; width:auto !important; max-width:100%; margin:0 !important; cursor:pointer; font-size:13px; font-weight:700; color:#25456d; }
.prod03-provisional-checkbox input[type=checkbox] { display:inline-block !important; appearance:auto !important; -webkit-appearance:checkbox !important; flex:none !important; width:16px !important; height:16px !important; min-width:16px !important; min-height:16px !important; margin:0 !important; padding:0 !important; border-radius:3px !important; }
.prod03-provisional-help { margin:7px 0 14px; font-size:12px; color:#63748d; }
.prod03-provisional .field label { display:block; margin-bottom:7px; }
.prod03-provisional .field textarea { min-height:72px; }
@media (max-width:640px) { .prod03-component-summary{padding:12px;gap:8px}.prod03-component-body{padding:14px}.prod03-component-state{display:none} }
</style>

    <div class="stack">
        @if (session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
        @if ($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
        <div class="notice">Isi enam komponen. Tandai data sementara sebagai <strong>PROVISIONAL</strong> dan lengkapi nilai serta sumber/dasar informasinya. Pemeriksaan kesiapan dilakukan terpisah dari penyimpanan Draft.</div>

        <form class="stack" method="POST" action="{{ $brief->exists ? route('prod03.briefs.update', $brief) : route('prod03.briefs.store', $clientBrief) }}">
            @csrf
            @if ($brief->exists)@method('PUT')@endif
            <section class="card prod03-editor-card">
                <div class="section-heading"><div><h2>Komponen Brief Marketing</h2><p>Lengkapi enam komponen. Buka bagian yang ingin diedit; informasi tetap tersimpan melalui Simpan Draft.</p></div></div>
                <div class="prod03-component-list">
                    @foreach ($componentDefinitions as $key => $label)
                        @php($component = $brief->components->firstWhere('component', $key))
                        @php($hasError = $errors->has("components.{$key}.value") || $errors->has("components.{$key}.information_basis"))
                        <details class="prod03-component" @if($loop->first || $hasError) open @endif>
                            <summary class="prod03-component-summary">
                                <span class="prod03-component-number">{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span class="prod03-component-label">{{ $label }}</span>
                                <span class="prod03-component-state">{{ filled(old("components.{$key}.value", $component?->value)) ? 'Terisi' : 'Belum diisi' }}</span>
                                <span class="prod03-component-chevron" aria-hidden="true">⌄</span>
                            </summary>
                            <div class="prod03-component-body">
                                <div class="field">
                                    <label for="component-{{ $key }}">{{ $label }}</label>
                                    @if (in_array($key, ['work_concept', 'visualization', 'work_specification'], true))
                                        <textarea id="component-{{ $key }}" name="components[{{ $key }}][value]" rows="3" aria-describedby="component-{{ $key }}-hint" @if($errors->has("components.{$key}.value")) aria-invalid="true" @endif>{{ old("components.{$key}.value", $component?->value) }}</textarea>
                                    @else
                                        <input id="component-{{ $key }}" name="components[{{ $key }}][value]" value="{{ old("components.{$key}.value", $component?->value) }}" aria-describedby="component-{{ $key }}-hint" @if($errors->has("components.{$key}.value")) aria-invalid="true" @endif>
                                    @endif
                                    <p class="field-hint" id="component-{{ $key }}-hint">Nilai final atau estimasi yang dapat ditinjau.</p>
                                    @error("components.{$key}.value")<p class="field-error">{{ $message }}</p>@enderror
                                </div>
                                <div class="prod03-provisional">
                                    <label class="prod03-provisional-checkbox" for="provisional-{{ $key }}">
                                        <input id="provisional-{{ $key }}" type="checkbox" name="components[{{ $key }}][is_provisional]" value="1" @checked(old("components.{$key}.is_provisional", $component?->is_provisional))>
                                        <span>Data provisional</span>
                                    </label>
                                    <p class="prod03-provisional-help">Tandai bila data masih bersifat sementara dan cantumkan dasar informasinya.</p>
                                    <div class="field">
                                        <label for="basis-{{ $key }}">Nilai dan sumber/dasar informasi provisional</label>
                                        <textarea id="basis-{{ $key }}" name="components[{{ $key }}][information_basis]" rows="2" aria-label="Sumber atau dasar informasi {{ strtolower($label) }}">{{ old("components.{$key}.information_basis", $component?->information_basis) }}</textarea>
                                        @error("components.{$key}.information_basis")<p class="field-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>
            <div class="actions">
                <button class="button" type="submit">Simpan Draft</button>
                @if ($brief->exists)<a class="button button-secondary" href="{{ route('prod03.briefs.show', $brief) }}">Kembali ke detail</a>@endif
                <a class="button button-secondary" href="{{ route('prod03.briefs.index') }}">Batal</a>
            </div>
        </form>
    </div>

{{-- MARKETING_WORKSPACE_STAGE2G_PREFILL: suggestion-only; no autosave --}}
@if(!$brief->exists && request()->boolean('workspace_prefill'))
<div class="notice" id="mw-prefill-panel" style="margin:12px 0">
    <strong>Persiapan Brief dari Marketing Workspace</strong>
    <p>Gunakan hanya data yang sudah tersedia. Tombol ini mengisi kolom kosong pada layar, <strong>tidak menyimpan atau membuat Brief</strong>.</p>
    <button class="button button-secondary" type="button" id="mw-prefill-button">Isi kolom kosong dari Persiapan Brief</button>
    <p id="mw-prefill-result" role="status" aria-live="polite"></p>
</div>
<script>
(() => {
  const button = document.getElementById('mw-prefill-button');
  const info = document.getElementById('mw-prefill-result');
  if (!button || !info) return;
  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      const response = await fetch(@json(route('marketing.workspace.brief-prefill', $clientBrief)), {credentials:'same-origin', headers:{'Accept':'application/json'}});
      if (!response.ok) throw new Error('Tidak bisa mengambil data (HTTP '+response.status+').');
      const data = await response.json();
      let count = 0;
      for (const [component,value] of Object.entries(data.suggestions || {})) {
        const input = document.querySelector('[name="components['+component+'][value]"]');
        if (input && !String(input.value || '').trim() && typeof value === 'string') {
          input.value = value;
          input.dispatchEvent(new Event('input',{bubbles:true}));
          count++;
        }
      }
      info.textContent = count+' kolom kosong diisi. Periksa keenam komponen sebelum Simpan Draft. '+
        (data.designer_requested?'Bantuan Designer diminta; Design Request belum dibuat. ':'')+
        (data.provisional_note?'Catatan provisional (atur manual per komponen): '+data.provisional_note:'');
    } catch (err) { info.textContent = 'Prefill gagal: '+err.message; }
    finally { button.disabled = false; }
  });
})();
</script>
@endif

@endsection
