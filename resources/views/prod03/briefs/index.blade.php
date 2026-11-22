@extends('layouts.app')

@section('title', 'Kelola Brief Marketing · Enterprise BPM')
@section('eyebrow', 'Production · PROD-03')
@section('page-title', 'Kelola Brief Marketing')
@section('page-description', 'Susun, lanjutkan, dan pantau Brief Marketing dari permintaan klien yang tercatat.')

@section('content')
<style>
/* UI-03A V1.3: full width content, scoped only to the PROD-03 listing */
main.page-content:has(.prod03-list),
.page-content:has(.prod03-list) {
    max-width:none !important;
    width:100%;
    padding-top:20px !important;
    padding-left:28px !important;
    padding-right:28px !important;
}
@media (max-width: 700px) {
    main.page-content:has(.prod03-list),
    .page-content:has(.prod03-list) {
        padding-left:16px !important;
        padding-right:16px !important;
    }
}
.prod03-list {display:grid;gap:20px;color:#192e51;width:100%;max-width:none;min-width:0}
.prod03-list > * {min-width:0}
.prod03-top {display:flex;justify-content:space-between;align-items:flex-start;gap:18px;flex-wrap:wrap}
.prod03-top h2 {margin:0;font-size:23px;color:#192e51}.prod03-top p{margin:5px 0 0;color:#58718f;font-size:12px}
.prod03-button {display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none;font-size:12px;font-weight:700;min-height:38px;padding:9px 15px;border-radius:9px;border:1px solid #d2e0f1;background:white;color:#23487c;cursor:pointer}
.prod03-button.primary {background:#2864b2;border-color:#2864b2;color:white}
.prod03-button:focus-visible {outline:3px solid #92bbeb;outline-offset:2px}
.prod03-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.prod03-stat{--stat-accent:#2864b2;--stat-soft:#e9f1fc;background:#fff;border:1px solid #dce6f2;border-radius:13px;padding:17px 18px 14px;display:flex;flex-direction:column;gap:5px;box-shadow:0 2px 8px rgba(31,69,120,.035)}
.prod03-stat:nth-child(2){--stat-accent:#168a63;--stat-soft:#e5f6ee}.prod03-stat:nth-child(3){--stat-accent:#bd8316;--stat-soft:#fdf3dc}.prod03-stat:nth-child(4){--stat-accent:#54739b;--stat-soft:#eaf0f8}
.prod03-stat-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
.prod03-stat-icon{width:36px;height:36px;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;background:var(--stat-soft);color:var(--stat-accent)}
.prod03-stat-icon svg{width:19px;height:19px;display:block}
.prod03-stat-count{font-size:27px;line-height:1;font-weight:800;color:#1a2b50;font-variant-numeric:tabular-nums}
.prod03-stat-title{font-size:13px;font-weight:750;color:#233d68}
.prod03-stat-detail{font-size:11px;line-height:1.4;color:#6b7e97;min-height:30px;margin:0}
.prod03-stat-track{height:4px;background:#edf2f8;overflow:hidden;border-radius:8px;margin-top:7px}
.prod03-stat-track span{height:100%;display:block;background:var(--stat-accent);border-radius:8px}

.prod03-tabs{display:flex;gap:22px;flex-wrap:wrap;padding:0 20px;border-bottom:1px solid #dfe8f3;background:#fff}
.prod03-tab{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;padding:17px 4px 14px;background:transparent;border:0;border-bottom:3px solid transparent;color:#647b98;font-size:12px;font-weight:750}
.prod03-tab.is-active{color:#215ba8;border-bottom-color:#2864b2}
.prod03-tab:focus-visible{outline:3px solid #91bbef;outline-offset:2px}
.prod03-incoming-tools{display:flex;align-items:center;gap:10px;padding:14px 20px;border-top:1px solid #eef2f7}
.prod03-incoming-tools input{flex:1;min-width:0;border:1px solid #d3e0ee;border-radius:7px;padding:10px;font:inherit;font-size:12px;min-height:38px}
.prod03-incoming-hidden{display:none}
.prod03-panel{background:#fff;border:1px solid #d9e5f2;border-radius:15px;overflow:hidden}
.prod03-tab-content{min-width:0}
.prod03-panel-head{padding:20px 20px 16px;display:flex;align-items:center;justify-content:space-between;gap:10px}.prod03-panel-head h3{font-size:16px;margin:0;color:#1b3155}.prod03-panel-head p{font-size:11px;color:#627995;margin:6px 0 0}
.prod03-counter{background:#f2f6fb;border:1px solid #dbe5f0;border-radius:7px;font-size:11px;padding:7px 10px;color:#45618a}
.prod03-filters{display:flex;gap:10px;padding:14px 20px;border-top:1px solid #eef2f7;align-items:center}
.prod03-filters input,.prod03-filters select{border:1px solid #d3e0ee;background:white;border-radius:7px;padding:10px;font:inherit;font-size:12px;color:#25426b;min-height:38px}
.prod03-filters input{flex:1;min-width:0}.prod03-filters select{width:190px}
.prod03-table-scroll{overflow-x:auto}.prod03-table{border-collapse:collapse;width:100%;min-width:770px;font-size:12px}.prod03-table thead{background:#f1f5fa}.prod03-table th{padding:13px 16px;color:#637996;font-size:10px;letter-spacing:.09em;text-align:left}.prod03-table td{padding:13px 16px;border-top:1px solid #e4ecf5;vertical-align:middle}.prod03-table tr:nth-child(even){background:#fbfcff}
.prod03-table tr[hidden]{display:none}.prod03-table small{display:block;color:#74849a;margin-top:4px;font-size:10px}.prod03-code{font-size:11px;color:#2e609e;font-weight:750}.prod03-status{display:inline-flex;border-radius:20px;background:#edf3fc;padding:7px 10px;font-size:10px;color:#27558d;font-weight:750}.prod03-status.status-submitted{background:#e8f1ef;color:#177657}
.prod03-pagination{padding:0 20px 17px}.prod03-overlay{position:fixed;inset:0;background:rgba(16,35,65,.52);display:none;align-items:center;justify-content:center;z-index:1001;padding:20px}.prod03-overlay.is-open{display:flex}
.prod03-dialog{background:white;border-radius:12px;width:min(760px,100%);max-height:85vh;display:flex;flex-direction:column;padding:23px;box-shadow:0 20px 70px #091e3c50}
.prod03-dialog-header{display:flex;align-items:center;justify-content:space-between;gap:12px}.prod03-dialog h3{margin:0;color:#1a3667}.prod03-dialog p{font-size:12px;color:#6c7f96;margin:8px 0 14px}.prod03-dialog-search{border:1px solid #ccdcec;border-radius:7px;padding:11px;width:100%;box-sizing:border-box}.prod03-dialog-list{overflow:auto;margin-top:14px;border:1px solid #dce6f3;border-radius:8px}.prod03-dialog-row{display:flex;justify-content:space-between;align-items:center;gap:13px;padding:12px;border-top:1px solid #e4ebf4}.prod03-dialog-row:first-child{border-top:0}.prod03-dialog-row strong{display:block;font-size:12px;color:#2b518a}.prod03-dialog-row small{font-size:11px;color:#617792}
@media(max-width:900px){.prod03-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:550px){.prod03-stats{grid-template-columns:1fr}.prod03-filters{flex-direction:column;align-items:stretch}.prod03-filters select{width:100%}.prod03-dialog-row{flex-wrap:wrap}}

/* UI-03A V1.7 — Table visual alignment with PROD-02 */
.prod03-panel{border-radius:17px;border-color:#d8e5f3}
.prod03-panel-head{padding:15px 17px 10px}
.prod03-panel-head h3{font-size:15px}
.prod03-panel-head p{margin-top:4px}
.prod03-filters,.prod03-incoming-tools{padding:10px 17px 11px;border-top:0}
.prod03-filters input,.prod03-filters select,.prod03-incoming-tools input{min-height:36px}
.prod03-table{font-size:11px;min-width:780px}
.prod03-table thead{background:#f3f6fa}
.prod03-table th{padding:16px 14px;font-size:10px;font-weight:800;color:#637996;letter-spacing:.12em;border-top:1px solid #e0eaf4;border-bottom:1px solid #e0eaf4}
.prod03-table td{padding:13px 14px;border-top:1px solid #e0eaf4}
.prod03-table tr:nth-child(even){background:#fafcff}
.prod03-table td strong{font-weight:750;color:#182d4e}
.prod03-code{display:inline-flex;padding:4px 7px;border:1px solid #dce7f5;border-radius:5px;background:#f3f7fc;color:#315d92;font-size:10px;text-decoration:none}
.prod03-status{gap:5px;padding:7px 11px;letter-spacing:.045em}
.prod03-status:before{content:"";height:5px;width:5px;border-radius:50%;background:currentColor}
.prod03-tab{padding-top:14px;padding-bottom:12px}
.prod03-button{min-height:32px;padding:7px 12px;font-size:11px}

</style>
<div class="prod03-list">
    @if(session('message'))<div class="notice notice-success" role="status">{{ session('message') }}</div>@endif
    @if($errors->any())<div class="notice notice-warning" role="alert">{{ $errors->first() }}</div>@endif
    
    @php($prod03TotalCount = max(1, array_sum($statusCounts ?? [])))
    <div class="prod03-stats" aria-label="Ringkasan status Brief Marketing">
        @foreach([
            ['status' => 'DRAFT', 'title' => 'Draft', 'detail' => 'Sedang disusun · belum selesai', 'icon' => 'edit'],
            ['status' => 'READY_FOR_SUBMISSION', 'title' => 'Siap Diajukan', 'detail' => 'Siap untuk proses pengajuan', 'icon' => 'check'],
            ['status' => 'READY_WITH_NOTES', 'title' => 'Siap dengan Catatan', 'detail' => 'Ada outstanding yang tercatat', 'icon' => 'alert'],
            ['status' => 'SUBMITTED', 'title' => 'Sudah Diajukan', 'detail' => 'Telah diajukan oleh Marketing', 'icon' => 'send'],
        ] as $stat)
            @php($statCount = (int) ($statusCounts[$stat['status']] ?? 0))
            <div class="prod03-stat">
                <div class="prod03-stat-top">
                    <span class="prod03-stat-icon" aria-hidden="true">
                        @if($stat['icon'] === 'edit')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16 4l4 4L9 19H5v-4L16 4z"/></svg>
                        @elseif($stat['icon'] === 'check')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                        @elseif($stat['icon'] === 'alert')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3z"/><path d="M12 9v5m0 3h.01"/></svg>
                        @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m22 2-7 20-4-9-9-4 20-7z"/><path d="M22 2 11 13"/></svg>
                        @endif
                    </span>
                    <strong class="prod03-stat-count">{{ $statCount }}</strong>
                </div>
                <div class="prod03-stat-title">{{ $stat['title'] }}</div>
                <p class="prod03-stat-detail">{{ $stat['detail'] }}</p>
                <div class="prod03-stat-track" aria-hidden="true"><span style="width:{{ min(100, round($statCount * 100 / $prod03TotalCount)) }}%"></span></div>
            </div>
        @endforeach
    </div>
    @php($prod03ActiveTab = request()->query('tab') === 'incoming' ? 'incoming' : 'briefs')
    <div class="prod03-panel">
    <nav class="prod03-tabs" id="prod03-tabs" aria-label="Tampilan pekerjaan Marketing">
        <a class="prod03-tab {{ $prod03ActiveTab === 'incoming' ? 'is-active' : '' }}" href="{{ route('prod03.briefs.index', ['tab' => 'incoming']) }}" @if($prod03ActiveTab === 'incoming') aria-current="page" @endif>Permintaan Masuk</a>
        <a class="prod03-tab {{ $prod03ActiveTab === 'briefs' ? 'is-active' : '' }}" href="{{ route('prod03.briefs.index', ['tab' => 'briefs']) }}" @if($prod03ActiveTab === 'briefs') aria-current="page" @endif>Brief Marketing Saya</a>
    </nav>
    @if($prod03ActiveTab === 'incoming')
    <section class="prod03-tab-content" aria-labelledby="prod03-incoming-heading">
        <div class="prod03-panel-head"><div><h3 id="prod03-incoming-heading">Permintaan Klien Siap Diproses</h3><p>Permintaan berstatus RECORDED dari PROD-02. Satu permintaan dapat menghasilkan beberapa Brief Marketing.</p></div><span class="prod03-counter">{{ $availableClientBriefs->count() }} permintaan</span></div>
        <div class="prod03-incoming-tools"><input type="search" id="prod03-incoming-search" aria-label="Cari permintaan masuk" placeholder="Cari nomor REQ, klien, atau jenis kebutuhan..." /></div>
        <div class="prod03-table-scroll"><table class="prod03-table"><thead><tr><th>Nomor Permintaan</th><th>Klien</th><th>Jenis Kebutuhan</th><th>Status</th><th>Aksi</th></tr></thead><tbody id="prod03-incoming-body">
        @forelse($availableClientBriefs as $req)
            <tr data-prod03-incoming="{{ mb_strtolower(($req->request_number ?? '').' '.($req->client_name ?? '').' '.($req->requirement_type ?? '')) }}"><td><span class="prod03-code">{{ $req->request_number }}</span></td><td><strong>{{ $req->client_name ?: 'Belum diisi' }}</strong></td><td>{{ $req->requirement_type ?: '—' }}</td><td><span class="prod03-status status-submitted">RECORDED</span></td><td><a class="prod03-button primary" href="{{ route('prod03.briefs.create', $req) }}">Susun Brief →</a></td></tr>
        @empty
            <tr><td colspan="5" style="text-align:center;padding:35px;color:#66809e">Belum ada Permintaan Klien berstatus RECORDED.</td></tr>
        @endforelse
        </tbody></table></div>
        <p class="prod03-incoming-hidden" id="prod03-incoming-empty" role="status" style="padding:14px 20px;color:#66809e;font-size:12px">Tidak ada permintaan sesuai pencarian.</p>
    </section>
    @else
    <section class="prod03-tab-content" aria-labelledby="prod03-list-title">
        <div class="prod03-panel-head"><div><h3 id="prod03-list-title">Daftar Brief Marketing</h3><p>Dokumen yang dibuat oleh Marketing Anda · Pilih Lanjutkan untuk mengedit Brief.</p></div><span class="prod03-counter">{{ $briefs->total() }} hasil</span></div>
        <form class="prod03-filters" action="{{ route('prod03.briefs.index') }}" method="GET">
            <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari nomor Brief, nomor REQ, klien..." aria-label="Cari Brief Marketing" />
            <select name="status" aria-label="Filter status"><option value="">Semua status</option>@foreach(['DRAFT','READY_FOR_SUBMISSION','READY_WITH_NOTES','SUBMITTED'] as $status)<option value="{{ $status }}" @selected(($statusFilter ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select>
            <button class="prod03-button" type="submit">Cari / Filter</button>
            @if(($search ?? '') !== '' || ($statusFilter ?? '') !== '')<a class="prod03-button" href="{{ route('prod03.briefs.index') }}">Reset</a>@endif
        </form>
        <div class="prod03-table-scroll"><table class="prod03-table"><thead><tr><th>Brief</th><th>Permintaan / Klien</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
        @forelse($briefs as $brief)
            <tr><td><a class="prod03-code" href="{{ route('prod03.briefs.show', $brief) }}">#{{ $brief->id }}</a></td><td><strong>{{ $brief->clientBrief?->request_number ?? '—' }}</strong><small>{{ $brief->clientBrief?->client_name ?? '—' }}</small></td><td><span class="prod03-status {{ $brief->status === 'SUBMITTED' ? 'status-submitted' : '' }}">{{ str_replace('_',' ',$brief->status) }}</span></td><td>{{ $brief->created_at?->format('d/m/Y H:i') ?? '—' }}</td><td><a class="prod03-button" href="{{ $brief->status === 'DRAFT' || $brief->status === 'READY_FOR_SUBMISSION' || $brief->status === 'READY_WITH_NOTES' ? route('prod03.briefs.edit', $brief) : route('prod03.briefs.show', $brief) }}">{{ in_array($brief->status, ['DRAFT','READY_FOR_SUBMISSION','READY_WITH_NOTES']) ? 'Lanjutkan →' : 'Detail →' }}</a></td></tr>
        @empty
            <tr><td colspan="5" style="text-align:center;padding:35px;color:#66809e">Tidak ditemukan Brief Marketing untuk pencarian atau filter ini.</td></tr>
        @endforelse
        </tbody></table></div><div class="prod03-pagination">{{ $briefs->links() }}</div>
    </section>
    @endif
    </div>
</div>
<script>
(function(){
    const input=document.getElementById('prod03-incoming-search');
    input?.addEventListener('input',()=>{
        const term=input.value.trim().toLocaleLowerCase();
        const rows=[...document.querySelectorAll('[data-prod03-incoming]')];
        let visible=0;
        rows.forEach(row=>{
            const show=row.dataset.prod03Incoming.includes(term);
            row.hidden=!show;
            if(show)visible++;
        });
        const empty=document.getElementById('prod03-incoming-empty');
        if(empty)empty.classList.toggle('prod03-incoming-hidden',visible!==0);
    });
})();
</script>
@endsection
