@extends('layouts.app')
@section('title', 'Supplier Material | Enterprise BPM')
@section('content')
<style>
.mm-page{max-width:1400px;margin:0 auto;padding:28px;color:#172033}.mm-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}.mm-head h1{margin:0;font-size:26px}.mm-muted{color:#64748b;font-size:13px}.mm-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-top:18px}.mm-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;align-items:end}.mm-field label{display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px}.mm-field input,.mm-field textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;font:inherit;font-size:13px;background:#fff}.mm-field textarea{min-height:40px;resize:vertical}.mm-btn{border:0;border-radius:8px;padding:9px 13px;background:#1e3a5f;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;font-size:12px;display:inline-block}.mm-btn.secondary{background:#e8eef5;color:#1e3a5f}.mm-btn.danger{background:#9f3030}.mm-alert{padding:11px 14px;border-radius:9px;background:#eaf7ee;color:#21633a;margin-top:15px;font-size:13px}.mm-error{padding:11px 14px;border-radius:9px;background:#fff1f0;color:#9f3030;margin-top:15px;font-size:13px}.mm-table-wrap{overflow:auto}.mm-table{width:100%;border-collapse:collapse;min-width:1050px}.mm-table th,.mm-table td{padding:11px;border-bottom:1px solid #edf1f5;text-align:left;font-size:13px;vertical-align:top}.mm-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase}.mm-status{display:inline-block;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.mm-status.on{background:#e8f6ee;color:#207044}.mm-status.off{background:#f1f3f6;color:#667085}.mm-filter{display:flex;gap:8px;align-items:end;flex-wrap:wrap}.mm-rowform{display:grid;grid-template-columns:100px 150px 140px 130px 180px;gap:6px;align-items:end}.mm-rowform input,.mm-rowform textarea{width:100%;padding:7px;border:1px solid #cbd5e1;border-radius:7px;font:inherit;font-size:12px}.mm-actions{display:flex;gap:6px;align-items:center;margin-top:7px}.mm-pagination{padding-top:14px}.mm-nav{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.mm-nav a{padding:8px 12px;border-radius:8px;background:#e8eef5;color:#1e3a5f;text-decoration:none;font-size:12px;font-weight:700}.mm-nav a.active{background:#1e3a5f;color:#fff}@media(max-width:900px){.mm-page{padding:16px}.mm-form,.mm-rowform{grid-template-columns:1fr 1fr}}

/* Supplier UI: responsive table and accessible native dialog */
.mm-page{max-width:1220px;width:100%;box-sizing:border-box;padding:28px 24px 48px}
.mm-panel{box-shadow:0 2px 12px rgba(15,35,65,.025);min-width:0}
.mm-form>*{min-width:0}
.mm-filter .mm-field{min-width:0}
.mm-filter select{border:1px solid #cbd5e1;border-radius:8px;padding:9px 10px;background:#fff;font:inherit;font-size:13px;min-height:38px}
.mm-table-wrap{overflow-x:auto;max-width:100%}
.mm-table{min-width:0;width:100%;table-layout:fixed}
.mm-table th,.mm-table td{padding:14px 10px;overflow-wrap:anywhere;word-break:normal}
.mm-table th:nth-child(1){width:21%}
.mm-table th:nth-child(2){width:18%}
.mm-table th:nth-child(3){width:16%}
.mm-table th:nth-child(4){width:19%}
.mm-table th:nth-child(5){width:10%}
.mm-table th:nth-child(6){width:16%}
.mm-table td:first-child strong{color:#203c64}
.supplier-action-group{display:flex;flex-wrap:wrap;gap:7px;align-items:center}
.supplier-action-group form{margin:0}
.supplier-action-group .mm-btn{white-space:nowrap}
.supplier-dialog{position:fixed;inset:0;margin:auto;border:1px solid #d9e3f0;border-radius:16px;padding:0;width:min(560px,calc(100vw - 32px));max-height:calc(100dvh - 36px);overflow-y:auto;background:#fff;color:#172033;box-shadow:0 24px 80px rgba(12,30,58,.3)}
.supplier-dialog::backdrop{background:rgba(12,28,52,.58)}
.supplier-dialog-head{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;padding:22px 24px 18px;border-bottom:1px solid #e7edf5}
.supplier-dialog-head h2{font-size:19px;margin:0 0 6px}
.supplier-dialog-head p{font-size:12px;color:#64748b;margin:0}
.supplier-dialog-close{border:0;background:#eef2f7;color:#334155;border-radius:8px;width:32px;height:32px;font-size:22px;cursor:pointer;flex-shrink:0}
.supplier-dialog-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;padding:22px 24px}
.supplier-dialog-grid .supplier-full{grid-column:1/-1}
.supplier-dialog-footer{display:flex;justify-content:flex-end;gap:9px;padding:16px 24px 22px;border-top:1px solid #e7edf5}
.supplier-dialog .mm-field input,.supplier-dialog .mm-field textarea{box-sizing:border-box}
@media(max-width:1050px){.mm-page{padding:20px 16px 36px}.mm-table th,.mm-table td{padding:11px 7px;font-size:12px}.supplier-action-group .mm-btn{font-size:11px;padding:8px 9px}}
@media(max-width:700px){.mm-form{grid-template-columns:1fr}.mm-page{padding:16px 12px 28px}.mm-panel{padding:14px}.mm-table{table-layout:auto;min-width:640px}.supplier-dialog-grid{grid-template-columns:1fr}.supplier-dialog-grid .supplier-full{grid-column:auto}.supplier-dialog-head,.supplier-dialog-grid,.supplier-dialog-footer{padding-left:17px;padding-right:17px}}
</style>

<div class="mm-page">
    <div class="mm-head">
        <div><h1>Supplier Material</h1><p class="mm-muted">Daftar pemasok dan informasi kontak untuk Master Material.</p></div>
        <a class="mm-btn secondary" href="{{ route('mm05.workspace') }}">Kembali ke Workspace</a>
    </div>
    @if(session('message'))<div class="mm-alert">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="mm-error"><strong>Periksa kembali data:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($canManage)
        <section class="mm-panel">
            <h2 style="font-size:16px;margin:0 0 14px">Tambah Supplier</h2>
            <p class="mm-muted">Kode Supplier dibuat otomatis saat data disimpan.</p>
            <form class="mm-form" method="POST" action="{{ route('mm05.suppliers.store') }}">
                @csrf
                <div class="mm-field"><label for="new-name">Nama supplier</label><input id="new-name" name="name" maxlength="255" value="{{ old('name') }}" required></div>
                <div class="mm-field"><label for="new-contact-name">Nama kontak</label><input id="new-contact-name" name="contact_name" maxlength="150" value="{{ old('contact_name') }}"></div>
                <div class="mm-field"><label for="new-phone">Telepon</label><input id="new-phone" name="phone" maxlength="60" value="{{ old('phone') }}"></div>
                <div class="mm-field"><label for="new-email">Email</label><input id="new-email" name="email" type="email" maxlength="255" value="{{ old('email') }}"></div>
                <div class="mm-field"><label for="new-notes">Catatan</label><textarea id="new-notes" name="notes">{{ old('notes') }}</textarea></div>
                <button class="mm-btn" type="submit">Simpan Supplier</button>
            </form>
        </section>
    @endif

    <section class="mm-panel">
        <form class="mm-filter" method="GET" action="{{ route('mm05.suppliers.index') }}">
            <div class="mm-field" style="min-width:260px"><label for="q">Cari kode atau nama supplier</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Masukkan kode/nama supplier"></div>
            <div class="mm-field"><label for="status">Status</label><select id="status" name="status"><option value="">Semua</option><option value="active" @selected(request('status') === 'active')>Aktif</option><option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option></select></div>
            <button class="mm-btn" type="submit">Terapkan</button>
            <a class="mm-btn secondary" href="{{ route('mm05.suppliers.index') }}">Reset</a>
        </form>
    </section>

    <section class="mm-panel">
        <div class="mm-head" style="margin-bottom:12px"><h2 style="font-size:16px;margin:0">Daftar Supplier</h2><span class="mm-muted">{{ $records->total() }} data</span></div>
        <div class="mm-table-wrap">
            <table class="mm-table">
                <thead><tr><th>Kode / Nama</th><th>Kontak</th><th>Telepon</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    <tr>
                        <td><strong>{{ $record->code }}</strong><br>{{ $record->name }}</td>
                        <td>{{ $record->contact_name ?: '—' }}@if($record->notes)<div class="mm-muted">{{ $record->notes }}</div>@endif</td>
                        <td>{{ $record->phone ?: '—' }}</td>
                        <td>{{ $record->email ?: '—' }}</td>
                        <td><span class="mm-status {{ $record->is_active ? 'on' : 'off' }}">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td>
                            @if($canManage && $record->is_active)
                                <div class="supplier-action-group">
                                    <button class="mm-btn secondary" type="button" onclick="document.getElementById('supplier-edit-{{ $record->id }}').showModal()">Edit</button>
                                    <form method="POST" action="{{ route('mm05.suppliers.deactivate', $record) }}" onsubmit="return confirm('Nonaktifkan supplier? Data dan histori penawaran tetap tersimpan.');">
                                        @csrf
                                        <button class="mm-btn danger" type="submit">Nonaktifkan</button>
                                    </form>
                                </div>
                                <dialog class="supplier-dialog" id="supplier-edit-{{ $record->id }}" aria-labelledby="supplier-title-{{ $record->id }}">
                                    <div class="supplier-dialog-head">
                                        <div>
                                            <h2 id="supplier-title-{{ $record->id }}">Edit Supplier</h2>
                                            <p>Kode {{ $record->code }} · Kode supplier tidak dapat diubah.</p>
                                        </div>
                                        <button type="button" class="supplier-dialog-close" aria-label="Tutup" onclick="this.closest('dialog').close()">×</button>
                                    </div>
                                    <form method="POST" action="{{ route('mm05.suppliers.update', $record) }}">
                                        @csrf @method('PUT')
                                        <div class="supplier-dialog-grid">
                                            <div class="mm-field supplier-full"><label for="edit-name-{{ $record->id }}">Nama supplier</label><input id="edit-name-{{ $record->id }}" name="name" value="{{ $record->name }}" maxlength="255" required></div>
                                            <div class="mm-field"><label for="edit-contact-{{ $record->id }}">Nama kontak</label><input id="edit-contact-{{ $record->id }}" name="contact_name" value="{{ $record->contact_name }}" maxlength="150"></div>
                                            <div class="mm-field"><label for="edit-phone-{{ $record->id }}">Telepon</label><input id="edit-phone-{{ $record->id }}" name="phone" value="{{ $record->phone }}" maxlength="60"></div>
                                            <div class="mm-field supplier-full"><label for="edit-email-{{ $record->id }}">Email</label><input id="edit-email-{{ $record->id }}" name="email" type="email" value="{{ $record->email }}" maxlength="255"></div>
                                            <div class="mm-field supplier-full"><label for="edit-notes-{{ $record->id }}">Catatan</label><textarea id="edit-notes-{{ $record->id }}" name="notes" rows="3">{{ $record->notes }}</textarea></div>
                                        </div>
                                        <div class="supplier-dialog-footer">
                                            <button class="mm-btn secondary" type="button" onclick="this.closest('dialog').close()">Batal</button>
                                            <button class="mm-btn" type="submit">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </dialog>
                            @elseif($canManage)
                                <span class="mm-muted">Nonaktif — data tetap tersimpan sebagai referensi.</span>
                                <form class="mm-actions" method="POST" action="{{ route('mm05.suppliers.activate', $record) }}">
                                    @csrf<button class="mm-btn secondary" type="submit">Aktifkan kembali</button>
                                </form>
                            @else
                                <span class="mm-muted">Hanya baca.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:26px;color:#64748b">Belum ada supplier yang cocok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mm-pagination">{{ $records->links() }}</div>
    </section>
</div>
@endsection
