<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Enterprise BPM')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=ui01">
    {{-- UI-03: route-scoped visual styling for PROD-02 --}}
    @if(request()->routeIs('prod02.*'))
        <link rel="stylesheet" href="{{ asset('css/ui03_prod02.css') }}">
        <link rel="stylesheet" href="{{ asset('css/prod02_header_v1.css') }}?v=1">
    @elseif(request()->routeIs('prod03.briefs.index'))
        <link rel="stylesheet" href="{{ asset('css/prod02_header_v1.css') }}?v=1">
    @endif
</head>
<body>
<div class="app-shell">
    <aside id="sms-sidebar" class="sidebar" aria-label="Navigasi utama">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><rect x="2" y="2" width="12" height="12" rx="3" fill="currentColor" opacity=".95"/><rect x="18" y="2" width="12" height="12" rx="3" fill="currentColor" opacity=".52"/><rect x="2" y="18" width="12" height="12" rx="3" fill="currentColor" opacity=".52"/><rect x="18" y="18" width="12" height="12" rx="3" fill="currentColor" opacity=".95"/></svg></span>
            <span>
                <span class="brand-name">Enterprise BPM</span>
                <span class="brand-caption">Operations Workspace</span>
            </span>
        </a>

        <nav aria-label="Modul">
            <p class="nav-label">MENU UTAMA</p>
            <div class="side-nav">
                <a class="nav-link" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg></span>
                    <span>Dashboard</span>
                </a>
                @if(auth()->user()?->roles()->whereIn('code', ['ADMIN_BPM', 'PROCUREMENT'])->exists())
                    <div class="mm05-nav-group">
                        <a class="nav-link mm05-nav-parent @if(request()->is('material-master*')) mm05-nav-parent-active @endif" href="{{ route('mm05.materials.index') }}">
                            <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M5 7V4h14v3M5 7v13h14V7M9 11h6M9 15h6"/></svg></span>
                            <span>Master Material</span>
                        </a>
                        <div class="mm05-subnav" role="group" aria-label="Menu Master Material">
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.materials.index') }}"@if(request()->is('material-master') || request()->is('material-master/materials*') || request()->is('material-master/variants*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v9"/></svg></span>
                                <span>Material</span>
                            </a>
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.categories.index') }}"@if(request()->is('material-master/categories*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h7l2 2h9v10H3z"/><path d="M3 8V5a1 1 0 0 1 1-1h6l2 2"/></svg></span>
                                <span>Kategori</span>
                            </a>
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.templates.index') }}"@if(request()->is('material-master/specification-templates*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5h16M4 12h10M4 17.5h16"/><path d="M18 12h2v5.5h-2z"/></svg></span>
                                <span>Template</span>
                            </a>
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.units.index') }}"@if(request()->is('material-master/units*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18v10H3z"/><path d="M7 7v4M11 7v3M15 7v4M19 7v3"/></svg></span>
                                <span>Satuan</span>
                            </a>
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.suppliers.index') }}"@if(request()->is('material-master/suppliers*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V8l9-5 9 5v13H3Z"/><path d="M8 21v-6h8v6M8 10h.01M12 10h.01M16 10h.01"/></svg></span>
                                <span>Supplier</span>
                            </a>
                            <a class="nav-link mm05-subnav-link" href="{{ route('mm05.prices.index') }}"@if(request()->is('material-master/material-prices*') || request()->is('material-master/supplier-quotes*')) aria-current="page"@endif>
                                <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13 12 21 3 12V4h8l9 9Z"/><circle cx="7.5" cy="8.5" r="1"/></svg></span>
                                <span>Harga Material</span>
                            </a>
                        </div>
                    </div>
                @endif
                @if (($canAccessProduction ?? request()->routeIs('prod02.*')) || ($canAccessProd03 ?? false) || ($canAccessProd04 ?? false))
                    <a class="nav-link" href="{{ ($canAccessProd03Briefs ?? false) ? route('prod02.index') : route('prod04.index') }}" @if(request()->routeIs('prod02.*')) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span>
                        <span>{{ ($canAccessProd03Briefs ?? false) ? 'Permintaan Klien' : 'Production' }}</span>
                    </a>
                @endif
                @if ($canAccessProd03Briefs ?? false)
                    <a class="nav-link" href="{{ route('prod03.briefs.index') }}" @if(request()->routeIs('prod03.briefs.*') || (request()->routeIs('prod03.design-requests.*') && ($canAccessProd03Briefs ?? false))) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h8l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg></span>
                        <span>Brief Marketing</span>
                    </a>
                @endif
                @if ($canAccessProd03TaskPool ?? false)
                    <a class="nav-link" href="{{ route('prod03.task-pool.index') }}" @if(request()->routeIs('prod03.task-pool.*') || (request()->routeIs('prod03.design-requests.*') && ($canAccessProd03TaskPool ?? false))) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 12-12 4 4L7 21H3zM13 7l4 4M15 19h6"/></svg></span>
                        <span>Task Pool Desain</span>
                    </a>
                @endif
                @unless(request()->routeIs('prod02.*'))
                @if ($canAccessProd03 ?? false)
                    <a class="nav-link" href="{{ route('prod03.notifications.index') }}" @if(request()->routeIs('prod03.notifications.*')) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
                        <span>Notifikasi @if(($prod03UnreadNotificationCount ?? 0) > 0)<span class="status-badge status-review">{{ $prod03UnreadNotificationCount }}</span>@endif</span>
                    </a>
                @endif
                @endunless
                @if (($canAccessProd04 ?? false) || ($canAccessProd04Admin ?? false))
                    <a class="nav-link" href="{{ route('prod04.index') }}" @if(request()->routeIs('prod04.*')) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4z"/></svg></span>
                        <span>Distribusi Brief</span>
                    </a>
                @endif
            </div>
        </nav>

        <div class="sidebar-note">
            <span class="workspace-indicator" aria-hidden="true"></span>
            <div><strong>Workspace aktif</strong><span>Enterprise BPM · Production</span></div>
        </div>
    </aside>

    <div class="workspace">
            {{-- SMS_PROD02_HEADER_V1: isolated PROD-02 two-tier header --}}
            @if(request()->routeIs('prod02.*') || request()->routeIs('prod03.briefs.index'))
            <header class="sms-header-v1" aria-label="Header {{ request()->routeIs('prod03.briefs.index') ? 'Brief Marketing' : 'Permintaan Klien' }}">
                <div class="sms-header-v1__top">
                    <button class="sms-header-v1__menu" type="button" aria-label="Tampilkan atau sembunyikan menu utama" aria-expanded="true" aria-controls="sms-sidebar" onclick="(function(btn){var sidebar=document.getElementById('sms-sidebar'); if(!sidebar)return; var hidden=sidebar.classList.toggle('sms-sidebar-collapsed'); sidebar.closest('.app-shell').classList.toggle('sms-app-expanded',hidden); btn.setAttribute('aria-expanded',String(!hidden));})(this)">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <a class="sms-header-v1__directory" href="{{ request()->routeIs('prod03.briefs.index') ? route('prod03.briefs.index') : route('prod02.index') }}" title="Buka daftar {{ request()->routeIs('prod03.briefs.index') ? 'Brief Marketing' : 'permintaan klien' }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
                        <span>{{ request()->routeIs('prod03.briefs.index') ? 'Buka daftar Brief Marketing' : 'Buka daftar permintaan klien' }}</span>
                    </a>
                    <div class="sms-header-v1__right">
                        @if($canAccessProd03 ?? false)
                        <a class="sms-header-v1__bell" href="{{ route('prod03.notifications.index') }}" aria-label="Notifikasi, {{ (int) ($prod03UnreadNotificationCount ?? 0) }} belum dibaca" title="Buka notifikasi">
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            @if(($prod03UnreadNotificationCount ?? 0) > 0)
                                <span class="sms-header-v1__count">{{ $prod03UnreadNotificationCount }}</span>
                            @endif
                        </a>
                        @endif
                        <details class="sms-header-v1__account">
                            <summary>
                                <span class="sms-header-v1__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="sms-header-v1__identity"><strong>{{ auth()->user()->name }}</strong><small>@if($canAccessProd03TaskPool ?? false)Designer @elseif($canAccessProd03Briefs ?? false)Marketing @elseif($canAccessProd04 ?? false)Produksi @else Workspace aktif @endif</small></span>
                                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 9 6 6 6-6"/></svg>
                            </summary>
                            <div class="sms-header-v1__account-menu">
                                <span>Akun masuk</span>
                                <strong>{{ auth()->user()->name }}</strong>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">Keluar</button>
                                </form>
                            </div>
                        </details>
                    </div>
                </div>
                <div class="sms-header-v1__page">
                    <div class="sms-header-v1__heading">
                        <p class="sms-header-v1__crumb"><a href="{{ request()->routeIs('prod03.briefs.index') ? route('prod03.briefs.index') : route('prod02.index') }}">{{ request()->routeIs('prod03.briefs.index') ? 'Brief Marketing' : 'Permintaan Klien' }}</a><span aria-hidden="true">›</span><span>@yield('page-title', 'Permintaan Klien')</span></p>
                        <h1>@yield('page-title', 'Permintaan Klien')</h1>
                        @hasSection('page-description')<p class="sms-header-v1__desc">@yield('page-description')</p>@endif
                    </div>
                    @if (request()->routeIs('prod03.briefs.index'))
                        <a class="sms-header-v1__back" href="{{ route('prod03.briefs.index', ['tab' => 'incoming']) }}"><span aria-hidden="true">→</span> Permintaan Masuk</a>
                    @elseif (request()->is('production/briefs'))
                        <a class="sms-header-v1__back" href="{{ route('prod02.create') }}"><span aria-hidden="true">+</span> Permintaan Baru</a>
                    @else
                        <a class="sms-header-v1__back" href="{{ route('prod02.index') }}"><span aria-hidden="true">←</span> Kembali ke Daftar Permintaan</a>
                    @endif
                </div>
            </header>
            @else
        <header class="topbar">
            <div class="topbar-title">
                <p class="eyebrow"><span class="crumb-root">Enterprise BPM</span><span class="crumb-separator" aria-hidden="true">/</span>@yield('eyebrow', 'Workspace')</p>
                <h1 class="page-title">@yield('page-title', 'Workspace')</h1>
                @hasSection('page-description')
                    <p class="page-description">@yield('page-description')</p>
                @endif
            </div>
            <div class="user-menu" aria-label="Informasi pengguna">
                <span class="user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-meta">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">@if($canAccessProd03TaskPool ?? false)Designer @elseif($canAccessProd03Briefs ?? false)Marketing @elseif($canAccessProd04 ?? false)Produksi @else Workspace aktif @endif</span>
                </span>
                <form class="logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-secondary btn-inline" type="submit">Logout</button>
                </form>
            </div>
        </header>
            @endif

        <main class="page-content">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
