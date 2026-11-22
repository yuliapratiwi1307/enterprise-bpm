<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Inbox Brief Produksi — SJR</title>
<style>body{font:14px Inter,system-ui,sans-serif;margin:0}*{box-sizing:border-box}a{text-decoration:none;color:#2367bd}
:root{--navy:#112441;--blue:#286de9;--line:#dce7f4;--canvas:#f4f6fb}
body{background:var(--canvas)!important;color:#172b4a}
.sjr-sidebar{position:fixed;left:0;top:0;bottom:0;width:222px;background:#10213e;color:#e1ecff;padding:24px 15px;z-index:10;display:flex;flex-direction:column}
.sjr-brand{display:flex;align-items:center;gap:11px;margin-bottom:45px;font-size:13px;font-weight:750;line-height:1.4}
.sjr-brandmark{height:38px;width:38px;display:grid;place-items:center;border-radius:10px;background:#3278f3;color:#fff;font-weight:800;font-size:16px}
.sjr-brand small{display:block;font-size:11px;color:#a9bfdc;font-weight:400}
.sjr-nav-caption{font-size:10px;letter-spacing:2px;color:#8da5c9;padding:0 10px;margin-bottom:12px}
.sjr-navlink{display:flex;align-items:center;gap:10px;color:#d9e8ff!important;border-radius:9px;padding:12px 13px;font-size:12px;font-weight:750;text-decoration:none;background:#263f66}
.sjr-nav-footer{margin-top:auto;border-top:1px solid #344968;padding:15px 9px 0;font-size:11px;color:#95abc9;line-height:1.65}
.sjr-topbar{height:72px;display:flex;align-items:center;justify-content:space-between;background:white;border-bottom:1px solid #e1e9f3;padding:0 27px;font-size:13px;font-weight:750}
.sjr-topbar small{display:block;font-size:11px;color:#7b8faa;margin-top:4px;font-weight:400}
.sjr-topbar-pill{background:#edf3fe;color:#2b63bd;border-radius:99px;padding:8px 12px;font-size:11px;white-space:nowrap}
.sjr-main{margin-left:222px;min-height:100vh}
.sjr-content{padding:25px 25px 48px;max-width:1800px;margin:auto}
.sjr-eyebrow{font-size:10px;letter-spacing:2.2px;color:#2a6ee9;font-weight:800}
.sjr-heading{font-size:28px;font-weight:800;margin:8px 0 5px;letter-spacing:-.7px}
.sjr-subtitle{color:#7287a3;font-size:12px}
.sjr-card{background:white;border:1px solid var(--line);border-radius:14px;padding:20px;box-shadow:0 5px 20px #19375808}
.sjr-icon-button{display:inline-flex;align-items:center;justify-content:center;height:35px;width:35px;border:1px solid #cfddf1;border-radius:9px;background:#fff;color:#2569ca;font-size:17px;text-decoration:none;font-weight:700}
.sjr-icon-button:hover{background:#ebf3ff;border-color:#a7c6f4}
.sjr-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:22px 0 18px}
.sjr-kpi{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px;display:flex;gap:12px;align-items:center}
.sjr-kpi-icon{background:#eaf2ff;color:#2c70dd;border-radius:10px;width:39px;height:39px;display:grid;place-items:center;font-size:18px}
.sjr-kpi-number{font-weight:850;font-size:22px;line-height:1}
.sjr-kpi-label{font-size:11px;color:#7083a1;margin-top:5px}
.sjr-controls{display:flex;gap:10px;align-items:center;margin:0 0 15px}
.sjr-search{border:1px solid #d9e4f2;border-radius:10px;min-width:250px;max-width:460px;width:100%;height:40px;padding:0 13px;font:inherit;background:#fff}
.sjr-select{border:1px solid #d9e4f2;border-radius:10px;padding:10px;background:white;color:#1f3759;font:inherit}
.sjr-table-wrap{overflow:auto}
.sjr-table{width:100%;border-collapse:collapse;min-width:740px}
.sjr-table th{background:#f7f9fc;color:#667e9d;font-size:11px;text-align:left;font-weight:750}
.sjr-table td,.sjr-table th{padding:16px 14px;border-bottom:1px solid #e9eef6}
.sjr-table td{font-size:12px;color:#213858}
.sjr-status{border-radius:99px;padding:7px 10px;display:inline-block;background:#ebf1ff;color:#3161b4;font-size:11px;font-weight:750;white-space:nowrap}
.sjr-status.clear{background:#e4f6eb;color:#17804a}
.sjr-status.clarify{background:#fff1e0;color:#a76515}
.sjr-empty{padding:35px;text-align:center;color:#7487a2}
@media(max-width:1050px){.sjr-sidebar{width:184px}.sjr-main{margin-left:184px}.sjr-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.sjr-sidebar{position:static;width:auto;height:auto;padding:12px}.sjr-nav-caption,.sjr-nav-footer{display:none}.sjr-brand{margin:0 0 10px}.sjr-main{margin-left:0}.sjr-topbar{height:auto;min-height:58px;padding:12px}.sjr-content{padding:14px}.sjr-kpis{grid-template-columns:repeat(2,1fr)}.sjr-controls{flex-wrap:wrap}.sjr-heading{font-size:23px}}
.sjr-status.ready{background:#e0f5e9;color:#147d48}
.sjr-ready-summary{display:flex;align-items:center;gap:8px;font-size:12px;color:#47749d;margin:-6px 0 16px}
.sjr-ready-dot{width:8px;height:8px;background:#22a66c;border-radius:50%}
</style></head><body>
<aside class="sjr-sidebar" aria-label="Navigasi SJR"><div class="sjr-brand"><span class="sjr-brandmark">S</span><span>Enterprise BPM<small>Simple Job Request</small></span></div><div class="sjr-nav-caption">MENU SJR</div><a class="sjr-navlink" href="{{ route('sjr.production.technical.index') }}">▦ &nbsp; Inbox Brief Produksi</a><div class="sjr-nav-footer">SJR • Perencanaan Produksi<br>Database pengujian<br>PROD-02 s.d. PROD-09 tetap terpisah</div></aside>
<div class="sjr-main"><header class="sjr-topbar"><div>Simple Job Request / Production Workspace<small>Produksi · Pemeriksaan Brief · Persiapan Teknis · HPP</small></div><span class="sjr-topbar-pill">SJR · PRODUCTION</span></header>
<main class="sjr-content"><div class="sjr-eyebrow">PRODUKSI / SJR</div><h1 class="sjr-heading">Inbox Brief Produksi</h1><p class="sjr-subtitle">Brief final Marketing yang siap diperiksa sebelum persiapan teknis dan kalkulasi HPP.</p>
@php
  $total = $jobs->count();
  $unreviewed = $jobs->filter(fn($j) => !in_array($j->decision, ['CLEAR','CLARIFICATION']))->count();
  $clear = $jobs->where('decision','CLEAR')->count();
  $clarify = $jobs->where('decision','CLARIFICATION')->count();
  // Read-only status of technical drafts, indexed by SJR number.
  $readyJobNumbers = \Illuminate\Support\Facades\DB::table('sjr_technical_drafts as d')
      ->join('sjr_jobs as j', 'j.id', '=', 'd.sjr_job_id')
      ->where('d.state', 'READY_FOR_HPP')
      ->whereIn('j.job_number', $jobs->pluck('job_number')->all())
      ->pluck('j.job_number')->all();
  $readyLookup = array_fill_keys($readyJobNumbers, true);
  $readyCount = count($readyJobNumbers);
@endphp
<div class="sjr-kpis">
  <div class="sjr-kpi"><span class="sjr-kpi-icon">▤</span><div><div class="sjr-kpi-number">{{ $total }}</div><div class="sjr-kpi-label">Total Brief Masuk</div></div></div>
  <div class="sjr-kpi"><span class="sjr-kpi-icon">◷</span><div><div class="sjr-kpi-number">{{ $unreviewed }}</div><div class="sjr-kpi-label">Belum Diperiksa</div></div></div>
  <div class="sjr-kpi"><span class="sjr-kpi-icon">✓</span><div><div class="sjr-kpi-number">{{ $clear }}</div><div class="sjr-kpi-label">Brief Cukup Jelas / Siap Teknis</div></div></div>
  <div class="sjr-kpi"><span class="sjr-kpi-icon">↺</span><div><div class="sjr-kpi-number">{{ $clarify }}</div><div class="sjr-kpi-label">Perlu Klarifikasi</div></div></div>
</div>
<div class="sjr-ready-summary"><span class="sjr-ready-dot"></span> {{ $readyCount }} pekerjaan siap masuk Kalkulasi HPP</div>
<div class="sjr-controls"><input id="jobSearch" class="sjr-search" placeholder="Cari nomor SJR, pekerjaan, atau klien..." aria-label="Cari pekerjaan"><select id="statusFilter" class="sjr-select" aria-label="Filter pemeriksaan"><option value="">Semua status</option><option value="Belum Diperiksa">Belum Diperiksa</option><option value="Brief Cukup Jelas">Brief Cukup Jelas</option><option value="Siap Hitung HPP">Siap Hitung HPP</option><option value="Perlu Klarifikasi">Perlu Klarifikasi</option></select></div>
<section class="sjr-card" style="padding:0;overflow:hidden"><div class="sjr-table-wrap"><table class="sjr-table"><thead><tr><th>No. SJR</th><th>Pekerjaan</th><th>Klien</th><th>Target Selesai</th><th>Pemeriksaan</th><th>Aksi</th></tr></thead><tbody id="jobRows">
@forelse($jobs as $j)
@php $status = isset($readyLookup[$j->job_number]) ? 'Siap Hitung HPP' : ($j->decision==='CLEAR' ? 'Brief Cukup Jelas' : ($j->decision==='CLARIFICATION' ? 'Perlu Klarifikasi' : 'Belum Diperiksa')); @endphp
<tr data-status="{{ $status }}"><td>{{ $j->job_number }}</td><td><strong>{{ $j->job_title }}</strong></td><td>{{ $j->client_name }}</td><td>{{ $j->target_date ? \Illuminate\Support\Carbon::parse($j->target_date)->format('d/m/Y') : '—' }}</td><td><span class="sjr-status {{ $status==='Siap Hitung HPP'?'ready':($j->decision==='CLEAR'?'clear':($j->decision==='CLARIFICATION'?'clarify':'')) }}">{{ $status }}</span></td><td><a class="sjr-icon-button" href="{{ route('sjr.production.technical.show',['jobNumber'=>$j->job_number]) }}" title="Buka detail pekerjaan" aria-label="Buka detail {{ $j->job_number }}">↗</a></td></tr>
@empty
<tr><td colspan="6" class="sjr-empty">Belum ada brief yang memenuhi syarat masuk Produksi.</td></tr>
@endforelse
</tbody></table></div><div id="visibleCount" style="padding:16px;color:#7487a2;font-size:11px">Menampilkan {{ $total }} pekerjaan</div></section>
</main></div>
<script>const s=document.getElementById('jobSearch'),f=document.getElementById('statusFilter'),rows=[...document.querySelectorAll('#jobRows tr[data-status]')];function filterJobs(){let n=0;for(const tr of rows){let ok=tr.textContent.toLowerCase().includes(s.value.trim().toLowerCase())&&(!f.value||tr.dataset.status===f.value);tr.hidden=!ok;if(ok)n++}document.getElementById('visibleCount').textContent='Menampilkan '+n+' dari '+rows.length+' pekerjaan'}s.addEventListener('input',filterJobs);f.addEventListener('change',filterJobs);</script>
</body></html>
