@extends('layouts.app')
@section('title', 'Kategori Material | Enterprise BPM')
@section('content')

<style>
.mm-page{max-width:1400px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:820px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:middle}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-rowform{display:grid;grid-template-columns:110px minmax(150px,1fr) minmax(140px,1fr) auto;gap:6px;align-items:center}.mm-rowform input,.mm-rowform select{width:100%;padding:7px;border:1px solid #cbd5e1;border-radius:7px;font-size:12px}.mm-filter{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.mm-actions{display:flex;gap:6px;align-items:center}.mm-pagination{padding-top:14px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}@media(max-width:900px){.mm-page{padding:16px}.mm-form{grid-template-columns:1fr 1fr}.mm-rowform{grid-template-columns:1fr 1fr}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Kategori Material</h1><p class="mm-muted">Kelola kategori dan hierarki pengelompokan material.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    <nav class="mm-nav" aria-label="Master data"><a class="active" href="{{ route('mm05.categories.index') }}">Kategori</a><a href="{{ route('mm05.units.index') }}">Satuan</a></nav>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($canManage)
    <section class="mm-panel">
        <h2 style="font-size:16px;margin:0 0 14px">Tambah Kategori</h2>
        <form class="mm-form" method="POST" action="{{ route('mm05.categories.store') }}">
            @csrf
            <div class="mm-field"><label for="new-code">Kode kategori</label><input id="new-code" name="code" maxlength="40" value="{{ old('code') }}" required></div>
            <div class="mm-field"><label for="new-name">Nama kategori</label><input id="new-name" name="name" maxlength="150" value="{{ old('name') }}" required></div>
            <div class="mm-field"><label for="new-parent">Kategori induk (opsional)</label><select id="new-parent" name="parent_id"><option value="">Tanpa induk</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>@endforeach</select></div>
            <button class="mm-btn" type="submit">Simpan</button>
        </form>
    </section>
    @endif

    <section class="mm-panel">
        <form class="mm-filter" method="GET" action="{{ route('mm05.categories.index') }}">
            <div class="mm-field" style="min-width:260px"><label for="q">Cari kode atau nama</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Masukkan kode/nama kategori"></div>
            <div class="mm-field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select></div>
            <button class="mm-btn" type="submit">Terapkan</button><a class="mm-btn secondary" href="{{ route('mm05.categories.index') }}">Reset</a>
        </form>
    </section>

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><h2 style="font-size:16px;margin:0">Daftar Kategori</h2><span class="mm-muted">{{ $records->total() }} data</span></div>
        <div class="mm-table-wrap"><table class="mm-table">
            <thead><tr><th>Kode</th><th>Nama kategori</th><th>Induk</th><th>Status</th><th>Perbarui / Aksi</th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td><strong>{{ $record->code }}</strong></td><td>{{ $record->name }}</td>
                    <td>{{ $record->parent?->name ?? '—' }}</td>
                    <td><span class="mm-status {{ $record->is_active ? 'on' : 'off' }}">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                    @if($canManage)
                    @if($record->is_active)
                        <form class="mm-rowform" method="POST" action="{{ route('mm05.categories.update', $record->id) }}">
                            @csrf @method('PUT')
                            <input name="code" value="{{ $record->code }}" maxlength="40" aria-label="Kode kategori" required>
                            <input name="name" value="{{ $record->name }}" maxlength="150" aria-label="Nama kategori" required>
                            <select name="parent_id" aria-label="Kategori induk"><option value="">Tanpa induk</option>@foreach($parents as $parent)@if($parent->id != $record->id)<option value="{{ $parent->id }}" @selected($record->parent_id == $parent->id)>{{ $parent->code }} — {{ $parent->name }}</option>@endif @endforeach</select>
                            <button class="mm-btn secondary" type="submit">Update</button>
                        </form>
                        <form method="POST" action="{{ route('mm05.categories.deactivate', $record->id) }}" style="margin-top:7px" onsubmit="return confirm('Nonaktifkan kategori ini? Data tidak akan dihapus.');">@csrf<button class="mm-btn danger" type="submit">Nonaktifkan</button></form>
                    @else <span class="mm-muted">Data nonaktif — tetap tersimpan sebagai referensi.</span> @endif
                    @else <span class="mm-muted">Hanya baca.</span> @endif
                    </td>
                </tr>
            @empty<tr><td colspan="5" style="text-align:center;padding:26px;color:#64748b">Belum ada kategori yang cocok.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="mm-pagination">{{ $records->links() }}</div>
    </section>
</div>
@endsection
