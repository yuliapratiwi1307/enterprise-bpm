@extends('layouts.app')

@section('title', 'Dashboard — Enterprise BPM')
@section('eyebrow', 'Workspace')
@section('page-title', 'Dashboard')
@section('page-description', 'Ringkasan alur kerja dan akses modul sesuai peran Anda.')

@section('content')
@if ($canAccessProd03Briefs ?? false)
{{-- UI-02 Modern Enterprise: Marketing-only dashboard; no DB queries or fake metrics. --}}
<section class="ui02-welcome" aria-labelledby="ui02-welcome-title">
  <div class="ui02-welcome-copy">
    <p class="ui02-eyebrow">MARKETING WORKSPACE <span class="ui02-dot">·</span> ENTERPRISE BPM</p>
    <h2 id="ui02-welcome-title">Selamat datang, {{ auth()->user()->name }}.</h2>
    <p>Kelola pekerjaan klien, koordinasi desain, dan distribusi Brief Marketing dalam satu tempat.</p>
    <div class="ui02-welcome-actions">
      <a class="ui02-btn ui02-btn-light" href="{{ route('prod03.briefs.index') }}">Buka Brief Marketing <span aria-hidden="true">↗</span></a>
      @if (Route::has('prod02.create'))
        <a class="ui02-btn ui02-btn-ghost" href="{{ route('prod02.create') }}">+ Permintaan Klien</a>
      @endif
    </div>
  </div>
  <div class="ui02-welcome-graphic" aria-hidden="true">
    <div class="ui02-orbit ui02-orbit-one"></div><div class="ui02-orbit ui02-orbit-two"></div>
    <div class="ui02-graphic-label">BPM <span>·</span> WORKFLOW</div>
    <div class="ui02-graphic-node">01 <span>Client</span></div>
    <div class="ui02-graphic-node">02 <span>Brief</span></div>
    <div class="ui02-graphic-node">03 <span>Distribusi</span></div>
  </div>
</section>

<section class="ui02-main-section" aria-labelledby="ui02-priority-title">
  <div class="ui02-section-heading"><div><p class="ui02-section-kicker">WORKSPACE</p><h2 id="ui02-priority-title">Mulai pekerjaan Anda</h2><p>Pilih proses sesuai kebutuhan. Semua tombol membuka modul Laravel yang sudah tersedia.</p></div></div>
  <div class="ui02-cards">
    <a class="ui02-work-card" href="{{ route('prod02.index') }}">
      <span class="ui02-card-icon ui02-icon-blue" aria-hidden="true">01</span>
      <span class="ui02-card-main"><strong>Permintaan Klien</strong><small>Daftar Client Brief, status pencatatan, dan kebutuhan baru.</small><span class="ui02-card-cta">Buka permintaan <span aria-hidden="true">→</span></span></span>
    </a>
    <a class="ui02-work-card" href="{{ route('prod03.briefs.index') }}">
      <span class="ui02-card-icon ui02-icon-violet" aria-hidden="true">02</span>
      <span class="ui02-card-main"><strong>Brief Marketing</strong><small>Lengkapi pekerjaan, tinjau kesiapan, dan koordinasikan desain.</small><span class="ui02-card-cta">Kelola brief <span aria-hidden="true">→</span></span></span>
    </a>
    @if (($canAccessProd04 ?? false) || ($canAccessProd04Admin ?? false))
      <a class="ui02-work-card" href="{{ route('prod04.index') }}">
        <span class="ui02-card-icon ui02-icon-teal" aria-hidden="true">03</span>
        <span class="ui02-card-main"><strong>Distribusi Brief</strong><small>Lihat dokumen resmi, penerima, dan acknowledgement.</small><span class="ui02-card-cta">Pantau distribusi <span aria-hidden="true">→</span></span></span>
      </a>
    @endif
  </div>
</section>

<div class="ui02-lower-grid">
  <section class="ui02-panel" aria-labelledby="ui02-flow-title">
    <div class="ui02-panel-header"><div><p class="ui02-section-kicker">PANDUAN PROSES</p><h2 id="ui02-flow-title">Alur kerja Marketing</h2></div><span class="ui02-panel-tag">PROD-02 — 04</span></div>
    <ol class="ui02-steps">
      <li><span class="ui02-step-number">1</span><span><strong>Catat permintaan klien</strong><small>Lengkapi Client Brief dan pastikan nomor resminya.</small></span></li>
      <li><span class="ui02-step-number">2</span><span><strong>Siapkan Brief Marketing</strong><small>Koordinasikan tugas desain dan validasi kesiapan.</small></span></li>
      <li><span class="ui02-step-number">3</span><span><strong>Ajukan dan pantau distribusi</strong><small>Penerima Produksi mengonfirmasi secara terpisah.</small></span></li>
    </ol>
  </section>
  <section class="ui02-panel ui02-attention" aria-labelledby="ui02-follow-title">
    <div class="ui02-panel-header"><div><p class="ui02-section-kicker">TINDAK LANJUT</p><h2 id="ui02-follow-title">Pemeriksaan distribusi</h2></div><span class="ui02-outline-icon" aria-hidden="true">↗</span></div>
    <p>Buka daftar distribusi untuk melihat status dokumen aktual. Konfirmasi penerimaan adalah wewenang penerima di Produksi.</p>
    @if (($canAccessProd04 ?? false) || ($canAccessProd04Admin ?? false))
      <a href="{{ route('prod04.index') }}" class="ui02-outline-button">Lihat distribusi Brief <span aria-hidden="true">→</span></a>
    @else
      <p class="ui02-note">Menu ini ditampilkan jika akses distribusi tersedia.</p>
    @endif
    <div class="ui02-data-note">Status pekerjaan dibaca dari modul terkait, bukan dihitung atau direkayasa pada Dashboard.</div>
  </section>
</div>

@else

    <div class="stack">
        <section class="dashboard-hero" style="padding:clamp(17px, 3vw, 26px)" aria-labelledby="welcome-heading">
            <div>
                <p class="eyebrow" style="color:#b9d2fb">Enterprise BPM</p>
                <h2 id="welcome-heading">Selamat datang, {{ auth()->user()->name }}.</h2>
                <p>Kelola proses bisnis dengan alur yang terstruktur, terdokumentasi, dan mudah ditelusuri.</p>
            </div>
            @if ($canAccessProduction)
                <a class="button" href="{{ route('prod02.index') }}">Buka Production <span aria-hidden="true">→</span></a>
            @endif
        </section>

        <section aria-labelledby="workflow-heading">
            <div class="section-heading">
                <div>
                    <h2 id="workflow-heading">Ringkasan alur PROD-02</h2>
                    <p>Tahapan penerimaan brief klien dari Draft hingga pencatatan resmi.</p>
                </div>
                @if ($canAccessProduction)
                    <a href="{{ route('prod02.index') }}">Lihat daftar brief <span aria-hidden="true">→</span></a>
                @endif
            </div>
            <div class="summary-grid">
                <article class="summary-card">
                    <p class="summary-label">Tahap 01</p>
                    <h3 class="summary-title">Draft</h3>
                    <p class="summary-copy">Simpan dan lengkapi informasi permintaan sebelum ditinjau.</p>
                </article>
                <article class="summary-card" data-tone="warning">
                    <p class="summary-label">Tahap 02</p>
                    <h3 class="summary-title">Pemeriksaan</h3>
                    <p class="summary-copy">Tinjau kandidat duplikasi dan tetapkan keputusan Marketing.</p>
                </article>
                <article class="summary-card" data-tone="success">
                    <p class="summary-label">Tahap 03</p>
                    <h3 class="summary-title">Recorded</h3>
                    <p class="summary-copy">Permintaan baru yang sah dicatat dengan nomor resmi.</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="quick-access-heading">
            <div class="section-heading">
                <div>
                    <h2 id="quick-access-heading">Akses cepat</h2>
                    <p>Modul yang tersedia untuk akun Anda.</p>
                </div>
            </div>
            @if ($canAccessProduction)
                <div class="quick-links">
                    <a class="quick-link" href="{{ route('prod02.index') }}">
                        <span class="nav-icon" aria-hidden="true">P</span>
                        <span>
                            <h3>Production · PROD-02</h3>
                            <p>Buka daftar, kelola Draft, tinjau kandidat, dan lihat permintaan tercatat.</p>
                        </span>
                    </a>
                    <a class="quick-link" href="{{ route('prod02.create') }}">
                        <span class="nav-icon" aria-hidden="true">+</span>
                        <span>
                            <h3>Buat Draft brief</h3>
                            <p>Mulai mencatat kebutuhan klien sebagai Draft baru.</p>
                        </span>
                    </a>
                </div>
            @else
                <div class="card card-subtle">
                    <p class="muted small" style="margin:0">Belum ada modul yang tersedia untuk role akun ini.</p>
                </div>
            @endif
        </section>
    </div>
@endif
@endsection
