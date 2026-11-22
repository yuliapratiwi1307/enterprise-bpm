@extends('layouts.app')
@section('title', 'Spesifikasi Material | Enterprise BPM')
@section('content')
<style>
.mm-page{max-width:1400px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr 1fr 1fr auto;gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:1180px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:top}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-rowform{display:grid;grid-template-columns:140px 130px 160px 120px 130px 95px 90px auto;gap:6px;align-items:center;min-width:1050px}.mm-rowform input,.mm-rowform select{width:100%;padding:7px;border:1px solid #cbd5e1;border-radius:7px;font-size:12px}.mm-actions{display:flex;gap:6px;align-items:center;margin-top:7px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}.mm-pagination{padding-top:14px}.mm-check{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:700;color:#475569;min-height:36px}@media(max-width:900px){.mm-page{padding:16px}.mm-form{grid-template-columns:1fr 1fr}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Spesifikasi Material</h1><p class="mm-muted">Kelola definisi atribut berdasarkan kategori material.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    <nav class="mm-nav" aria-label="Master Material">
        <a href="{{ route('mm05.materials.index') }}">Material</a>
        <a href="{{ route('mm05.categories.index') }}">Kategori</a>
        <a href="{{ route('mm05.units.index') }}">Satuan</a>
        <a class="active" href="{{ route('mm05.specifications.index') }}">Spesifikasi</a>
        <a href="{{ route('mm05.variants.index') }}">Varian</a>
    </nav>

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())
        <div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($canManage)
        <section class="mm-panel">
            <h2 style="font-size:16px;margin:0 0 14px">Tambah Definisi Atribut</h2>
            <form class="mm-form" method="POST" action="{{ route('mm05.specifications.store') }}">
                @csrf
                <div class="mm-field"><label for="new-category">Kategori</label><select id="new-category" name="category_id" required><option value="">Pilih kategori aktif</option>@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-code">Kode atribut</label><input id="new-code" name="code" maxlength="60" value="{{ old('code') }}" required></div>
                <div class="mm-field"><label for="new-label">Nama atribut</label><input id="new-label" name="label" maxlength="150" value="{{ old('label') }}" required></div>
                <div class="mm-field"><label for="new-type">Tipe nilai</label><select id="new-type" name="data_type" required><option value="text">Teks</option><option value="number">Angka</option><option value="boolean">Ya / Tidak</option></select></div>
                <div class="mm-field"><label for="new-unit">Satuan (untuk angka)</label><select id="new-unit" name="unit_id"><option value="">Tanpa satuan</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach</select></div>
                <div class="mm-field"><label for="new-order">Urutan</label><input id="new-order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', 0) }}"></div>
                <div><label class="mm-check"><input type="checkbox" name="is_required" value="1" @checked(old('is_required'))> Wajib</label><button class="mm-btn" type="submit">Simpan</button></div>
            </form>
            @if($categories->where('is_active', true)->isEmpty())<p class="mm-muted">Belum ada kategori aktif. Aktifkan kategori terlebih dahulu untuk menambahkan spesifikasi.</p>@endif
        </section>
    @endif

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><h2 style="font-size:16px;margin:0">Daftar Definisi Atribut</h2><span class="mm-muted">{{ $definitions->total() }} data</span></div>
        <div class="mm-table-wrap">
            <table class="mm-table">
                <thead><tr><th>Kategori</th><th>Kode</th><th>Nama</th><th>Tipe</th><th>Satuan</th><th>Wajib</th><th>Urutan</th><th>Status</th><th>Pengelolaan</th></tr></thead>
                <tbody>
                @forelse($definitions as $definition)
                    <tr>
                        <td>{{ $definition->category?->code }} — {{ $definition->category?->name }}</td>
                        <td><strong>{{ $definition->code }}</strong></td>
                        <td>{{ $definition->label }}</td>
                        <td>{{ ['text' => 'Teks', 'number' => 'Angka', 'boolean' => 'Ya / Tidak'][$definition->data_type] ?? $definition->data_type }}</td>
                        <td>{{ $definition->unit?->code ?? '—' }}</td>
                        <td>{{ $definition->is_required ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $definition->sort_order }}</td>
                        <td><span class="mm-status {{ $definition->is_active ? 'on' : 'off' }}">{{ $definition->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            @if($canManage && $definition->is_active)
                                <form class="mm-rowform" method="POST" action="{{ route('mm05.specifications.update', $definition) }}">
                                    @csrf @method('PUT')
                                    <select name="category_id" aria-label="Kategori" required>
                                        @if(! $definition->category?->is_active)<option value="{{ $definition->category_id }}" selected>{{ $definition->category?->name }} (nonaktif)</option>@endif
                                        @foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}" @selected($definition->category_id == $category->id)>{{ $category->code }} — {{ $category->name }}</option>@endforeach
                                    </select>
                                    <input name="code" value="{{ $definition->code }}" maxlength="60" aria-label="Kode atribut" required>
                                    <input name="label" value="{{ $definition->label }}" maxlength="150" aria-label="Nama atribut" required>
                                    <select name="data_type" aria-label="Tipe nilai" required>@foreach(['text' => 'Teks', 'number' => 'Angka', 'boolean' => 'Ya / Tidak'] as $type => $label)<option value="{{ $type }}" @selected($definition->data_type === $type)>{{ $label }}</option>@endforeach</select>
                                    <select name="unit_id" aria-label="Satuan">
                                        <option value="">Tanpa satuan</option>
                                        @if($definition->unit && ! $definition->unit->is_active)<option value="{{ $definition->unit_id }}" selected>{{ $definition->unit->code }} — {{ $definition->unit->name }} (nonaktif; dipertahankan)</option>@endif
                                        @foreach($units as $unit)<option value="{{ $unit->id }}" @selected($definition->unit_id == $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>@endforeach
                                    </select>
                                    <input name="sort_order" type="number" min="0" max="65535" value="{{ $definition->sort_order }}" aria-label="Urutan">
                                    <label class="mm-check"><input type="checkbox" name="is_required" value="1" @checked($definition->is_required)> Wajib</label>
                                    <button class="mm-btn secondary" type="submit">Update</button>
                                </form>
                                <form class="mm-actions" method="POST" action="{{ route('mm05.specifications.deactivate', $definition) }}" onsubmit="return confirm('Nonaktifkan definisi atribut ini? Data nilai yang sudah tersimpan tetap dipertahankan.');">
                                    @csrf<button class="mm-btn danger" type="submit">Nonaktifkan</button>
                                </form>
                            @elseif(! $canManage)
                                <span class="mm-muted">Hanya baca.</span>
                            @else
                                <span class="mm-muted">Nonaktif — tetap tersimpan sebagai referensi.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align:center;padding:26px;color:#64748b">Belum ada definisi atribut.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mm-pagination">{{ $definitions->links() }}</div>
    </section>
</div>
@endsection
