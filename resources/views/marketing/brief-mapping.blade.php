<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pratinjau Pemetaan PROD-03 · Enterprise BPM</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f1f5fb;color:#17304f;font:15px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}header{background:#122b49;color:white;padding:19px max(24px,calc((100vw - 1080px)/2))}main{max-width:1080px;margin:35px auto;padding:0 24px}h1{margin:8px 0 3px;font-size:30px;letter-spacing:-.6px}h2{font-size:19px}.sub{color:#647b98}.eyebrow{font-size:12px;font-weight:700;letter-spacing:1.4px;color:#618bb8}a{color:#2169b9}a.back{display:inline-block;margin-bottom:15px}.card{background:#fff;border:1px solid #d8e3f0;border-radius:14px;padding:23px;margin:18px 0;box-shadow:0 5px 16px #102f4c07}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.comp{border:1px solid #dce7f2;border-radius:10px;padding:16px;min-height:138px}.comp h3{font-size:16px;margin:0 0 8px}.comp p{white-space:pre-wrap;overflow-wrap:anywhere;margin:5px 0}.tag{font-size:12px;font-weight:700;color:#825800;background:#fff3d9;border-radius:20px;padding:3px 9px;display:inline-block}.tag.ok{background:#e2f5e9;color:#197346}.muted{font-size:12px;color:#647b98}.warning{background:#eef5ff;border-radius:10px;padding:15px;color:#385f91}.links{display:flex;gap:14px;flex-wrap:wrap}button{background:#2874cd;color:white;border:0;border-radius:7px;padding:9px 13px;cursor:pointer}@media(max-width:650px){.grid{grid-template-columns:1fr}main{margin-top:20px}}
</style></head><body>
<header><strong>▦ Enterprise BPM</strong>　<small>Marketing / Workspace — Pratinjau Pemetaan PROD-03</small></header>
<main><a class="back" href="{{ route('marketing.workspace') }}">← Kembali ke Marketing Workspace</a>
<div class="eyebrow">TAHAP 2F · HANYA BACA · DATABASE PENGUJIAN</div>
<h1>Pratinjau Persiapan Brief → PROD-03</h1>
<p class="sub">{{ $draft->project_name ?: ('Draft #'.$draft->id) }} · {{ $draft->client_name ?: 'Klien belum diisi' }} · PROD-02 #{{ $draft->id }}</p>
<div class="card"><h2>Status dokumen dan batasan</h2><p>Status PROD-02: <strong>{{ $draft->status }}</strong> · Nomor REQ: <strong>{{ $draft->request_number ?: 'Belum diterbitkan' }}</strong></p>
<div class="warning">Ini hanya pemetaan dan pratinjau informasi. Tidak membuat Brief Marketing PROD-03, tidak mengubah data, tidak menjalankan validasi readiness, dan tidak melakukan distribusi. Draft PROD-02 tetap harus diselesaikan melalui proses resmi.</div></div>
<div class="card"><h2>Enam komponen wajib PROD-03</h2><p class="sub">Field yang tidak tersedia dibiarkan kosong. Tidak ada penyalinan otomatis ke dokumen resmi pada tahap ini.</p>
<div class="grid">
@foreach($components as $component)
<section class="comp"><h3>{{ $loop->iteration }}. {{ $component['label'] }}</h3>
@if($component['value'])<span class="tag ok">Data persiapan tersedia</span><p>{{ $component['value'] }}</p>@else<span class="tag">Belum tersedia</span><p class="sub">Belum ada data yang dapat dipetakan dengan aman.</p>@endif
<div class="muted">Sumber: {{ $component['origin'] }}</div></section>
@endforeach
</div></div>
<div class="card"><h2>Catatan tambahan dari Marketing</h2>
<p><strong>Butuh Designer:</strong> {{ !empty($preparation['designer']) ? 'Ya — belum otomatis membuat Design Request' : 'Tidak / belum dipilih' }}</p>
<p><strong>Provisional:</strong> {{ !empty($preparation['provisional']) ? 'Ya' : 'Tidak' }}</p>
@if(!empty($preparation['provisional']))<p><strong>Dasar estimasi:</strong> {{ $preparation['source'] ?? 'Belum diisi' }}</p><p class="muted">Catatan provisional ini belum dialokasikan otomatis ke masing-masing komponen; verifikasi komponen tetap diperlukan pada PROD-03.</p>@endif
<p class="muted">Status data persiapan: {{ $preparation ? 'Ditemukan' : 'Belum ditemukan' }}. Pembacaan berasal dari riwayat event Draft PROD-02.</p></div>
<div class="links"><a class="back" href="{{ route('marketing.workspace') }}">← Kembali ke Workspace</a><a href="{{ route('prod02.edit', $draft) }}">Lihat PROD-02 resmi ↗</a></div>

{{-- MARKETING_WORKSPACE_STAGE2G_PREFILL: only a navigation link, never creates a brief --}}
@if($draft->status === 'RECORDED')
<div class="card">
  <h2>Gunakan persiapan ini pada formulir PROD-03</h2>
  <p>Isi kolom kosong secara manual melalui tombol prefill di formulir resmi. Konfirmasi enam komponen dan provisional tetap diperlukan.</p>
  <a href="{{ route('prod03.briefs.create', $draft) }}?workspace_prefill=1">Buka formulir PROD-03 dengan opsi Prefill ↗</a>
</div>
@endif

</main></body></html>
