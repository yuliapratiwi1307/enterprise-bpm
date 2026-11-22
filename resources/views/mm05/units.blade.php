@extends('layouts.app')
@section('title', 'Satuan Material | Enterprise BPM')
@section('content')

<style>
.mm-page{max-width:1400px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field select{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:820px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:middle}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-rowform{display:grid;grid-template-columns:110px minmax(130px,1fr) minmax(120px,1fr) minmax(180px,1.3fr) auto;gap:6px;align-items:center}.mm-rowform input,.mm-rowform select{width:100%;padding:7px;border:1px solid #cbd5e1;border-radius:7px;font-size:12px}.mm-filter{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.mm-actions{display:flex;gap:6px;align-items:center}.mm-pagination{padding-top:14px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}@media(max-width:900px){.mm-page{padding:16px}.mm-form{grid-template-columns:1fr 1fr}.mm-rowform{grid-template-columns:1fr 1fr}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Satuan Material</h1><p class="mm-muted">Kelola unit pengukuran dan dimensi satuan material.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    

    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($canManage)
    <section class="mm-panel">
        <h2 style="font-size:16px;margin:0 0 14px">Tambah Satuan</h2>
        <form class="mm-form" method="POST" action="{{ route('mm05.units.store') }}">
            @csrf
            
            <div class="mm-field"><label for="new-name">Nama satuan</label><input id="new-name" name="name" maxlength="100" value="{{ old('name') }}" required></div>
            <div class="mm-field"><label for="new-dimension">Dimensi</label><select id="new-dimension" name="dimension"><option value="">Tidak ditentukan</option>@foreach(['count'=>'Jumlah','length'=>'Panjang','area'=>'Luas','volume'=>'Volume','mass'=>'Massa'] as $key=>$label)<option value="{{ $key }}" @selected(old('dimension')===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="mm-field"><label for="new-unit-usage">Penggunaan satuan</label><select id="new-unit-usage" name="unit_usage" required><option value="specification" @selected(old('unit_usage', 'both') === 'specification')>Satuan Dasar / Spesifikasi saja</option><option value="price" @selected(old('unit_usage', 'both') === 'price')>Satuan Harga saja</option><option value="both" @selected(old('unit_usage', 'both') === 'both')>Keduanya</option></select></div>
            <button class="mm-btn" type="submit">Simpan</button>
        </form>
    </section>
    @endif

    <section class="mm-panel">
        <form class="mm-filter" method="GET" action="{{ route('mm05.units.index') }}">
            <div class="mm-field" style="min-width:260px"><label for="q">Cari kode, nama, atau dimensi</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Masukkan kata pencarian"></div>
            <div class="mm-field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua</option><option value="active" @selected(request('status')==='active')>Aktif</option><option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option></select></div>
            <button class="mm-btn" type="submit">Terapkan</button><a class="mm-btn secondary" href="{{ route('mm05.units.index') }}">Reset</a>
        </form>
    </section>

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><h2 style="font-size:16px;margin:0">Daftar Satuan</h2><span class="mm-muted">{{ $records->total() }} data</span></div>
        <div class="mm-table-wrap"><table class="mm-table">
            <thead><tr><th>Kode</th><th>Nama satuan</th><th>Dimensi</th><th>Penggunaan</th><th>Status</th><th>Perbarui / Aksi</th></tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td><strong>{{ $record->code }}</strong></td><td>{{ $record->name }}</td>
                    <td>{{ ['count'=>'Jumlah','length'=>'Panjang','area'=>'Luas','volume'=>'Volume','mass'=>'Massa'][$record->dimension] ?? '—' }}</td>
                    <td>
                        @if($record->is_specification_unit && $record->usage_group !== null && $record->usage_group !== 'specification') Keduanya
                        @elseif($record->is_specification_unit) Spesifikasi
                        @else Harga @endif
                    </td>
                    <td><span class="mm-status {{ $record->is_active ? 'on' : 'off' }}">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td>
                    @if($canManage)
                    @if($record->is_active)
                        <form class="mm-rowform" method="POST" action="{{ route('mm05.units.update', $record->id) }}">
                            @csrf @method('PUT')
                            <input value="{{ $record->code }}" aria-label="Kode satuan otomatis" title="Kode satuan tidak dapat diubah" readonly>
                            <input name="name" value="{{ $record->name }}" maxlength="100" aria-label="Nama satuan" required>
                            <select name="dimension" aria-label="Dimensi"><option value="">Tidak ditentukan</option>@foreach(['count'=>'Jumlah','length'=>'Panjang','area'=>'Luas','volume'=>'Volume','mass'=>'Massa'] as $key=>$label)<option value="{{ $key }}" @selected($record->dimension===$key)>{{ $label }}</option>@endforeach</select>
                            <select name="unit_usage" aria-label="Penggunaan satuan" required>
                                @php($currentUsage = $record->is_specification_unit ? (($record->usage_group === null || $record->usage_group === 'specification') ? 'specification' : 'both') : 'price')
                                <option value="specification" @selected($currentUsage === 'specification')>Spesifikasi saja</option>
                                <option value="price" @selected($currentUsage === 'price')>Harga saja</option>
                                <option value="both" @selected($currentUsage === 'both')>Keduanya</option>
                            </select>
                            <button class="mm-btn secondary" type="submit">Update</button>
                        </form>
                        <form method="POST" action="{{ route('mm05.units.deactivate', $record->id) }}" style="margin-top:7px" onsubmit="return confirm('Nonaktifkan satuan ini? Data tidak akan dihapus.');">@csrf<button class="mm-btn danger" type="submit">Nonaktifkan</button></form>
                    @else <span class="mm-muted">Data nonaktif — tetap tersimpan sebagai referensi.</span> @endif
                    @else <span class="mm-muted">Hanya baca.</span> @endif
                    </td>
                </tr>
            @empty<tr><td colspan="6" style="text-align:center;padding:26px;color:#64748b">Belum ada satuan yang cocok.</td></tr>@endforelse
            </tbody>
        </table></div>
        <div class="mm-pagination">{{ $records->links() }}</div>
    </section>
</div>
@endsection
