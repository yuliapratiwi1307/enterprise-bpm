<!doctype html><html lang="id" data-bs-navbar-position="vertical" data-bs-sidebar="folded-hover"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>@yield('page_title', 'Production Workspace') · Enterprise BPM</title><style>
:root{font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;color:#172742;background:#f2f5fb;font-size:13px}*{box-sizing:border-box}body{margin:0}a{color:#2563c5;text-decoration:none}button,input{font:inherit}button{cursor:pointer}.app{display:flex;min-height:100vh}.side{width:222px;flex-shrink:0;background:#10203e;color:#b6c4d9;padding:24px 16px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}.brand{display:flex;gap:12px;align-items:center;color:#fff;font-weight:760;font-size:15px}.logo{background:#3279ed;color:#fff;width:38px;height:38px;display:grid;place-items:center;border-radius:11px;font-size:17px}.brand small{display:block;color:#91a5c4;font-size:11px;font-weight:400;margin-top:3px}.menu-label{font-size:10px;letter-spacing:2px;font-weight:800;margin:36px 11px 12px;color:#849dbe}.nav{display:flex;gap:11px;align-items:center;padding:13px;border-radius:9px;color:#b9c9e0;font-size:12px;font-weight:700}.nav.active,.nav:hover{background:#263f67;color:#fff}.side-foot{margin-top:auto;padding:16px 10px 0;border-top:1px solid #2c4061;color:#8399ba;font-size:10px;line-height:1.8}.main{min-width:0;flex:1}.header{background:#fff;height:66px;border-bottom:1px solid #e0e7f3;display:flex;justify-content:space-between;align-items:center;padding:0 28px}.header strong{display:block;font-size:13px}.header small{font-size:11px;color:#71839f}.header-tag{border-radius:999px;color:#2864bf;background:#e9f2ff;font-size:10px;padding:7px 12px;font-weight:800}.content{padding:25px 28px 60px;max-width:1570px;margin:0 auto}.eyebrow{font-size:10px;color:#2469e3;letter-spacing:2.3px;font-weight:850;text-transform:uppercase}.pagehead{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:20px}h1{font-size:27px;letter-spacing:-.8px;margin:7px 0 5px;line-height:1.15}h2{font-size:15px;margin:0;color:#172742}h3{font-size:13px;margin:0}p{line-height:1.6}.sub{color:#7286a2;font-size:12px;margin:0}.muted{color:#6b7e9a;font-size:11px}.card{background:white;border:1px solid #dbe5f4;border-radius:13px;padding:19px;margin-bottom:14px}.section-title{display:flex;align-items:center;gap:10px;margin-bottom:18px}.iconbox{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:#e9f2ff;color:#2769d1;font-size:16px;flex-shrink:0}.kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:18px 0}.kpi{display:flex;gap:13px;align-items:center;background:#fff;border:1px solid #dce5f4;border-radius:12px;padding:20px}.kpi strong{font-size:24px;display:block}.kpi small{font-size:11px;color:#7587a1}.toolbar{display:flex;gap:10px;justify-content:space-between;margin:17px 0 12px}.search{width:min(460px,100%);border:1px solid #dbe4f2;border-radius:9px;background:#fff;padding:12px 15px;outline:none}.search:focus{border-color:#3478ec;box-shadow:0 0 0 2px #e5efff}.btn{border:1px solid #cbdaf0;border-radius:9px;padding:10px 14px;text-decoration:none;color:#285caf;background:#fff;font-weight:700;font-size:11px;display:inline-flex;align-items:center;justify-content:center;gap:7px}.btn:hover{background:#eff5ff}.table-scroll{overflow-x:auto}.data-table{width:100%;border-collapse:collapse;text-align:left;font-size:12px;min-width:690px}.data-table th{background:#f6f9fd;color:#6c7e99;font-size:10px;font-weight:750;padding:14px 13px;border-bottom:1px solid #e3ebf7;white-space:nowrap}.data-table td{padding:15px 13px;border-bottom:1px solid #e9eef7;vertical-align:middle}.data-table tbody tr:hover{background:#f8faff}.data-table tbody tr:last-child td{border-bottom:0}.data-table .strong{font-weight:750}.pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;background:#eaf3ff;color:#2a66bf;padding:7px 10px;font-size:10px;font-weight:700;white-space:nowrap}.pill.amber{background:#fff2dc;color:#a4660c}.pill.green{background:#e7f8ec;color:#168454}.pill.gray{background:#f0f3f8;color:#60738e}.action-icon{display:inline-flex;border:1px solid #cbdcf5;color:#2867c8;border-radius:8px;background:#fff;align-items:center;justify-content:center;width:34px;height:34px;font-size:16px}.action-icon:hover{background:#eff5ff}.two-col{display:grid;grid-template-columns:minmax(0,1.85fr) minmax(320px,1fr);gap:14px;align-items:start}.stat-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-bottom:1px solid #e5ecf6;padding-bottom:14px;margin-bottom:16px}.stat{padding:7px 12px;border-right:1px solid #e4ebf5}.stat:first-child{padding-left:0}.stat:last-child{border:0}.stat label{display:block;color:#7185a5;font-size:10px;margin-bottom:6px}.stat strong{font-size:12px;word-break:break-word}.brief{border-left:3px solid #8eafff;padding:7px 0 7px 14px;line-height:1.65;overflow-wrap:anywhere}.brief p{margin:4px 0}.brief a{word-break:break-word}.item-card{border:1px solid #d8e5f8;border-radius:11px;overflow:hidden;margin:10px 0;background:#fff}.item-card summary{list-style:none;display:flex;align-items:center;gap:14px;padding:12px 13px;cursor:pointer}.item-card summary::-webkit-details-marker{display:none}.item-card summary:hover{background:#f7faff}.thumb{height:62px;width:70px;border:1px solid #dbe5f2;border-radius:8px;object-fit:contain;background:#f6f8fb;flex-shrink:0}.thumb-empty{display:grid;place-items:center;color:#8ea2c2}.item-main{min-width:0;flex:1}.item-main small{display:block;font-size:10px;color:#7489aa;margin:3px 0}.item-main b{font-size:13px}.item-right{text-align:right}.item-right small{display:block;color:#3970c9;margin-top:9px}.item-body{background:#f8faff;border-top:1px solid #e6edf7;padding:16px}.preview-strip{display:flex;gap:9px;flex-wrap:wrap;margin:10px 0 0}.preview-strip img{width:120px;height:90px;object-fit:contain;border-radius:8px;border:1px solid #d8e5f4;background:white}.mini-table{width:100%;border-collapse:collapse;font-size:11px;background:white;border:1px solid #dce6f5}.mini-table th{background:#eef4fc;color:#657d9c;text-align:left;font-size:10px;padding:11px}.mini-table td{border-top:1px solid #e5edf6;padding:11px;overflow-wrap:anywhere}.file-tag{font-size:9px;padding:5px 7px;border-radius:6px;background:#e7f0ff;color:#3664ad;font-weight:800}.doc-row{display:flex;justify-content:space-between;align-items:center;gap:10px;border-bottom:1px solid #e7edf7;padding:13px 0}.doc-row:last-child{border:0}.doc-row strong{font-size:11px;overflow-wrap:anywhere}.doc-row small{display:block;color:#7c8ba3;margin-top:5px}.info-note{padding:13px;border-radius:9px;background:#f0f5ff;color:#3a629d;font-size:11px;line-height:1.6}.empty{padding:28px;text-align:center;color:#788ba7}.foot{text-align:center;color:#8a9bb4;font-size:10px;padding:10px}.count{font-size:11px;color:#7890af}.sidebar-label{color:#7393c5;font-size:11px;font-weight:700;margin-top:10px}@media(max-width:1050px){.side{width:190px}.two-col{grid-template-columns:1fr}.stat-row{grid-template-columns:repeat(2,1fr)}.stat:nth-child(2){border:0}}@media(max-width:700px){.app{display:block}.side{position:static;width:100%;height:auto;padding:12px}.side .menu-label,.side-foot{display:none}.side .nav{padding:6px 10px}.header{height:auto;min-height:56px;padding:12px 16px}.content{padding:20px 13px}.kpi-grid{grid-template-columns:1fr}.pagehead{align-items:start}.stat-row{grid-template-columns:1fr 1fr}.header-tag{display:none}}
.review-form{display:grid;gap:12px}.decision{display:flex;align-items:start;gap:10px;padding:12px;border:1px solid #dbe5f4;border-radius:9px;cursor:pointer}.decision:has(input:checked){background:#eff6ff;border-color:#8db8fa}.decision input{margin-top:3px;accent-color:#2563eb}.decision span{font-weight:700}.decision small{display:block;font-weight:400;color:#71839f;margin-top:5px;line-height:1.5}.review-textarea{width:100%;border:1px solid #cfdaed;border-radius:9px;padding:12px;font:inherit;resize:vertical;min-height:85px}.review-button{border:0;background:#2d69d8;color:white;border-radius:9px;padding:12px 16px;font-weight:750;cursor:pointer}.review-button:hover{background:#2057bd}.notice-success{background:#e7f7ed;border:1px solid #b8e5c8;border-radius:9px;padding:12px;color:#116c3b;margin-bottom:13px}.notice-error{background:#fff1f1;border:1px solid #ffd0d0;color:#b42335;border-radius:9px;padding:12px;margin-bottom:13px}.review-history{border-left:2px solid #c5d9f7;margin:10px 0 0 6px;padding:0 0 0 14px}.review-event{margin:0 0 18px;position:relative}.review-event:before{content:'';position:absolute;left:-21px;top:4px;width:10px;height:10px;border:2px solid white;border-radius:50%;background:#377bf1;box-shadow:0 0 0 1px #a6c7fb}.review-event p{margin:4px 0 0}.readonly-note{padding:13px;border-radius:9px;background:#f4f7fb;color:#5e738f;font-size:11px}
.tech-item{border:1px solid #dbe5f4;border-radius:11px;overflow:hidden;margin-bottom:12px;background:#fff}
.tech-item summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:10px;background:#f6f9fe;padding:15px}
.tech-item summary::-webkit-details-marker{display:none}.tech-item summary b{display:block}.tech-item summary small{display:block;color:#7286a2;margin-top:4px}.tech-form{padding:16px;display:grid;gap:15px}
.tech-form h3{margin:2px 0 8px}.tech-table{width:100%;border-collapse:collapse}.tech-table th{font-size:10px;color:#71839f;background:#f3f7fd;text-align:left;padding:10px 7px}.tech-table td{padding:7px 4px;border-bottom:1px solid #e7edf7}
.tech-field{border:1px solid #cbd9ec;border-radius:7px;padding:9px 8px;width:100%;min-width:0;background:#fff;font:inherit}
.tech-field:focus{outline:2px solid #bcd4fc;border-color:#5d95f1}.tech-form textarea{min-height:75px;resize:vertical}
.tech-add{border:1px solid #cbdaf0;border-radius:7px;background:#fff;color:#2d69d8;padding:8px 11px;font-weight:650;cursor:pointer}
.tech-remove{border:1px solid #f3cbd1;color:#bb334e;background:#fff;border-radius:7px;padding:8px;cursor:pointer}
.tech-two{display:grid;grid-template-columns:180px 1fr;gap:12px}.tech-foot{display:flex;justify-content:flex-end;align-items:center;gap:10px}
.tech-foot small{color:#7286a2}.tech-saved{color:#13734b;font-weight:700;font-size:11px}
@media(max-width:720px){.tech-two{grid-template-columns:1fr}.tech-table{min-width:620px}}
.inline-tech{margin-top:20px;padding:18px;border:1px solid #d5e3f8;background:#f8fbff;border-radius:11px}.inline-tech-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:14px}.inline-tech-head h3{font-size:13px}.tech-overview{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;background:#f6f9ff;border:1px solid #e0eafb;border-radius:8px;padding:10px 12px;margin-bottom:13px;color:#5a7297;font-size:11px}.tech-total-line{text-align:right;padding:12px 2px;font-size:12px}.tech-total-line strong{font-size:14px;color:#153a75}.tech-subtotal{white-space:nowrap;font-weight:700;color:#264b7f;font-size:11px}.tech-table:has([data-rows="processes"]){min-width:940px}.tech-table:has([data-rows="processes"]) td:nth-child(5){min-width:125px}.tech-table:has([data-rows="processes"]) td:nth-child(6){min-width:110px}.inline-tech .tech-form{padding:0;background:transparent;border:0}.inline-tech .tech-form>div{margin-bottom:16px}.inline-tech .tech-form>div:last-child{margin-bottom:0}
/* V5.8.1: simplified material and process inputs; legacy JSON remains compatible. */
.two-col > div,.two-col section,.item-card,.item-body,.inline-tech,.tech-form,.tech-form > div{min-width:0;max-width:100%}
.inline-tech{width:100%;padding:16px;overflow:hidden}
.inline-tech-head{min-width:0;flex-wrap:wrap}
.inline-tech .tech-form{display:flex;flex-direction:column;gap:17px;min-width:0}
.inline-tech .tech-form>div{width:100%;margin-bottom:0;min-width:0}
.inline-tech .table-scroll{width:100%;max-width:100%;overflow:visible}
.inline-tech .tech-table,.inline-tech .tech-table:has([data-rows="processes"]){display:block;width:100%;max-width:100%;min-width:0;border:0;background:transparent}
.inline-tech .tech-table thead{display:none}
.inline-tech .tech-table tbody{display:grid;gap:10px;width:100%}
.inline-tech .tech-table tbody tr{position:relative;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 12px;padding:13px 46px 13px 13px;border:1px solid #dce7f7;border-radius:10px;background:#fff;min-width:0}
.inline-tech .tech-table tbody td,.inline-tech .tech-table:has([data-rows="processes"]) td:nth-child(5),.inline-tech .tech-table:has([data-rows="processes"]) td:nth-child(6){display:flex;flex-direction:column;gap:5px;min-width:0;width:100%;padding:0;border:0;align-items:stretch}
.inline-tech .tech-table tbody td::before{content:'';font-size:10px;font-weight:750;color:#6a7f9e}
.inline-tech .tech-table tbody[data-rows="materials"] td:nth-child(1)::before{content:'Material'}
.inline-tech .tech-table tbody[data-rows="materials"] td:nth-child(2)::before{content:'Ukuran'}
.inline-tech .tech-table tbody[data-rows="materials"] td:nth-child(3)::before{content:'Jumlah'}



.inline-tech .tech-table tbody[data-rows="processes"] tr{grid-template-columns:repeat(2,minmax(0,1fr))}
.inline-tech .tech-table tbody[data-rows="processes"] td:nth-child(1)::before{content:'Proses / Jasa'}
.inline-tech .tech-table tbody[data-rows="processes"] td:nth-child(2)::before{content:'Deskripsi'}
.inline-tech .tech-table tbody[data-rows="processes"] td:nth-child(3)::before{content:'Jumlah'}
.inline-tech .tech-table tbody[data-rows="processes"] td:nth-child(4)::before{content:'Harga (Rp)'}




.inline-tech .tech-table tbody tr td:last-child{position:absolute;right:10px;top:10px;width:auto;padding:0}
.inline-tech .tech-table tbody tr td:last-child::before{display:none}
.inline-tech .tech-table tbody .tech-subtotal{display:flex;justify-content:center;min-height:35px;padding:8px 10px!important;border:1px solid #dce7f7;border-radius:7px;background:#f4f8ff;white-space:nowrap}
.inline-tech .tech-table tbody .tech-subtotal::before{display:block}
.inline-tech .tech-field{box-sizing:border-box;display:block;width:100%;max-width:100%;min-width:0;height:36px;padding:8px 10px}
.inline-tech textarea.tech-field{height:auto;min-height:100px;resize:vertical}
.inline-tech .tech-two{grid-template-columns:minmax(0,160px) minmax(0,1fr);gap:12px}
.inline-tech .tech-two label{display:block;min-width:0;font-size:11px;font-weight:650;color:#496080}
.inline-tech .tech-two label .tech-field{margin-top:7px}
.inline-tech .tech-foot{display:flex;align-items:center;flex-wrap:wrap;justify-content:flex-end;gap:10px;padding-top:10px;border-top:1px solid #e2eaf7}
.inline-tech .tech-foot .review-button{max-width:100%;white-space:normal}
.inline-tech .tech-total-line{display:flex;justify-content:flex-end;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #e3ebf8}
.inline-tech .tech-remove{width:27px;height:27px;display:grid;place-items:center;padding:0}
@media(max-width:850px){.inline-tech .tech-table tbody[data-rows="processes"] tr{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:540px){.inline-tech{padding:11px}.inline-tech .tech-table tbody tr,.inline-tech .tech-table tbody[data-rows="processes"] tr{grid-template-columns:1fr;padding:12px 43px 12px 12px}.inline-tech .tech-two{grid-template-columns:1fr}.inline-tech .tech-foot .review-button{width:100%}}
/* V5.7.5: space Add Material below the boxed material row. */
.inline-tech [data-rows="materials"]{margin-bottom:12px}
.inline-tech [data-add="materials"]{display:inline-flex;align-items:center;margin-top:14px;margin-bottom:2px}
.inline-tech [data-add="processes"]{display:inline-flex;align-items:center;margin-top:14px}
/* V5.7.6: enforce one consistent action column for both row variants. */
.inline-tech .tech-table tbody[data-rows="materials"] tr,
.inline-tech .tech-table tbody[data-rows="processes"] tr{
  position:relative;
  border:1px solid #dce7f7;
  border-radius:10px;
  padding:14px 48px 14px 14px;
  background:#fff;
  column-gap:12px;
  row-gap:11px;
  overflow:visible;
}
.inline-tech .tech-table tbody[data-rows="materials"] tr > td:last-child,
.inline-tech .tech-table tbody[data-rows="processes"] tr > td:last-child{
  position:absolute !important;
  display:block !important;
  box-sizing:border-box !important;
  top:12px !important;
  right:12px !important;
  left:auto !important;
  bottom:auto !important;
  width:28px !important;
  min-width:28px !important;
  max-width:28px !important;
  height:28px !important;
  padding:0 !important;
  margin:0 !important;
  border:0 !important;
  background:transparent !important;
  z-index:1;
}
.inline-tech .tech-table tbody tr > td:last-child::before{display:none !important;content:none !important}
.inline-tech .tech-table tbody tr > td:last-child .tech-remove{
  width:28px;height:28px;display:grid;place-items:center;
  padding:0;margin:0;line-height:1;
}
.inline-tech .tech-table tbody tr > td:not(:last-child){min-width:0}
.inline-tech .tech-table tbody tr > td .tech-field{margin:0}
.inline-tech .tech-table tbody[data-rows="materials"],
.inline-tech .tech-table tbody[data-rows="processes"]{display:grid;gap:12px;margin:0}
.inline-tech .tech-table tbody[data-rows="materials"] + *,
.inline-tech .tech-table tbody[data-rows="processes"] + *{margin-top:0}
.inline-tech button.tech-add[data-add="materials"],
.inline-tech button.tech-add[data-add="processes"]{
  display:inline-flex;align-items:center;justify-content:center;
  margin-top:16px;margin-bottom:0;
  min-height:34px;
}
.inline-tech .tech-form>div > h3{margin:0 0 12px}
.inline-tech .tech-form{gap:22px}
.inline-tech .tech-form>div{margin:0}
@media(max-width:540px){
  .inline-tech .tech-table tbody[data-rows="materials"] tr,
  .inline-tech .tech-table tbody[data-rows="processes"] tr{padding:14px 48px 14px 14px}
}

/* V5.7.7 — clear section hierarchy, compact row actions, uniform separators. */
.inline-tech{padding:0;overflow:hidden;background:#f8fbff}
.inline-tech .inline-tech-head{
  display:flex;align-items:center;justify-content:space-between;gap:12px;
  margin:0;padding:15px 17px;border-bottom:1px solid #d7e5f6;
  background:#edf4ff;
}
.inline-tech .tech-heading-copy{display:flex;align-items:center;gap:9px;min-width:0}
.inline-tech .tech-heading-copy h3{margin:0;font-size:14px;line-height:1.35;color:#18355e;font-weight:750}
.inline-tech .tech-heading-icon{display:grid;place-items:center;width:25px;height:25px;flex:none;border-radius:7px;background:#dceaff;color:#2465c8;font-size:14px}
.inline-tech .tech-form{padding:0 17px 17px;gap:0}
.inline-tech .tech-form > div:not(.tech-foot){padding:18px 0 20px;margin:0;border-bottom:1px solid #d9e5f4}
.inline-tech .tech-form > div:not(.tech-foot) > h3{margin:0 0 13px;font-size:12px;font-weight:750;color:#1e3554}
.inline-tech .tech-form > div.tech-foot{margin:0;padding-top:17px}
.inline-tech .tech-table tbody[data-rows="materials"] tr > td:last-child,
.inline-tech .tech-table tbody[data-rows="processes"] tr > td:last-child{
  top:10px !important;right:10px !important;
  width:22px !important;min-width:22px !important;max-width:22px !important;height:22px !important;
}
.inline-tech .tech-table tbody tr > td:last-child .tech-remove{
  width:22px !important;height:22px !important;border-radius:5px;
  font-size:11px;line-height:1;padding:0 !important;
}
.inline-tech .tech-table tbody[data-rows="materials"] tr,
.inline-tech .tech-table tbody[data-rows="processes"] tr{padding-right:39px}
.inline-tech button.tech-add[data-add="materials"],
.inline-tech button.tech-add[data-add="processes"]{margin-top:14px}
@media(max-width:540px){
  .inline-tech .inline-tech-head{padding:13px 12px}
  .inline-tech .tech-form{padding:0 12px 13px}
  .inline-tech .tech-table tbody[data-rows="materials"] tr,
  .inline-tech .tech-table tbody[data-rows="processes"] tr{padding-right:39px}
}

/* V5.8.1 Dynamic Material Input — applies only to material rows. */
.inline-tech .material-tech-table tbody[data-rows="materials"] .dynamic-material-row{display:block;position:relative;padding:16px 48px 16px 16px}
.inline-tech .material-tech-table tbody[data-rows="materials"] .dynamic-material-content{display:block!important;width:100%;padding:0!important}
.inline-tech .material-tech-table tbody[data-rows="materials"] .dynamic-material-content::before{display:none!important;content:none!important}
.inline-tech .dynamic-material-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 12px}
.inline-tech .dynamic-material-grid label{display:block;font-size:11px;font-weight:700;color:#637b9d;min-width:0}
.inline-tech .dynamic-material-grid .tech-field{margin-top:6px}
.inline-tech .legacy-material-spec{padding:8px 10px;margin-bottom:10px;background:#f4f7fe;border-radius:6px;color:#657995;font-size:11px}
.inline-tech .material-tech-table tbody[data-rows="materials"] .dynamic-material-action{position:absolute!important;right:12px!important;top:12px!important;width:28px!important;padding:0!important}
@media(max-width:550px){.inline-tech .dynamic-material-grid{grid-template-columns:1fr}}

.sjr-manual-nesting svg{user-select:none;-webkit-user-select:none}
.sjr-manual-nesting button:disabled{opacity:.45;cursor:not-allowed}
.sjr-manual-nesting [data-piece-id]:active{cursor:grabbing}
@media(max-width:540px){.sjr-manual-nesting{padding:9px!important}.sjr-manual-nesting svg{max-width:100%}}
</style>
<!-- SJR_V578_PREVIEW_NO_DUPLICATE_START -->
<style>
details.item-card[open] > summary > img.thumb { display: none !important; }
</style>
<!-- SJR_V578_PREVIEW_NO_DUPLICATE_END -->

<style>
/* MM-05: modal pemilihan material lebih lebar */
@media (min-width: 768px) {
  #mm05PickerModal > div {
    width: 96vw !important;
    max-width: 1800px !important;
  }
}
</style>



<!-- SJR_BOXICONS_COMPACT_V1 -->
<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
<style id="sjr-boxicons-compact-css">
.sjr-cp-toolbar{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:7px!important;padding:9px 10px!important;background:#eee!important}
.sjr-cp-toolbar .sjr-cp-top,.sjr-cp-toolbar .sjr-cp-actions{display:flex!important;align-items:center!important;flex-wrap:nowrap!important;gap:6px!important;min-width:0}
.sjr-cp-toolbar .sjr-cp-top select{min-width:0!important;width:auto!important;flex:0 1 auto!important;max-width:170px!important;height:31px!important;min-height:31px!important;padding:3px 7px!important;font-size:12px!important}
.sjr-cp-toolbar .sjr-cp-top select:nth-child(1){width:98px!important}
.sjr-cp-toolbar .sjr-cp-top select:nth-child(2){width:116px!important}
.sjr-cp-toolbar .sjr-cp-top select:nth-child(3){width:84px!important}
.sjr-cp-toolbar .sjr-cp-color{display:inline-flex;align-items:center;gap:5px;padding:2px 7px;height:31px;border:1px solid #cbd9ec;border-radius:5px;background:#fff;font-size:11px;white-space:nowrap}
.sjr-cp-toolbar .sjr-cp-color input[type=color]{height:22px!important;width:25px!important;min-height:0!important;padding:1px!important;border:0!important}
.sjr-cp-toolbar .sjr-cp-actions button{width:31px!important;min-width:31px!important;height:31px!important;min-height:31px!important;padding:0!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;font-size:17px!important;line-height:1!important;font-weight:400!important}
.sjr-cp-toolbar .sjr-cp-actions button i{font-size:19px!important;line-height:1!important;font-weight:400!important}
.sjr-cp-toolbar .sjr-cp-actions button:hover{background:#edf3ff!important}
@media(max-width:700px){.sjr-cp-toolbar .sjr-cp-top,.sjr-cp-toolbar .sjr-cp-actions{flex-wrap:wrap!important}}
</style>
<style id="sjr-ui-v6-stage1-css">
.sjr-v6-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0;padding:9px;background:#edf3fd;border:1px solid #d8e5f6;border-radius:12px}
.sjr-v6-tab{border:1px solid #cbdaf0;background:#fff;color:#254b7d;border-radius:9px;padding:11px 16px;font-weight:750;min-width:105px}
.sjr-v6-tab[aria-selected="true"]{background:#205ec2;border-color:#205ec2;color:#fff}
.sjr-v6-ready .item-card.sjr-v6-inactive{display:none!important}
.sjr-v6-ready .item-card.sjr-v6-active>summary{background:#eaf2ff}
.sjr-v6-quicknav{display:flex;gap:7px;flex-wrap:wrap;margin:10px 0 14px;padding:10px;border:1px solid #d9e5f6;background:#fff;border-radius:9px}
.sjr-v6-quicknav button{background:#f4f8ff;border:1px solid #d6e3f5;border-radius:7px;padding:8px 11px;color:#244b80;font-weight:650}
.sjr-v6-ready .item-card.sjr-v6-active .tech-foot{position:sticky;bottom:0;z-index:30;background:#fff;padding:12px 14px!important;border:1px solid #c9d9ef;border-radius:10px;box-shadow:0 -4px 18px rgba(17,46,88,.10)}
.sjr-v6-dirty{color:#9a5b12;font-weight:700;font-size:11px}
@media(max-width:650px){.sjr-v6-tab{flex:1;min-width:75px}.sjr-v6-ready .item-card.sjr-v6-active .tech-foot{bottom:0}}
</style>
<style id="sjr-ui-v6-stage2-css">
/* SJR_UI_V6_STAGE2: reference-inspired white outlined tabs */
.sjr-v6-tabs{background:#f8faff!important;border:1px solid #e5ebf5!important;border-radius:14px!important;padding:8px 9px 0!important;gap:3px!important}
.sjr-v6-tab{border:1px solid transparent!important;border-bottom:0!important;border-radius:12px 12px 0 0!important;background:transparent!important;color:#44546d!important;padding:12px 16px!important;box-shadow:none!important;min-width:0!important}
.sjr-v6-tab[aria-selected="true"]{background:white!important;color:#387ca3!important;border-color:#e4eaf2!important;box-shadow:0 -2px 10px rgba(25,55,90,.035)!important}
.sjr-v6-ready .sjr-v6-quicknav{display:none!important}
.sjr-v6-subtabs,.sjr-v6-material-tabs{display:flex;align-items:stretch;gap:3px;overflow-x:auto;max-width:100%;padding:8px 8px 0;background:#f8faff;border:1px solid #e5ebf5;border-radius:13px 13px 0 0;scrollbar-width:thin;margin:12px 0 0}
.sjr-v6-subtab,.sjr-v6-material-tab{appearance:none;flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;background:transparent;border:1px solid transparent;border-bottom:0;border-radius:11px 11px 0 0;padding:12px 15px;color:#43516a;font:inherit;font-size:13px;font-weight:650;cursor:pointer}
.sjr-v6-subtab[aria-selected="true"],.sjr-v6-material-tab[aria-selected="true"]{background:#fff;color:#337da5;border-color:#e1e8f0;box-shadow:0 -2px 10px rgba(30,70,110,.035)}
.sjr-v6-subtab:focus-visible,.sjr-v6-material-tab:focus-visible{outline:2px solid #3578cc;outline-offset:-3px}
.sjr-v6-subtab-icon{font-size:16px;line-height:1;opacity:.8}
.sjr-v6-ready .sjr-v6-section-hidden{display:none!important}
.sjr-v6-ready .dynamic-material-row.sjr-v6-material-hidden{display:none!important}
.sjr-v6-material-tabs .sjr-v6-add-material{margin-left:auto;color:#205ec2;font-weight:750}
.sjr-v6-material-tabs .sjr-v6-material-tab[aria-selected="true"]{color:#337da5}
.sjr-v6-ready .dynamic-material-section>.table-scroll{margin-top:0;border:1px solid #e5ebf5;border-top:0;border-radius:0 0 12px 12px}
.sjr-v6-ready .dynamic-material-section>h3{margin-bottom:4px}
.sjr-v6-ready .dynamic-material-section>[data-add="materials"]{display:none!important}
.sjr-v6-ready .tech-foot{position:sticky!important;bottom:0!important;z-index:35!important;background:#fff!important;padding:12px!important;border:1px solid #d8e5f3!important;border-radius:10px!important;box-shadow:0 -4px 18px rgba(17,46,88,.10)!important}
@media(max-width:650px){.sjr-v6-subtab,.sjr-v6-material-tab{padding:11px 12px;font-size:12px}}
</style>
<style id="sjr-ui-v6-tabs-menyatu-css">
/* SJR_UI_V6_TABS_MENYATU_V1: one unified visual panel, no duplicated cards */
.sjr-v6-unified-workspace{background:#fff;border:1px solid #dce6f4;border-radius:16px;overflow:hidden;margin:14px 0;min-width:0}
.sjr-v6-unified-workspace>.sjr-v6-tabs{margin:0!important;border:0!important;border-bottom:1px solid #e2e9f3!important;border-radius:0!important;padding:12px 12px 0!important;background:#f7f9fd!important;gap:4px!important;flex-wrap:nowrap!important;overflow-x:auto!important;scrollbar-width:thin}
.sjr-v6-unified-workspace>.sjr-v6-tabs>.sjr-v6-tab{white-space:nowrap!important;flex:0 0 auto!important;border-radius:12px 12px 0 0!important;padding:14px 18px!important}
.sjr-v6-unified-workspace>.sjr-v6-tabs>.sjr-v6-tab[aria-selected="true"]{position:relative!important;background:#fff!important;border:1px solid #dce6f4!important;border-bottom:1px solid #fff!important;margin-bottom:-1px!important;color:#367fa7!important;z-index:1}
.sjr-v6-unified-workspace>details.item-card{border:0!important;border-radius:0!important;margin:0!important;overflow:visible!important;box-shadow:none!important;background:#fff!important}
.sjr-v6-unified-workspace>details.item-card>summary{border:0!important;border-radius:0!important;background:#fff!important;padding:18px 20px!important}
.sjr-v6-unified-workspace>details.item-card>.item-body{border-top:1px solid #e8eef6!important;background:#fff!important;padding:18px 20px!important}
.sjr-v6-unified-workspace .sjr-v6-subtabs{margin:14px 0 0!important;border-radius:12px 12px 0 0!important;border-bottom:1px solid #e5ebf5!important}
.sjr-v6-unified-workspace .sjr-v6-subtabs+.dynamic-material-section{margin-top:0!important}
.sjr-v6-unified-workspace .sjr-v6-material-tabs{margin:12px 0 0!important;border-radius:12px 12px 0 0!important}
.sjr-v6-unified-workspace .sjr-v6-ready .tech-foot{max-width:100%}
@media(max-width:650px){.sjr-v6-unified-workspace>.sjr-v6-tabs>.sjr-v6-tab{padding:12px!important}.sjr-v6-unified-workspace>details.item-card>.item-body{padding:12px!important}}
</style>

<style id="sjr-v6-scrollbar-fix">
/* SJR UI V6: tab Item tanpa scrollbar vertikal */
.sjr-v6-unified-workspace .sjr-v6-tabs {
    overflow-x: auto !important;
    overflow-y: hidden !important;
}
</style>








<style id="sjr-v6-material-dropdown-nav">
/* SJR_V6_MATERIAL_DROPDOWN_NAV_V1 */

.sjr-v6-material-tabs.sjr-material-nav-ready {
    display: none !important;
}

.sjr-material-dropdown-nav {
    display: flex;
    align-items: end;
    gap: 9px;
    width: 100%;
    min-width: 0;
    padding: 12px;
    margin: 0;
    border: 1px solid #d9e5f6;
    border-bottom: 0;
    border-radius: 10px 10px 0 0;
    background: #f5f8fd;
}

.sjr-material-dropdown-main {
    flex: 1;
    min-width: 0;
}

.sjr-material-dropdown-label {
    display: block;
    margin-bottom: 5px;
    font-size: 10px;
    font-weight: 750;
    color: #617998;
}

.sjr-material-dropdown-select {
    display: block;
    width: 100%;
    min-width: 0;
    height: 36px;
    padding: 7px 11px;
    border: 1px solid #cbdcf2;
    border-radius: 7px;
    background: white;
    color: #1e3557;
    font-size: 12px;
    font-weight: 650;
    outline: none;
}

.sjr-material-dropdown-select:focus {
    border-color: #4c8be8;
    box-shadow: 0 0 0 2px #e3efff;
}

.sjr-material-dropdown-arrow {
    display: grid;
    place-items: center;
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    border: 1px solid #cbdcf2;
    border-radius: 7px;
    background: #fff;
    color: #2864b6;
    font-size: 19px;
    font-weight: 700;
    cursor: pointer;
}

.sjr-material-dropdown-arrow:hover:not(:disabled) {
    background: #e8f2ff;
}

.sjr-material-dropdown-arrow:disabled {
    opacity: .35;
    cursor: default;
}

.sjr-material-dropdown-add {
    flex: 0 0 auto;
    height: 36px;
    padding: 0 12px;
    border: 1px solid #b9d2f6;
    border-radius: 7px;
    background: white;
    color: #2563b6;
    font-size: 11px;
    font-weight: 750;
    cursor: pointer;
}

@media(max-width:650px) {
    .sjr-material-dropdown-nav {
        flex-wrap: wrap;
    }
    .sjr-material-dropdown-main {
        flex-basis: 100%;
    }
}
</style>


{{-- Tabler Core 1.7.5: compiled from user-supplied source, installed locally. --}}
<link rel="stylesheet" href="{{ asset('vendor/tabler-1.7.5/css/tabler.min.css') }}">

{{-- SJR_TABLER_STAGE7 --}}





{{-- SJR_TABLER_STAGE6: preview-only visual refinement --}}
{{-- SJR_TABLER_STAGE5: official Tabler component migration, preview only --}}
<link rel="stylesheet" href="{{ asset('css/sjr-tabler-restructured.css') }}?v=2">
<link rel="stylesheet" href="{{ asset('css/sjr-tabler-redesign-total.css') }}?v=1">

<style id="sjr-folded-hover-compat">
/* Only reconcile old preview overrides with the official Tabler sidebar mechanism.
   Fold/hover geometry and transitions are provided by tabler.min.css. */
html[data-bs-sidebar="folded-hover"] body.sjr-folded-hover > .page > aside.navbar-vertical {background:var(--tblr-bg-surface, #fff);color:var(--tblr-body-color);}
html[data-bs-sidebar="folded-hover"] body.sjr-folded-hover > .page > aside.navbar-vertical .nav-link-icon {display:inline-flex!important;flex:0 0 auto;}
html[data-bs-sidebar="folded-hover"] body.sjr-folded-hover > .page > aside.navbar-vertical .nav-link-icon .icon {width:22px;height:22px;}
html[data-bs-sidebar="folded-hover"] body.sjr-folded-hover > .page > aside.navbar-vertical .navbar-brand a {display:flex;align-items:center;color:inherit;text-decoration:none;}
@media (min-width:992px){html[data-bs-sidebar="folded-hover"] body.sjr-folded-hover > .page > aside.navbar-vertical {z-index:1040;}}
</style>
</head><body class="layout-fluid sjr-tabler-restructured sjr-tabler-redesign-total sjr-folded-hover" data-bs-theme="light"><div class="page">
<aside class="navbar navbar-vertical navbar-expand-lg" aria-label="Navigasi Enterprise BPM">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sjr-tabler-menu" aria-controls="sjr-tabler-menu" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
    <h1 class="navbar-brand"><a href="{{ route('sjr.production.multi.inbox') }}" aria-label="Enterprise BPM"><span class="avatar avatar-sm bg-blue text-white">BPM</span><span class="navbar-brand-text ms-2">Enterprise BPM</span></a></h1>
    <div class="navbar-nav flex-row d-lg-none"><span class="avatar avatar-sm bg-blue-lt">E</span></div>
    <nav class="collapse navbar-collapse" id="sjr-tabler-menu" aria-label="Menu utama">
      <ul class="navbar-nav pt-lg-3">
        <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><span class="nav-link-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12l9 -9l9 9"/><path d="M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1 -1v-10"/><path d="M9 21v-6h6v6"/></svg></span><span class="nav-link-title">Dashboard</span></a></li>
        <li class="nav-item active"><a class="nav-link active" href="{{ route('sjr.production.multi.inbox') }}" aria-current="page"><span class="nav-link-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/><path d="M9 3h6v4h-6z"/><path d="M9 12h6"/><path d="M9 16h6"/></svg></span><span class="nav-link-title">Production Workspace</span></a></li>
        <li class="nav-item"><a class="nav-link" href="{{ route('sjr.production.multi.show', ['jobNumber' => $record->job_number]) }}"><span class="nav-link-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l9 5l-9 5l-9 -5z"/><path d="M3 8v8l9 5l9 -5v-8"/><path d="M12 13v8"/></svg></span><span class="nav-link-title">Halaman Produksi</span></a></li>
      </ul>
      <div class="navbar-side mt-auto"><ul class="navbar-nav"><li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><span class="nav-link-icon"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4.5 1.5c-1 1-2 1.5-2 3"/><path d="M12 17h.01"/></svg></span><span class="nav-link-title">Enterprise BPM</span></a></li></ul></div>
    </nav>
    <div class="nav-item dropdown d-none d-lg-flex mt-2 mb-3"><a class="nav-link d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-label="Akun pengguna" aria-expanded="false"><span class="avatar avatar-sm bg-blue-lt">E</span><span class="nav-link-title ms-2">{{ auth()->user()->name ?? 'Pengguna' }}</span></a><div class="dropdown-menu dropdown-menu-end"><span class="dropdown-item-text">Enterprise BPM</span></div></div>
  </div>
</aside>
<div class="page-wrapper">
<header class="navbar navbar-expand-md d-print-none"><div class="container-xl">
  <div class="navbar-nav flex-row order-md-last align-items-center gap-2"><span class="badge bg-azure-lt">Preview · Tabler 1.7.5</span><span class="avatar avatar-sm bg-blue-lt">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="d-none d-md-inline text-secondary small">{{ auth()->user()->name }}</span></div>
  <div class="navbar-brand navbar-brand-autodark pe-0 pe-md-3"><span class="fw-semibold">Simple Job Request</span></div>
</div></header>
<div class="page-body"><div class="container-fluid px-3 px-lg-4"><main class="content">
<div class="alert alert-info" role="status"><div class="d-flex align-items-center justify-content-between gap-3 flex-wrap"><div><div class="fw-bold">Preview · Tabler Core 1.7.5</div><div class="small">Mode pemeriksaan tampilan. Penyimpanan formulir dinonaktifkan pada halaman preview.</div></div><a class="btn btn-outline-primary" href="{{ route('sjr.production.multi.show', ['jobNumber' => $record->job_number]) }}">Buka halaman produksi</a></div></div>
<div class="page-header d-print-none sjr-project-header"><div class="row align-items-center w-100 g-3"><div class="col-12 col-lg"><div class="sjr-project-heading"><div class="eyebrow text-uppercase text-secondary small fw-semibold">PRODUKSI / DETAIL PROJECT / {{ $record->job_number }}</div><h1 class="page-title">{{ $record->job_title }}</h1><p class="sub">{{ $record->client_name }} · Target {{ $record->target_date ? \Illuminate\Support\Carbon::parse($record->target_date)->format('d/m/Y') : '—' }}</p><p style="margin:11px 0 0"><span class="pill badge bg-blue-lt">Diterima Produksi</span> <span class="muted">{{ count($items) }} jenis item · {{ collect($items)->sum('quantity') }} unit</span></p></div></div><div class="col-12 col-lg-auto"><div class="sjr7-actions d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="{{ route('sjr.production.multi.inbox') }}">← Production Workspace</a></div></div></div></div>
<nav class="sjr7-jump nav nav-pills gap-2" aria-label="Navigasi bagian SJR"><span class="sjr7-jump-label">Ke bagian:</span><a class="btn btn-sm btn-outline-secondary" href="#sjr7-info">Informasi Project</a><a class="btn btn-sm btn-outline-primary" href="#sjr7-items">Item & Persiapan Teknis</a><a class="btn btn-sm btn-outline-secondary" href="#sjr7-docs">Dokumen</a><a class="btn btn-sm btn-outline-secondary" href="#sjr7-status">Status Project</a></nav>

<div class="row g-3 sjr-main-grid"><div class="col-12 col-xl-9">
<section id="sjr7-info" class="card card-md shadow-sm sjr7-section"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">▤</span><h2>Informasi Project</h2></div><div class="stat-row row row-cols-2 row-cols-lg-4 g-0"><div class="stat col"><label>Nomor SJR</label><strong>{{ $record->job_number }}</strong></div><div class="stat col"><label>Klien</label><strong>{{ $record->client_name }}</strong></div><div class="stat col"><label>Target Selesai</label><strong>{{ $record->target_date ? \Illuminate\Support\Carbon::parse($record->target_date)->format('d/m/Y') : '—' }}</strong></div><div class="stat col"><label>Total Kuantitas</label><strong>{{ collect($items)->sum('quantity') }} unit</strong></div></div><h3 style="margin-bottom:12px">Detail Brief / Spesifikasi Umum</h3><div class="brief">{!! \App\Support\SjrBriefHtml::clean($record->brief_details_html ?: e($record->brief_details)) !!}</div></section>
<section id="sjr7-items" class="card card-md shadow-sm sjr7-section"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">▱</span><h2>Daftar Item Project ({{ count($items) }})</h2></div><p class="muted">Setiap item berisi preview, desain disetujui, dan Persiapan Teknis khusus item tersebut.</p>
@if(session('sjr_technical_success'))<div class="notice-success alert alert-success">{{ session('sjr_technical_success') }}</div>@endif
@if($errors->any())<div class="notice-error alert alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="tech-overview"><span>Draft teknis tersimpan: <b>{{ $technicalDrafts->count() }}/{{ count($items) }} item</b></span><span>@if($technicalFinalization)<b>READY_FOR_HPP · Seluruh item dikunci</b>@else Pengisian draft tidak otomatis mengirim ke HPP.@endif</span></div>
@if($technicalFinalization)
<div class="notice-success alert alert-success" role="status">✓ Persiapan Teknis difinalisasi pada {{ \Illuminate\Support\Carbon::parse($technicalFinalization->finalized_at)->format('d/m/Y H:i') }}. Ketiga item read-only. Belum ada perhitungan HPP.</div>
@elseif($technicalReady && $canBriefReview)
<div class="info-note alert alert-info" style="margin:12px 0 16px"><b>Finalisasi Persiapan Teknis Multi-Item</b><p style="margin:6px 0">Tindakan ini memvalidasi seluruh {{ count($items) }} item secara serentak. Jika ada data kurang, tidak ada item yang dikunci.</p>
<form method="post" action="{{ route('sjr.production.multi.technical.finalize',$record->job_number) }}" onsubmit="return confirm('Finalisasi seluruh item dan kunci Persiapan Teknis? HPP tidak akan dijalankan.');">@csrf
<label style="display:flex;gap:8px;align-items:center;margin-bottom:10px"><input type="checkbox" name="confirm" value="1" required> Saya telah memeriksa seluruh data teknis item.</label>
<button class="review-button btn btn-primary" type="submit">Konfirmasi Siap Hitung HPP · Kunci Semua Item</button></form></div>
@endif
<!-- SJR_UI_V6_STAGE1 -->
<div class="sjr-v6-tabs nav nav-tabs card-header-tabs" id="sjr-v6-tabs" role="tablist" aria-label="Pilih item proyek">
@foreach($items as $tabItem)
<button type="button" class="sjr-v6-tab nav-link" role="tab" data-sjr-v6-item="{{ $tabItem['item_id'] }}" aria-selected="false">Item {{ $tabItem['index'] }} · {{ $tabItem['name'] }}</button>
@endforeach
</div>
@foreach($items as $item)
<details class="item-card" {{ ($loop->first || (int)session('sjr_technical_item')===(int)$item['item_id']) ? 'open' : '' }}><summary>
@if(isset($previews[$item['item_id']]) && $previews[$item['item_id']]->isNotEmpty())<img class="thumb" loading="lazy" src="{{ route('sjr.production.multi.preview',[$record->job_number,$previews[$item['item_id']]->first()->id]) }}" alt="Preview {{ $item['name'] }}">@else<div class="thumb thumb-empty">▧</div>@endif
<div class="item-main"><small>ITEM {{ $item['index'] }}</small><b>{{ $item['name'] }}</b><small>{{ $item['dimensions'] }} {{ $item['dimension_unit'] }} · Qty {{ $item['quantity'] }} · {{ isset($previews[$item['item_id']]) ? $previews[$item['item_id']]->count() : 0 }} preview</small></div><div class="item-right"><span class="pill {{ $item['requires_designer'] ? 'green' : 'gray' }} badge bg-blue-lt">{{ $item['requires_designer'] ? 'Desain Disetujui' : 'Tanpa Designer' }}</span><small>Lihat detail ⌄</small></div>
</summary><div class="item-body">
@if(isset($previews[$item['item_id']]) && $previews[$item['item_id']]->isNotEmpty())<h3>Preview Item ({{ $previews[$item['item_id']]->count() }})</h3><div class="preview-strip">@foreach($previews[$item['item_id']] as $preview)<a target="_blank" rel="noopener" href="{{ route('sjr.production.multi.preview',[$record->job_number,$preview->id]) }}" title="{{ $preview->original_name }}"><img loading="lazy" src="{{ route('sjr.production.multi.preview',[$record->job_number,$preview->id]) }}" alt="{{ $preview->original_name }}"></a>@endforeach</div>@endif
@if($item['requires_designer'])<h3 style="margin:17px 0 10px">Hasil Desain Disetujui Marketing · Versi {{ $item['approved_version'] }}</h3>
@if($item['approved_submission_id'] && isset($designFiles[$item['approved_submission_id']]) && $designFiles[$item['approved_submission_id']]->isNotEmpty())<div class="table-scroll table-responsive"><table class="mini-table table table-vcenter table-striped"><thead><tr><th>No.</th><th>Nama File</th><th>Tipe</th><th>Aksi</th></tr></thead><tbody>@foreach($designFiles[$item['approved_submission_id']] as $df)<tr><td>{{ $loop->iteration }}</td><td>{{ $df->original_name }}</td><td><span class="file-tag badge bg-blue-lt bg-azure-lt">{{ strtoupper(pathinfo($df->original_name, PATHINFO_EXTENSION)) }}</span></td><td><a class="action-icon btn btn-icon btn-outline-primary btn-sm" style="width:30px;height:30px" title="Unduh {{ $df->original_name }}" aria-label="Unduh {{ $df->original_name }}" href="{{ route('sjr.production.multi.designfile',[$record->job_number,$df->id]) }}">⇩</a></td></tr>@endforeach</tbody></table></div>@else<p class="muted">Tidak ada file desain yang terlampir.</p>@endif
@else<p class="muted" style="margin-bottom:0">Item ini tidak membutuhkan proses Designer.</p>@endif
@php($td=$technicalDrafts[$item['item_id']]??null)
@php($materials=$td ? (json_decode($td->materials_json,true)?:[]) : [])
@php($processes=$td ? (json_decode($td->processes_json,true)?:[]) : [])
<div class="inline-tech" id="technical-item-{{ $item['item_id'] }}">
<div class="inline-tech-head"><div class="tech-heading-copy"><span class="tech-heading-icon" aria-hidden="true">⚙</span><h3>Persiapan Teknis Item {{ $item['index'] }}</h3></div><span class="pill {{ $td?'green':'gray' }} badge bg-blue-lt">{{ $technicalFinalization?'READY_FOR_HPP':($td?'Draft Tersimpan':'Belum Diisi') }}</span></div>
@if(!$technicalReady)
<div class="readonly-note alert alert-secondary">Persiapan Teknis tersedia setelah brief dinyatakan <b>Brief Cukup Jelas</b>.</div>
@else
@if($canEditTechnical)
<form class="tech-form" method="post" enctype="multipart/form-data" action="{{ route('sjr.production.multi.technical.save',[$record->job_number,$item['item_id']]) }}" data-tech-form>@csrf
<div class="dynamic-material-section"><h3>Kebutuhan Material</h3>
@php($materialFields=[
 'sheet'=>[['thickness_mm','Ketebalan (mm)'],['length_cm','Panjang (cm)'],['width_cm','Lebar (cm)']],
 'profile'=>[['profile_size','Ukuran Profil'],['wall_mm','Ketebalan Dinding (mm)'],['length_m','Panjang (m)']],
 'pipe'=>[['diameter','Diameter / Ukuran'],['length_m','Panjang (m)']],
 'cable'=>[['cable_type','Jenis / Ukuran'],['length_m','Panjang (m)']],
 'liquid'=>[['variant','Warna / Varian'],['volume_l','Volume (liter)']],
 'hardware'=>[['type','Tipe / Diameter'],['length_mm','Panjang (mm)']],
 'other'=>[['description','Spesifikasi']],
])
<div class="table-scroll table-responsive"><table class="tech-table material-tech-table table table-vcenter"><tbody data-rows="materials">
@foreach(count($materials)?$materials:[['name'=>'','spec'=>'','unit'=>'','quantity'=>'','category'=>'sheet','attributes'=>[],'custom_attributes'=>[]]] as $mi=>$m)
@php($cat=in_array($m['category']??'sheet',array_keys($materialFields),true)?($m['category']??'sheet'):'sheet')
<tr class="dynamic-material-row"><td class="dynamic-material-content">
<div class="dynamic-material-grid">
<div class="mm05-first-row sjr-new-material-request-ui" style="grid-column:1/-1;display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:9px;align-items:end">
<label>Material terpilih<input class="form-control tech-field mm05-selected-label" readonly value="{{ $m['name']??'Belum dipilih' }}" placeholder="Pilih dari Master Material"></label>
<button type="button" class="tech-add mm05-open-picker btn btn-outline-primary btn-sm">Cari Master Material</button>
<button type="button" class="tech-add sjr-new-material-toggle btn btn-outline-primary btn-sm" aria-pressed="{{ ($m['source_type']??'')==='new_material_request'?'true':'false' }}">+ Ajukan Material Baru</button>
</div>
<input type="hidden" class="sjr-material-source-type" name="materials[{{ $mi }}][source_type]" value="{{ $m['source_type']??((!empty($m['material_variant_id']))?'mm05_existing':'') }}">
<div class="sjr-new-material-panel card card-body border-warning" style="display:{{ ($m['source_type']??'')==='new_material_request'?'block':'none' }};grid-column:1/-1;padding:12px;border:1px solid #d7a64a;background:#fffaf0;border-radius:9px">
<strong>Pengajuan Material Baru</strong>
<p style="margin:5px 0 10px;color:#76551b;font-size:12px">Isi kebutuhan barang dan informasi yang diperlukan untuk pembelian. Data diteruskan setelah Persiapan Teknis difinalisasi.</p>
<div class="sjr-spec-field" style="display:block;margin-bottom:10px"><strong>Spesifikasi lengkap</strong>
<textarea class="form-control tech-field sjr-material-spec-visible" data-spec-visible maxlength="5000" placeholder="Tuliskan merek, tipe, ukuran/kompatibilitas, referensi toko, supplier/vendor, dan link marketplace.">{{ $m['spec']??'' }}</textarea></div>
<div class="sjr-attachment-widget">
  <strong>Attachment Material</strong>
  <div class="sjr-attachment-drop" tabindex="0" role="button" aria-label="Pilih atau tarik file lampiran">
    <span class="sjr-upload-icon">⇧</span><b>Tarik file ke sini atau klik untuk memilih</b>
    <small>PDF, gambar, DOC/DOCX, XLS/XLSX · Maksimal 5 file baru, 200 MB per file</small>
    <input class="sjr-attachment-input" type="file" name="materials[{{ $mi }}][attachments][]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
  </div>
  <div class="sjr-pending-attachments" aria-live="polite"></div>
  <div class="sjr-existing-attachments">
    @foreach(($m['attachments'] ?? []) as $ai => $attachment)
    <div class="sjr-attachment-file" data-attachment-path="{{ $attachment['path'] ?? '' }}">
      <span class="sjr-file-symbol">📎</span><span class="sjr-file-name">{{ $attachment['original_name'] ?? 'Lampiran' }}<small>{{ number_format(($attachment['size'] ?? 0)/1048576,2) }} MB · Tersimpan</small></span>
      <a class="sjr-file-action" title="Download" aria-label="Download {{ $attachment['original_name'] ?? 'lampiran' }}" href="{{ route('sjr.production.multi.material.attachment', [$record->job_number, $item['item_id'], $mi, $ai]) }}">↓</a>
      <button type="button" class="sjr-file-action sjr-remove-saved" title="Hapus lampiran" aria-label="Hapus {{ $attachment['original_name'] ?? 'lampiran' }}">×</button>
    </div>
    @endforeach
  </div>
  <input class="sjr-existing-attachment-data" type="hidden" name="materials[{{ $mi }}][existing_attachments]" value="{{ !empty($m['attachments']) ? json_encode($m['attachments'], JSON_UNESCAPED_UNICODE) : '[]' }}">
  <small class="sjr-attachment-hint">Penghapusan lampiran tersimpan berlaku setelah klik Simpan Draft.</small>
</div>
</div>
<div class="sjr-material-category-wrap" style="display:none">
<label>Jenis Material<select class="form-control tech-field material-category" name="materials[{{ $mi }}][category]" data-material-category>
@foreach(['sheet'=>'Lembaran / Plat','profile'=>'Batangan / Profil','pipe'=>'Pipa','cable'=>'Kabel / Gulungan','liquid'=>'Cairan / Cat','hardware'=>'Aksesori / Hardware','other'=>'Material Lainnya'] as $k=>$label)
<option value="{{ $k }}" {{ $cat===$k?'selected':'' }}>{{ $label }}</option>
@endforeach</select></label>
<label>Referensi MM-05<select class="form-control tech-field mm05-select" name="materials[{{ $mi }}][material_variant_id]"><option value="">Legacy / belum dipetakan</option>@foreach($mm05Options as $opt)<option value="{{ $opt['id'] }}" {{ (string)($m['material_variant_id']??'')===(string)$opt['id']?'selected':'' }}>{{ $opt['label'] }}</option>@endforeach</select></label>
</div>
<div class="mm05-reference-panel" style="grid-column:1/-1;display:none;padding:12px;background:#edf4ff;border:1px solid #c7d9f6;border-radius:8px;font-size:12px;line-height:1.6" aria-live="polite"></div>
<label class="mm05-legacy-name" style="grid-column:1/-1">Nama Material / Barang<input class="form-control tech-field" name="materials[{{ $mi }}][name]" value="{{ $m['name']??'' }}" placeholder="Contoh: PVC Board" maxlength="180"></label>
</div>
<div class="dynamic-material-grid material-standard-fields" data-standard-fields>
@foreach($materialFields[$cat] as $field)
<label>{{ $field[1] }}<input class="form-control tech-field" name="materials[{{ $mi }}][attributes][{{ $field[0] }}]" value="{{ $m['attributes'][$field[0]]??'' }}" maxlength="180"></label>
@endforeach
</div>
<div class="v6-sizes" data-v6-sizes style="margin:12px 0;padding:12px;border:1px solid #d8e5f5;border-radius:9px">
<strong>Daftar Ukuran Kebutuhan (Multi-Size)</strong>
<!-- MM05_JOIN_MANUAL_V1: explicit manual selection, never auto-enabled -->
<label class="mm05-join-control" style="display:none;margin-top:12px;padding:10px;border:1px solid #cbdcf3;border-radius:7px;background:#f6f9ff;font-weight:650">Mode pengerjaan batang
<select class="form-control tech-field mm05-join-mode" name="materials[{{ $mi }}][cut_settings][join_mode]" style="margin-top:6px">
<option value="cut" {{ ($m['cut_settings']['join_mode']??'cut')==='cut'?'selected':'' }}>Potong biasa (tanpa sambungan)</option>
<option value="join" {{ ($m['cut_settings']['join_mode']??'cut')==='join'?'selected':'' }}>Dengan sambungan — dipilih manual Production</option>
</select>
<small style="display:block;font-weight:400;margin-top:6px">Sambungan memerlukan pemeriksaan teknis; simulasi tidak mengesahkan kekuatan sambungan.</small>
</label>
<div class="dynamic-material-grid" style="margin-top:10px;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px">
<label>Kerf / lebar potong (cm)<input class="form-control tech-field v7-kerf" type="number" min="0" max="5" step="0.01" name="materials[{{ $mi }}][cut_settings][kerf_cm]" value="{{ $m['cut_settings']['kerf_cm']??'0' }}"></label>
<label>Margin tepi lembar (cm)<input class="form-control tech-field v7-margin" type="number" min="0" max="50" step="0.1" name="materials[{{ $mi }}][cut_settings][edge_margin_cm]" value="{{ $m['cut_settings']['edge_margin_cm']??'0' }}"></label>
</div>
<small style="display:block;color:#557;margin:5px 0 9px">Isi sesuai mesin dan SOP produksi. Margin diterapkan pada keempat tepi; kerf menjadi jarak antar-potongan. Nilai awal 0 berarti belum diterapkan.</small>
<div data-v6-size-rows>
@foreach((!empty($m['cut_sizes']) && is_array($m['cut_sizes']) ? $m['cut_sizes'] : [['length_cm'=>$m['attributes']['length_cm']??'', 'width_cm'=>$m['attributes']['width_cm']??'', 'quantity'=>$m['quantity']??'']]) as $si=>$cut)
<div class="v6-size-row" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end;margin:9px 0">
<label>Panjang (cm)<input class="form-control tech-field v6-length" type="number" step="0.01" min="0.01" name="materials[{{ $mi }}][cut_sizes][{{ $si }}][length_cm]" value="{{ $cut['length_cm']??'' }}"></label>
<label>Lebar (cm)<input class="form-control tech-field v6-width" type="number" step="0.01" min="0.01" name="materials[{{ $mi }}][cut_sizes][{{ $si }}][width_cm]" value="{{ $cut['width_cm']??'' }}"></label>
<label>Jumlah (pcs)<input class="form-control tech-field v6-qty" type="number" step="1" min="1" name="materials[{{ $mi }}][cut_sizes][{{ $si }}][quantity]" value="{{ $cut['quantity']??'' }}"></label>
<button type="button" class="tech-remove v6-remove-size btn btn-outline-danger btn-sm" title="Hapus ukuran">✕</button>
</div>
@endforeach
</div>
<button type="button" class="tech-add v6-add-size btn btn-outline-primary btn-sm">+ Tambah Ukuran</button>
<small style="display:block;margin-top:8px;color:#557">Ukuran lama tetap tersimpan untuk kompatibilitas. Perhitungan gabungan menggunakan daftar ukuran ini.</small>
</div>
<div class="dynamic-material-grid dynamic-quantity-fields"><label>Jumlah potongan (pcs)<input class="form-control tech-field" type="number" step="any" min="0.001" name="materials[{{ $mi }}][quantity]" value="{{ $m['quantity']??'' }}" placeholder="1"></label><label>Satuan Kebutuhan<select class="form-control tech-field" name="materials[{{ $mi }}][unit]">
@foreach([''=>'Pilih satuan','pcs'=>'Pcs','lembar'=>'Lembar','batang'=>'Batang','meter'=>'Meter','roll'=>'Roll','liter'=>'Liter','kg'=>'Kg','kaleng'=>'Kaleng','set'=>'Set'] as $unitValue=>$unitLabel)
<option value="{{ $unitValue }}" {{ ($m['unit']??'')===$unitValue?'selected':'' }}>{{ $unitLabel }}</option>
@endforeach</select></label></div>

<textarea class="sjr-material-spec-hidden" name="materials[{{ $mi }}][spec]" style="display:none">{{ $m['spec']??'' }}</textarea>
<input type="hidden" class="manual-cut-layout" name="materials[{{ $mi }}][cut_layout_json]" value="{{ isset($m['cut_layout']) ? json_encode($m['cut_layout'], JSON_UNESCAPED_UNICODE) : '' }}">
<div class="mm05-calculation" style="margin-top:14px;padding:12px;border:1px solid #d8e5f5;background:#f7faff;border-radius:8px;font-size:12px;line-height:1.8" aria-live="polite">Pilih material untuk menghitung kebutuhan bahan.</div>
</td><td class="dynamic-material-action"><button type="button" class="tech-remove material-remove-row btn btn-outline-danger btn-sm" title="Hapus material" aria-label="Hapus material">✕</button></td></tr>
@endforeach
</tbody></table></div><button type="button" class="tech-add btn btn-outline-primary btn-sm" data-add="materials">+ Tambah Material</button></div>
<div><h3>Proses / Jasa</h3><div class="table-scroll table-responsive"><table class="tech-table table table-vcenter"><thead><tr><th>Proses / Jasa</th><th>Deskripsi</th><th>Jumlah</th><th>Harga (Rp)</th><th>Aksi</th></tr></thead><tbody data-rows="processes">
@foreach(count($processes)?$processes:[['name'=>'','spec'=>'','unit'=>'','quantity'=>'']] as $pi=>$p)
<tr><td><input class="form-control tech-field" name="processes[{{ $pi }}][name]" value="{{ $p['name']??'' }}" placeholder="Cutting CNC"><input type="hidden" name="processes[{{ $pi }}][unit]" value="{{ $p['unit']??'' }}"></td><td><input class="form-control tech-field" name="processes[{{ $pi }}][spec]" value="{{ $p['spec']??'' }}" placeholder="Pemotongan"></td><td><input class="form-control tech-field" type="number" step="any" min="0.001" name="processes[{{ $pi }}][quantity]" value="{{ $p['quantity']??'' }}" placeholder="1"></td><td><input class="form-control tech-field tech-price" type="number" min="0" max="999999999999" step="1" name="processes[{{ $pi }}][unit_price]" value="{{ $p['unit_price']??'' }}" placeholder="0" aria-label="Harga satuan rupiah"></td><td><button type="button" class="tech-remove btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()" title="Hapus baris">✕</button></td></tr>
@endforeach</tbody></table></div><button type="button" class="tech-add btn btn-outline-primary btn-sm" data-add="processes">+ Tambah Proses / Jasa</button><p class="muted">Harga diisi oleh Asst. Manager Produksi. Nilai ini masih draft estimasi proses/jasa, belum menjadi HPP yang disetujui.</p></div>
<div><h3>Estimasi Lead Time Produksi</h3><label style="display:block;max-width:320px">Durasi (hari kerja)<input class="form-control tech-field" type="number" name="leadtime_days" min="1" max="3650" step="1" value="{{ old('leadtime_days', $td?->leadtime_days) }}" placeholder="Contoh: 5"><small class="muted">Diisi oleh Assistant Manager Produksi sebagai estimasi durasi pengerjaan item ini.</small></label></div>
<div><h3>Catatan</h3><textarea class="form-control tech-field" name="instructions" maxlength="10000" placeholder="Catatan pengerjaan untuk item ini...">{{ $td?->instructions }}</textarea></div>
<div class="tech-foot">@if($td)<small>Terakhir disimpan {{ \Illuminate\Support\Carbon::parse($td->saved_at)->format('d/m/Y H:i') }}</small>@endif <button class="review-button btn btn-primary" type="submit">Simpan Draft Item</button></div>
</form>
@else
<div class="tech-form">@if($td)<span class="tech-saved">Draft tersimpan</span><p>Material: {{ count($materials) }} baris · Proses/Jasa: {{ count($processes) }} baris</p><p><b>Estimasi biaya Proses/Jasa: Rp {{ number_format(collect($processes)->sum(fn($p)=>(float)($p['quantity']??0)*(float)($p['unit_price']??0)),0,',','.') }}</b></p><p><b>Lead Time Produksi:</b> {{ $td->leadtime_days ? $td->leadtime_days.' hari kerja' : 'Belum diisi' }}</p><p><b>Catatan:</b> {{ $td->instructions ?: '—' }}</p>@else<p class="muted">Belum ada draft teknis item.</p>@endif<div class="readonly-note alert alert-secondary">{{ $technicalFinalization ? 'Persiapan teknis terkunci setelah finalisasi READY_FOR_HPP.' : 'Hanya Asst. Manager Produksi dapat mengisi draft teknis pada checkpoint ini.' }}</div></div>
@endif
@endif
</div></div></details>
@endforeach</section>
</div><div class="col-12 col-xl-3"><section id="sjr7-docs" class="card card-md shadow-sm sjr7-section"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">⇧</span><h2>Lampiran Dokumen Project ({{ $attachments->count() }})</h2></div>@forelse($attachments as $file)<div class="doc-row"><div style="min-width:0"><strong>{{ $file->original_name }}</strong><small>{{ number_format($file->byte_size/1048576,2) }} MB · {{ $file->attachment_note ?: 'Tanpa catatan' }}</small></div><a class="action-icon btn btn-icon btn-outline-primary btn-sm" href="{{ route('sjr.production.multi.download',[$record->job_number,$file->id]) }}" title="Unduh {{ $file->original_name }}" aria-label="Unduh {{ $file->original_name }}">⇩</a></div>@empty<p class="muted">Tidak ada lampiran Project.</p>@endforelse</section>
<section id="sjr7-status" class="card card-md shadow-sm sjr7-section"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">◷</span><h2>Status & Alur Project</h2></div><p><span class="pill badge bg-blue-lt">Diterima Produksi</span></p><div class="info-note alert alert-info">Project lengkap diterima dari Marketing pada {{ \Illuminate\Support\Carbon::parse($record->sent_at)->format('d/m/Y H:i') }}. Pemeriksaan brief multi-item tersedia. Draft persiapan teknis per item tersedia setelah Brief Cukup Jelas.</div><p class="muted" style="margin-bottom:0">Data handover ini hanya-baca; tidak ada pengiriman parsial dan tidak ada proses HPP otomatis.</p></section>
<section class="card" id="brief-review"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">☑</span><h2>Pemeriksaan Brief Produksi</h2></div>
@if(session('sjr_review_success'))<div class="notice-success alert alert-success" role="status">{{ session('sjr_review_success') }}</div>@endif
@if($errors->any())<div class="notice-error alert alert-danger" role="alert">@foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach</div>@endif
<p><span class="pill {{ $briefReview?->decision==='CLEAR' ? 'green' : ($briefReview?->decision==='CLARIFICATION'?'amber':'gray') }} badge bg-blue-lt">{{ $briefReview?->decision==='CLEAR' ? 'Brief Cukup Jelas' : ($briefReview?->decision==='CLARIFICATION'?'Perlu Klarifikasi':'Belum Diperiksa') }}</span></p>
@if($briefReview && $briefReview->note)<p class="info-note alert alert-info" style="white-space:pre-wrap">{{ $briefReview->note }}</p>@endif
@if($canBriefReview && (!$briefReview || $briefReview->decision!=='CLEAR'))
<form class="review-form" method="post" action="{{ route('sjr.production.multi.brief.review',$record->job_number) }}">@csrf
<label class="decision"><input type="radio" name="decision" value="CLEAR" required {{ old('decision')==='CLEAR'?'checked':'' }}><div><span>Brief Cukup Jelas</span><small>Data Project lengkap. Pemeriksaan disimpan, tetapi Persiapan Teknis baru aktif pada tahap berikutnya.</small></div></label>
@if(!$briefReview || $briefReview->decision!=='CLARIFICATION')<label class="decision"><input type="radio" name="decision" value="CLARIFICATION" required {{ old('decision')==='CLARIFICATION'?'checked':'' }}><div><span>Minta Klarifikasi</span><small>Mencatat pertanyaan Produksi. Pengiriman dan balasan Marketing belum terintegrasi pada V5.7.1.</small></div></label>@endif
<label for="brief-review-note" style="font-weight:700;font-size:11px">Catatan pemeriksaan</label>
<textarea class="review-textarea" id="brief-review-note" name="note" maxlength="3000" placeholder="Catatan wajib jika meminta klarifikasi. Maks. 3.000 karakter.">{{ old('note') }}</textarea>
<button class="review-button btn btn-primary" type="submit">Simpan Keputusan Pemeriksaan</button>
</form>
@elseif(!$canBriefReview)<div class="readonly-note alert alert-secondary">Hanya Asst. Manager Produksi yang dapat menyimpan keputusan. Akun lain hanya dapat melihat hasil pemeriksaan.</div>
@else<div class="readonly-note alert alert-secondary">Pemeriksaan brief telah selesai. Pemeriksaan selesai. Draft Persiapan Teknis dapat diisi pada bagian kiri halaman.</div>@endif
</section>
<section class="card card-md shadow-sm"><div class="section-title card-header"><span class="iconbox avatar avatar-sm bg-blue-lt text-blue">◷</span><h2>Riwayat Pemeriksaan</h2></div>
@if($briefHistory->isEmpty())<p class="muted">Belum ada keputusan pemeriksaan.</p>@else<div class="review-history">@foreach($briefHistory as $event)<div class="review-event"><b>{{ $event->decision==='CLEAR'?'Brief Cukup Jelas':'Minta Klarifikasi' }}</b><p class="muted">{{ $event->reviewer_name ?: 'Petugas Produksi' }} · {{ \Illuminate\Support\Carbon::parse($event->reviewed_at)->format('d/m/Y H:i') }}</p>@if($event->note)<p style="white-space:pre-wrap">{{ $event->note }}</p>@endif</div>@endforeach</div>@endif
</section>
</div></div>
</main></div></div></div></div>
<div id="mm05-picker" class="mm05-modal" hidden role="dialog" aria-modal="true" aria-labelledby="mm05-picker-title">
<div class="mm05-modal-card"><div class="mm05-modal-head"><strong id="mm05-picker-title">Pilih Master Material MM-05</strong><button type="button" class="tech-add btn btn-outline-primary btn-sm" id="mm05-picker-close">Tutup ✕</button></div>
<input id="mm05-picker-search" class="form-control tech-field" placeholder="Cari material, spesifikasi, SKU, atau varian..." aria-label="Cari material" autocomplete="off">
<div class="mm05-modal-table-wrap"><table class="mm05-modal-table"><thead><tr><th>Material / Varian</th><th>Spesifikasi</th><th>Vendor</th><th>Harga Referensi</th></tr></thead><tbody id="mm05-picker-results"></tbody></table></div>
<p class="muted">Harga hanya referensi MM-05 yang memenuhi syarat; estimasi bukan HPP final. Maksimum 100 hasil per pencarian.</p></div></div>
<style>
.mm05-modal[hidden]{display:none!important}.mm05-modal{position:fixed;inset:0;background:rgba(10,25,50,.65);z-index:9999;display:flex;justify-content:center;align-items:center;padding:18px}.mm05-modal-card{background:#fff;border-radius:13px;width:min(1050px,100%);max-height:90vh;display:flex;flex-direction:column;padding:20px;gap:12px;box-shadow:0 12px 50px #142b4a55}.mm05-modal-head{display:flex;align-items:center;justify-content:space-between}.mm05-modal-table-wrap{overflow:auto;min-height:0}.mm05-modal-table{border-collapse:collapse;width:100%;font-size:12px}.mm05-modal-table th,.mm05-modal-table td{padding:11px;border-bottom:1px solid #e2e9f3;text-align:left;vertical-align:top}.mm05-modal-table th{position:sticky;top:0;background:#f3f7fc}.mm05-modal-table td{overflow-wrap:anywhere}.mm05-legacy-name{display:none}.mm05-calculation strong{color:#163e75}

.mm05-picker-row-selectable{cursor:pointer}.mm05-picker-row-selectable:hover,.mm05-picker-row-selectable:focus-visible{background:#eaf2ff;outline:2px solid #82a9f6;outline-offset:-2px}
.dynamic-material-row.sjr-is-new-request .mm05-legacy-name{display:block!important}
.dynamic-material-row.sjr-is-new-request .sjr-material-category-wrap{display:block!important}
.dynamic-material-row.sjr-is-new-request .mm05-reference-panel{display:none!important}
.dynamic-material-row.sjr-is-new-request .mm05-calculation{display:none!important}
.dynamic-material-row.sjr-is-new-request .v6-sizes{display:none!important}
.dynamic-material-row.sjr-is-new-request .dynamic-quantity-fields label:has(select[name$="[unit]"]){display:block!important}
</style><script>
(function(){
  var mm05Catalog = @json($mm05Options);
  function mm05Rupiah(n){return 'Rp '+Math.round(n).toLocaleString('id-ID');}
  function mm05Thickness(spec){
    var m=String(spec||'').match(/(?:^|[^0-9])([0-9]+(?:[.,][0-9]+)?)\s*mm\b/i);
    return m ? Number(m[1].replace(',','.')) : null;
  }
  var picker=document.getElementById('mm05-picker'),pickerSearch=document.getElementById('mm05-picker-search'),pickerResults=document.getElementById('mm05-picker-results'),pickerRow=null;
  function priceText(o){return o.reference_price===null||o.reference_price===undefined?'Harga belum tersedia':'Rp '+Number(o.reference_price).toLocaleString('id-ID',{maximumFractionDigits:2})+' / '+(o.unit_label||o.unit);}


  function mm05MaterialName(o){
    var label=String(o.label||'').trim();
    var spec=String(o.spec||'').trim();

    // Hilangkan spesifikasi tekstual di akhir label
    if(spec && label.endsWith(' - '+spec)){
      label=label.slice(0,-(spec.length+3));
    }

    // Hilangkan ukuran yang tertera di akhir nama varian
    label=label.replace(
      /\s*-\s*\d+(?:[.,]\d+)?\s*[x×]\s*\d+(?:[.,]\d+)?(?:\s*-\s*\d+(?:[.,]\d+)?\s*(?:mm|cm|m))?\s*$/i,
      ''
    );

    return label||'—';
  }

  function mm05Specification(o){
    var attrs=Array.isArray(o.specification_attributes)
      ?o.specification_attributes:[];

    var result=attrs.map(function(a){
      var value=String(a.value??'').trim();
      if(!value)return '';
      var name=String(a.name||'').trim();
      var unit=String(a.unit||'').trim();
      return (name?name+': ':'')+value+(unit?' '+unit:'');
    }).filter(Boolean).join(' · ');

    return result||String(o.spec||'').trim()||'—';
  }
  function renderPicker(){
    var term=pickerSearch.value.trim().toLocaleLowerCase('id-ID');pickerResults.replaceChildren();
    var found=mm05Catalog.filter(function(o){return !term||[
      o.label,
      o.spec,
      o.sku,
      mm05Specification(o),
      o.price_vendor
    ].join(' ').toLocaleLowerCase('id-ID').includes(term);}).slice(0,100);
    found.forEach(function(o){var tr=document.createElement('tr');
      tr.className='mm05-picker-row-selectable';tr.tabIndex=0;tr.setAttribute('role','button');
      tr.setAttribute('aria-label','Pilih '+o.label);
      [mm05MaterialName(o),mm05Specification(o),o.price_vendor||'Belum tersedia',priceText(o)].forEach(function(t){var td=document.createElement('td');td.textContent=t;tr.appendChild(td);});
      var choose=function(){if(!pickerRow)return;var s=pickerRow.querySelector('.mm05-select');if(!s)return;s.value=String(o.id);mm05Apply(s,true);picker.hidden=true;};
      tr.addEventListener('click',choose);
      tr.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();choose();}});
      pickerResults.appendChild(tr);
    });
    if(!found.length){var tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=4;td.textContent='Material tidak ditemukan.';tr.appendChild(td);pickerResults.appendChild(tr);}
  }
  function sjrPrepareNewRequestRow(row, enabled){
    if(!row)return;
    var panel=row.querySelector('.sjr-new-material-panel');
    var nameLabel=row.querySelector('.mm05-legacy-name');
    var qtyWrap=row.querySelector('.dynamic-quantity-fields');
    // Query label langsung; anchor pemulihan dapat menjadi child pertama.
    var qtyLabel=qtyWrap ? qtyWrap.querySelector('label') : null;
    var unitLabel=qtyWrap ? qtyWrap.querySelector('label:has(select[name$="[unit]"])') : null;
    var specVisible=row.querySelector('[data-spec-visible]');
    var specHidden=row.querySelector('.sjr-material-spec-hidden');

    function ensureHomeAnchor(field, className){
      if(!field || row.querySelector('.'+className))return;
      var anchor=document.createElement('span');
      anchor.className=className;
      anchor.hidden=true;
      field.parentNode.insertBefore(anchor,field);
    }
    function restoreField(field, className){
      var anchor=row.querySelector('.'+className);
      if(field && anchor && anchor.parentNode)anchor.parentNode.insertBefore(field,anchor.nextSibling);
    }

    ensureHomeAnchor(nameLabel,'sjr-name-home-anchor');
    ensureHomeAnchor(qtyLabel,'sjr-quantity-home-anchor');

    if(panel){
      var basicGrid=panel.querySelector('.sjr-new-material-basic-grid');
      if(!basicGrid){
        basicGrid=document.createElement('div');
        basicGrid.className='sjr-new-material-basic-grid';
      }
      if(enabled){
        if(nameLabel)basicGrid.appendChild(nameLabel);
        if(qtyLabel)basicGrid.appendChild(qtyLabel);
        var specLabel=panel.querySelector('.sjr-spec-field');
        // SJR_FIX_NEW_MATERIAL_INSERT_BEFORE
        var reference=specLabel || panel.querySelector('label');
        while(reference && reference.parentElement!==panel){
          reference=reference.parentElement;
        }
        if(reference){
          panel.insertBefore(basicGrid,reference);
        }else{
          panel.appendChild(basicGrid);
        }
      }else{
        restoreField(nameLabel,'sjr-name-home-anchor');
        restoreField(qtyLabel,'sjr-quantity-home-anchor');
        if(basicGrid.parentNode)basicGrid.remove();
      }
    }

    if(unitLabel)unitLabel.style.display=enabled?'none':'';
    if(nameLabel)nameLabel.style.display=enabled?'block':'';
    if(qtyLabel){
      var qInput=qtyLabel.querySelector('input');
      if(qtyLabel.firstChild)qtyLabel.firstChild.textContent=enabled?'Quantity':'Jumlah potongan (pcs)';
      if(qInput){qInput.placeholder=enabled?'Contoh: 4':'1';qInput.required=!!enabled;}
    }
    if(qtyWrap)qtyWrap.style.display=enabled?'none':'';
    if(specVisible && specHidden){
      specVisible.style.display=enabled?'block':'none';
      if(enabled)specVisible.value=specHidden.value||specVisible.value;
      specVisible.required=!!enabled;
      specVisible.oninput=function(){specHidden.value=specVisible.value;};
    }
    var standard=row.querySelector('[data-standard-fields]');if(standard)standard.style.display=enabled?'none':'';
    var sizes=row.querySelector('[data-v6-sizes]');if(sizes)sizes.style.display=enabled?'none':'';
    var calc=row.querySelector('.mm05-calculation');if(calc)calc.style.display=enabled?'none':'';
  }
  function setNewMaterialMode(row, enabled){
    if(!row)return;
    var source=row.querySelector('.sjr-material-source-type');
    var reason=row.querySelector('.sjr-material-request-reason');
    var panel=row.querySelector('.sjr-new-material-panel');
    var categoryWrap=row.querySelector('.sjr-material-category-wrap');
    var select=row.querySelector('.mm05-select');
    var name=row.querySelector('input[name$="[name]"]');
    var unit=row.querySelector('select[name$="[unit]"]');
    var hiddenUnit=row.querySelector('.mm05-unit-hidden');
    row.classList.toggle('sjr-is-new-request',!!enabled);
    sjrPrepareNewRequestRow(row,!!enabled);
    if(panel)panel.style.display=enabled?'block':'none';
    if(categoryWrap)categoryWrap.style.display=enabled?'block':'none';
    if(source)source.value=enabled?'new_material_request':'';
    if(reason){reason.required=!!enabled;if(!enabled)reason.value='';}
    if(enabled){
      if(select)select.value='';
      if(hiddenUnit)hiddenUnit.remove();
      if(name)name.readOnly=false;
      if(unit)unit.disabled=false;
      var selected=row.querySelector('.mm05-selected-label');
      if(selected)selected.value=name&&name.value?name.value:'Material baru — belum terdaftar di MM-05';
      row.classList.remove('mm05-awaiting-selection');
      var standard=row.querySelector('[data-standard-fields]');if(standard)standard.style.display='';
      var sizes=row.querySelector('[data-v6-sizes]');if(sizes)sizes.style.display='none';
      var calc=row.querySelector('.mm05-calculation');if(calc)calc.style.display='none';
    }else{
      if(select)select.value='';
      mm05Apply(select);
    }
  }
  document.addEventListener('click',function(e){
    var toggle=e.target.closest('.sjr-new-material-toggle');
    if(toggle){
      var row=toggle.closest('.dynamic-material-row');
      var enabled=!row.classList.contains('sjr-is-new-request');
      setNewMaterialMode(row,enabled);
      toggle.setAttribute('aria-pressed',enabled?'true':'false');
      toggle.textContent=enabled?'Batalkan Pengajuan':'+ Ajukan Material Baru';
      return;
    }
    var b=e.target.closest('.mm05-open-picker');if(!b)return;
    pickerRow=b.closest('.dynamic-material-row');
    if(pickerRow && pickerRow.classList.contains('sjr-is-new-request')){
      setNewMaterialMode(pickerRow,false);
      var t=pickerRow.querySelector('.sjr-new-material-toggle');
      if(t){t.setAttribute('aria-pressed','false');t.textContent='+ Ajukan Material Baru';}
    }
    picker.hidden=false;pickerSearch.value='';renderPicker();pickerSearch.focus();
  });
  document.getElementById('mm05-picker-close').addEventListener('click',function(){picker.hidden=true;});
  picker.addEventListener('click',function(e){if(e.target===picker)picker.hidden=true;});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!picker.hidden)picker.hidden=true;});
  pickerSearch.addEventListener('input',renderPicker);
  function sheetSize(o){
    var length=Number(o.sheet_length_cm),width=Number(o.sheet_width_cm);
    if(o.sheet_length_cm!==null&&o.sheet_width_cm!==null&&Number.isFinite(length)&&Number.isFinite(width)&&length>0&&width>0){return [length,width];}
    return null;
  }
  function v6Sync(row){
    var sizes=Array.from(row.querySelectorAll('.v6-size-row')).map(function(r){return {p:Number(r.querySelector('.v6-length').value),l:Number(r.querySelector('.v6-width').value),q:Number(r.querySelector('.v6-qty').value)};});
    var first=sizes[0];if(!first)return sizes;
    var oldP=row.querySelector('input[name$="[attributes][length_cm]"]'),oldL=row.querySelector('input[name$="[attributes][width_cm]"]'),oldQ=row.querySelector('.dynamic-quantity-fields input[name$="[quantity]"]') /* SJR-V6.2 quantity selector fix */;
    if(oldP)oldP.value=first.p>0?first.p:'';if(oldL)oldL.value=first.l>0?first.l:'';
    if(oldQ)oldQ.value=sizes.reduce(function(n,x){return n+(Number.isInteger(x.q)&&x.q>0?x.q:0);},0)||'';
    return sizes;
  }
  document.addEventListener('click',function(e){
    var add=e.target.closest('.v6-add-size'),remove=e.target.closest('.v6-remove-size');if(!add&&!remove)return;
    var row=e.target.closest('.dynamic-material-row');if(!row)return;
    var box=row.querySelector('[data-v6-size-rows]');if(!box)return;
    if(add){if(box.children.length>=30)return;var el=box.firstElementChild.cloneNode(true),prefix=row.querySelector('[data-material-category]').name.replace(/\[category\]$/,'');
      var next=Math.max(-1,...Array.from(box.querySelectorAll('.v6-length')).map(function(i){var m=i.name.match(/\[cut_sizes\]\[(\d+)\]/);return m?Number(m[1]):-1;}))+1;
      el.querySelectorAll('input').forEach(function(input){input.name=input.name.replace(/materials\[\d+\]\[cut_sizes\]\[\d+\]/,prefix+'[cut_sizes]['+next+']');input.value=input.classList.contains('v6-qty')?'1':'';});box.appendChild(el);mm05BarUi(row,mm05IsBar(mm05Catalog.find(function(o){return String(o.id)===row.querySelector('.mm05-select')?.value;})));
    }else if(box.children.length>1){remove.closest('.v6-size-row').remove();}
    else{remove.closest('.v6-size-row').querySelectorAll('input').forEach(function(input){input.value='';});}
    recalcMaterial(row);
  });
  // SJR-V6.1 quantity synchronization guard
  // Some number-input controls commit their final value on change/blur.
  // Recalculate from current DOM values, not a cached previous quantity.
  function v61Refresh(e){
    if(!e.target || !e.target.closest || !e.target.closest('.v6-size-row'))return;
    var row=e.target.closest('.dynamic-material-row');
    if(row)recalcMaterial(row);
  }
  document.addEventListener('input',v61Refresh);
  document.addEventListener('change',v61Refresh);
  document.addEventListener('blur',v61Refresh,true);
  document.addEventListener('keyup',function(e){
    if(e.target && e.target.matches && e.target.matches('.v6-qty,.v6-length,.v6-width'))v61Refresh(e);
  });
  document.querySelectorAll('form[data-tech-form]').forEach(function(form){
    form.addEventListener('submit',function(){
      form.querySelectorAll('.dynamic-material-row').forEach(function(row){
        if(row.querySelector('.v6-size-row'))recalcMaterial(row);
      });
    },true);
  });
  // SJR-V7: deterministic mixed-size shelf nesting with rotation, kerf and edge margins.
  // This is a practical heuristic, not a mathematically global-optimal nesting solver.
  // SJR Manual Nesting V1: interactive SVG editor with collision/bounds validation.
  function renderV63Layout(row,result,sheets,sz,settings){
    var wrap=document.createElement('section');wrap.className='sjr-manual-nesting';
    wrap.style.cssText='margin:14px 0;padding:14px;background:#fff;border:1px solid #cdddf6;border-radius:12px;box-shadow:0 2px 9px rgba(27,65,120,.05)';
    var head=document.createElement('div');head.style.cssText='display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap';
    var title=document.createElement('strong');title.style.cssText='font-weight:800;color:#183762;font-size:13px';title.textContent='✂ Rencana Pemotongan per Lembar';head.appendChild(title);
    var status=document.createElement('span');status.style.cssText='font-size:11px;color:#176b45;font-weight:700';status.textContent='Validasi aktif';head.appendChild(status);wrap.appendChild(head);
    var toolbar=document.createElement('div');toolbar.style.cssText='display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px;padding:10px 0 12px;border-bottom:1px solid #e7edf7';wrap.appendChild(toolbar);
    function button(label,fn){var b=document.createElement('button');b.type='button';b.className='tech-add';b.textContent=label;b.addEventListener('click',fn);toolbar.appendChild(b);return b;}
    var palette=['#347de1','#10a8aa','#7858d8','#dd8c25','#d45b8a','#308b61','#687cbd','#bd6948'];
    var hidden=row.querySelector('.manual-cut-layout');
    var seed=[];sheets.forEach(function(sh,si){sh.placed.forEach(function(p,pi){seed.push({id:'p'+(seed.length+1),group:p.group,sheet:si,x:p.x,y:p.y,w:p.w,h:p.h,base_w:p.w,base_h:p.h,rotated:!!p.rotated});});});
    var state=seed;
    if(hidden&&hidden.value){try{var saved=JSON.parse(hidden.value);if(saved&&Array.isArray(saved.pieces)&&saved.stock_width===sz[0]&&saved.stock_height===sz[1]&&saved.pieces.length===seed.length){var candidate=saved.pieces.map(function(p){return {id:String(p.id),group:Number(p.group),sheet:Number(p.sheet),x:Number(p.x),y:Number(p.y),w:Number(p.w),h:Number(p.h),base_w:Number(p.base_w||(p.rotated?p.h:p.w)),base_h:Number(p.base_h||(p.rotated?p.w:p.h)),rotated:!!p.rotated};});if(candidate.every(function(p){return Number.isFinite(p.x)&&Number.isFinite(p.y)&&Number.isFinite(p.w)&&Number.isFinite(p.h)&&Number.isFinite(p.base_w)&&Number.isFinite(p.base_h)&&p.w>0&&p.h>0&&((Math.abs(p.w-p.base_w)<1e-7&&Math.abs(p.h-p.base_h)<1e-7)||(Math.abs(p.w-p.base_h)<1e-7&&Math.abs(p.h-p.base_w)<1e-7))&&Number.isInteger(p.sheet)&&p.sheet>=0&&p.x>=settings.margin&&p.y>=settings.margin&&p.x+p.w<=sz[0]-settings.margin+1e-7&&p.y+p.h<=sz[1]-settings.margin+1e-7;})&&candidate.every(function(p,i){return !candidate.slice(i+1).some(function(q){return p.sheet===q.sheet&&p.x<q.x+q.w-1e-7&&p.x+p.w>q.x+1e-7&&p.y<q.y+q.h-1e-7&&p.y+p.h>q.y+1e-7;});})&&candidate.every(function(p){return seed.filter(function(q){return q.group===p.group&&Math.abs(q.w*q.h-p.w*p.h)<1e-6&&((Math.abs(q.w-p.w)<1e-6&&Math.abs(q.h-p.h)<1e-6)||(Math.abs(q.w-p.h)<1e-6&&Math.abs(q.h-p.w)<1e-6));}).length===candidate.filter(function(q){return q.group===p.group&&Math.abs(q.w*q.h-p.w*p.h)<1e-6&&((Math.abs(q.w-p.w)<1e-6&&Math.abs(q.h-p.h)<1e-6)||(Math.abs(q.w-p.h)<1e-6&&Math.abs(q.h-p.w)<1e-6));}).length;})&&candidate.every(function(p){var expected=seed.filter(function(q){return q.group===p.group;}).reduce(function(a,q){return a+q.w*q.h;},0),actual=candidate.filter(function(q){return q.group===p.group;}).reduce(function(a,q){return a+q.w*q.h;},0);return Math.abs(expected-actual)<Math.max(0.05,expected*0.00001);})){state=candidate;}}}catch(e){}}
    var selected=state.length?state[0].id:null,activeSheet=0,drag=null,canvasHost=document.createElement('div');wrap.appendChild(canvasHost);
    var colorLegend=document.createElement('div');colorLegend.style.cssText='display:flex;gap:13px;flex-wrap:wrap;border-top:1px solid #e7edf7;padding-top:11px;margin-top:13px;font-size:10px;color:#3e5573';
    var groups=Array.from(new Set(seed.map(function(p){return p.group;}))).sort(function(a,b){return a-b;});
    groups.forEach(function(group){var piece=seed.find(function(p){return p.group===group;}),count=seed.filter(function(p){return p.group===group;}).length;var item=document.createElement('span');item.style.cssText='display:inline-flex;align-items:center;gap:5px';var sw=document.createElement('i');sw.style.cssText='display:inline-block;width:19px;height:11px;border-radius:3px;background:'+palette[(group-1)%palette.length];item.appendChild(sw);item.appendChild(document.createTextNode('Potongan '+piece.base_w+' × '+piece.base_h+' cm ('+count+' pcs)'));colorLegend.appendChild(item);});
    var remItem=document.createElement('span');remItem.style.cssText='display:inline-flex;align-items:center;gap:5px';var remSw=document.createElement('i');remSw.style.cssText='display:inline-block;width:19px;height:11px;border:1px solid #cbd9ed;border-radius:3px;background:repeating-linear-gradient(135deg,#edf3fc,#edf3fc 5px,#dce8f8 5px,#dce8f8 9px)';remItem.appendChild(remSw);remItem.appendChild(document.createTextNode('Sisa material'));colorLegend.appendChild(remItem);wrap.appendChild(colorLegend);
    var legend=document.createElement('div');legend.style.cssText='font-size:10px;color:#526782;margin-top:9px';wrap.appendChild(legend);
    function overlap(a,b){return a.x<b.x+b.w-1e-7&&a.x+a.w>b.x+1e-7&&a.y<b.y+b.h-1e-7&&a.y+a.h>b.y+1e-7;}
    function valid(p,ignoreId){if(p.x<settings.margin-1e-7||p.y<settings.margin-1e-7||p.x+p.w>sz[0]-settings.margin+1e-7||p.y+p.h>sz[1]-settings.margin+1e-7)return false;return !state.some(function(q){if(q.id===ignoreId||q.sheet!==p.sheet)return false;if(overlap(p,q))return true;if(settings.kerf>0){var k=settings.kerf;return p.x<q.x+q.w+k-1e-7&&p.x+p.w+k>q.x+1e-7&&p.y<q.y+q.h+k-1e-7&&p.y+p.h+k>q.y+1e-7;}return false;});}
    function save(){if(hidden){hidden.value=JSON.stringify({version:1,stock_width:sz[0],stock_height:sz[1],kerf:settings.kerf,margin:settings.margin,pieces:state});}}
    function render(){canvasHost.replaceChildren();var sheetCount=Math.max(1,...state.map(function(p){return p.sheet+1;}));
      for(let si=0;si<sheetCount;si++){
        var section=document.createElement('div');section.style.cssText='margin:10px 0 12px;padding:12px;border:1px solid '+(activeSheet===si?'#8fb5f1':'#dce7f6')+';border-radius:10px;background:#fff';
        var shHead=document.createElement('div');shHead.style.cssText='display:flex;justify-content:space-between;align-items:center;margin-bottom:11px;gap:8px;flex-wrap:wrap';
        var shTitle=document.createElement('div');shTitle.style.cssText='display:inline-flex;flex-direction:column;align-items:center;justify-content:center;min-width:88px;padding:7px 11px;border:1px solid #c9d9f1;border-radius:8px;background:#f3f7fe;color:#1a3764;font-size:11px;line-height:1.45';var shName=document.createElement('strong');shName.textContent='Lembar '+(si+1);shTitle.appendChild(shName);var shDim=document.createElement('span');shDim.textContent=sz[0]+' × '+sz[1]+' cm';shTitle.appendChild(shDim);shHead.appendChild(shTitle);
        var shBtn=document.createElement('button');shBtn.type='button';shBtn.className='tech-add';shBtn.textContent=activeSheet===si?'Lembar aktif':'Pilih lembar';shBtn.addEventListener('click',function(idx){return function(){activeSheet=idx;render();};}(si));shHead.appendChild(shBtn);
        if(!state.some(function(p){return p.sheet===si;})){
          var del=document.createElement('button');del.type='button';del.className='tech-remove';del.textContent='Hapus lembar kosong';del.style.cssText='width:auto;height:auto;padding:7px 9px';
          del.addEventListener('click',function(idx){return function(){
            if(state.some(function(p){return p.sheet===idx;}))return;
            state.forEach(function(p){if(p.sheet>idx)p.sheet--;});
            activeSheet=Math.max(0,Math.min(activeSheet,Math.max(0,...state.map(function(p){return p.sheet;}))));
            save();render();status.textContent='Lembar kosong dihapus';status.style.color='#176b45';
          };}(si));shHead.appendChild(del);
        }
        section.appendChild(shHead);
        let svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox','0 0 '+sz[1]+' '+sz[0]);svg.setAttribute('role','group');svg.setAttribute('aria-label','Editor potongan lembar '+(si+1));svg.style.cssText='display:block;width:100%;max-width:760px;height:auto;touch-action:none;background:#fff;border:1px solid #cddbf1;border-radius:8px';
        // SJR-NESTING-V7-LANDSCAPE-PERMANENT: visual rotation only; no stock geometry or saved layout migration.
        let drawingLayer=document.createElementNS(svg.namespaceURI,'g');
        drawingLayer.setAttribute('transform','translate('+sz[1]+' 0) rotate(90)');drawingLayer.setAttribute('data-v7-coordinate-layer','1');
        svg.appendChild(drawingLayer);
        if(settings.margin>0){var mr=document.createElementNS(svg.namespaceURI,'rect');[['x',settings.margin],['y',settings.margin],['width',sz[0]-2*settings.margin],['height',sz[1]-2*settings.margin],['fill','none'],['stroke','#c04444'],['stroke-dasharray','2 1'],['stroke-width',Math.max(sz[0],sz[1])/500]].forEach(function(a){mr.setAttribute(a[0],a[1]);});drawingLayer.appendChild(mr);}
        state.filter(function(p){return p.sheet===si;}).forEach(function(p){var g=document.createElementNS(svg.namespaceURI,'g');g.dataset.pieceId=p.id;g.style.cursor='grab';var rect=document.createElementNS(svg.namespaceURI,'rect');[['x',p.x],['y',p.y],['width',p.w],['height',p.h],['fill',palette[(p.group-1)%palette.length]],['stroke',p.id===selected?'#153f94':'#ffffff'],['stroke-width',p.id===selected?Math.max(sz[0],sz[1])/150:Math.max(sz[0],sz[1])/500]].forEach(function(a){rect.setAttribute(a[0],a[1]);});g.appendChild(rect);var txt=document.createElementNS(svg.namespaceURI,'text');txt.setAttribute('x',p.x+p.w/2);txt.setAttribute('y',p.y+p.h/2);txt.setAttribute('text-anchor','middle');txt.setAttribute('dominant-baseline','middle');var originalW=Number(p.base_w)||p.w,originalH=Number(p.base_h)||p.h;
        var formatCm=function(n){return Number(n.toFixed(2)).toLocaleString('id-ID',{maximumFractionDigits:2});};
        var sizeLabel=formatCm(originalW)+' × '+formatCm(originalH)+' cm';
        // Text remains horizontal after rotation; fit the label inside the piece.
        var fontSize=Math.min(6,Math.max(1.6,p.h*0.22),p.w/(sizeLabel.length*0.59));
        txt.setAttribute('font-size',Math.max(1.4,fontSize));
        txt.setAttribute('font-weight','750');txt.setAttribute('fill','#ffffff');
        txt.setAttribute('pointer-events','none');txt.setAttribute('transform','rotate(-90 '+(p.x+p.w/2)+' '+(p.y+p.h/2)+')');txt.textContent=sizeLabel;g.appendChild(txt);
          g.addEventListener('pointerdown',function(e){
            if(e.button!==0 && e.pointerType==='mouse')return;
            e.preventDefault();selected=p.id;activeSheet=si;
            var pt=point(e,svg);
            drag={id:p.id,pointerId:e.pointerId,svg:svg,dx:pt.x-p.x,dy:pt.y-p.y,
              original:{x:p.x,y:p.y,sheet:p.sheet},preview:{x:p.x,y:p.y}};
            svg.setPointerCapture(e.pointerId);
            // Keep the SVG mounted throughout the drag; do not rerender on click.
            svg.querySelectorAll('[data-piece-id]').forEach(function(el){
              var r=el.querySelector('rect');if(r)r.setAttribute('stroke-width',el.dataset.pieceId===p.id?Math.max(sz[0],sz[1])/150:Math.max(sz[0],sz[1])/500);
            });
          });
          drawingLayer.appendChild(g);});
        svg.addEventListener('pointermove',function(e){
          if(!drag||drag.svg!==svg||drag.pointerId!==e.pointerId)return;
          var p=state.find(function(q){return q.id===drag.id;});if(!p)return;
          var pt=point(e,svg),lo=settings.margin;
          var x=Math.max(lo,Math.min(sz[0]-lo-p.w,pt.x-drag.dx));
          var y=Math.max(lo,Math.min(sz[1]-lo-p.h,pt.y-drag.dy));
          drag.preview={x:x,y:y};
          var el=Array.from(svg.querySelectorAll('[data-piece-id]')).find(function(g){return g.dataset.pieceId===p.id;});
          if(el){var r=el.querySelector('rect'),t=el.querySelector('text'),candidate=Object.assign({},p,{x:x,y:y});
            r.setAttribute('x',x);r.setAttribute('y',y);
            r.setAttribute('stroke',valid(candidate,p.id)?'#153f94':'#cf354a');
            t.setAttribute('x',x+p.w/2);t.setAttribute('y',y+p.h/2);t.setAttribute('transform','rotate(-90 '+(x+p.w/2)+' '+(y+p.h/2)+')');
          }
        });
        function finishDrag(e,cancelled){
          if(!drag||drag.svg!==svg||drag.pointerId!==e.pointerId)return;
          var d=drag;drag=null;
          var p=state.find(function(q){return q.id===d.id;});if(!p)return;
          var chosen=null;
          if(!cancelled){
            var x=d.preview.x,y=d.preview.y,k=settings.kerf,threshold=Math.max(1.2,Math.min(3,Math.min(sz[0],sz[1])*0.025));
            // Magnetic guides: stock edges and both sides of other pieces, kerf-aware.
            var xs=[settings.margin,sz[0]-settings.margin-p.w],ys=[settings.margin,sz[1]-settings.margin-p.h];
            state.forEach(function(q){if(q.id===p.id||q.sheet!==p.sheet)return;
              xs.push(q.x+q.w+k,q.x-p.w-k);ys.push(q.y+q.h+k,q.y-p.h-k);
            });
            var cx=xs.filter(function(v){return Math.abs(v-x)<=threshold;});
            var cy=ys.filter(function(v){return Math.abs(v-y)<=threshold;});
            cx.push(x);cy.push(y);
            var choices=[];
            cx.forEach(function(xx){cy.forEach(function(yy){
              var c=Object.assign({},p,{x:xx,y:yy});
              if(valid(c,p.id))choices.push({c:c,score:(xx-x)*(xx-x)+(yy-y)*(yy-y),snapped:xx!==x||yy!==y});
            });});
            choices.sort(function(a,b){return a.score-b.score||(Number(b.snapped)-Number(a.snapped));});
            chosen=choices.length?choices[0].c:null;
          }
          if(chosen){p.x=chosen.x;p.y=chosen.y;status.textContent='Posisi tersimpan · magnetic snap aktif';status.style.color='#176b45';}
          else{p.x=d.original.x;p.y=d.original.y;status.textContent=cancelled?'Pemindahan dibatalkan':'Posisi ditolak: bertabrakan atau melanggar kerf';status.style.color=cancelled?'#526782':'#b42335';}
          save();render();
        }
        svg.addEventListener('pointerup',function(e){finishDrag(e,false);});
        svg.addEventListener('pointercancel',function(e){finishDrag(e,true);});
        svg.addEventListener('lostpointercapture',function(e){if(drag&&drag.svg===svg)finishDrag(e,true);});
        section.appendChild(svg);canvasHost.appendChild(section);
      }
      var p=state.find(function(q){return q.id===selected;});legend.textContent=p?'Terpilih: potongan '+p.id+' · ukuran '+p.w+' × '+p.h+' cm · posisi X '+p.x.toFixed(1)+' / Y '+p.y.toFixed(1)+' cm · lembar '+(p.sheet+1):'Belum ada potongan.';
      rotateBtn.disabled=!p;nextBtn.disabled=!p;newBtn.disabled=!p;refreshTargets();}
    function point(e,svg){var pt=svg.createSVGPoint();pt.x=e.clientX;pt.y=e.clientY;var layer=svg.querySelector('g[data-v7-coordinate-layer]');var m=(layer||svg).getScreenCTM();if(m){pt=pt.matrixTransform(m.inverse());}return {x:pt.x,y:pt.y};}
    function renderLive(svg,si){var old=svg.querySelectorAll('g');old.forEach(function(g){var p=state.find(function(q){return q.id===g.dataset.pieceId;});if(!p)return;var r=g.querySelector('rect'),t=g.querySelector('text');r.setAttribute('x',p.x);r.setAttribute('y',p.y);t.setAttribute('x',p.x+p.w/2);t.setAttribute('y',p.y+p.h/2);});}
    // V2: rotate in place when possible; otherwise find the nearest legal placement
    // on the SAME sheet. All candidates pass the existing margin/kerf/collision validator.
    function nearestRotatedPlacement(p){
      var w=p.h,h=p.w,lo=settings.margin,hiX=sz[0]-settings.margin-w,hiY=sz[1]-settings.margin-h;
      if(hiX<lo-1e-7||hiY<lo-1e-7)return null;
      var xs=[lo,hiX,p.x,p.x+(p.w-w)/2],ys=[lo,hiY,p.y,p.y+(p.h-h)/2];
      state.forEach(function(q){if(q.id===p.id||q.sheet!==p.sheet)return;
        xs.push(q.x-w-settings.kerf,q.x+q.w+settings.kerf,q.x,q.x+q.w-w);
        ys.push(q.y-h-settings.kerf,q.y+q.h+settings.kerf,q.y,q.y+q.h-h);
      });
      function coords(values,hi){return [...new Set(values.filter(Number.isFinite).map(function(v){return Math.round(Math.max(lo,Math.min(hi,v))*1000000)/1000000;}))];}
      xs=coords(xs,hiX);ys=coords(ys,hiY);
      var best=null;
      xs.forEach(function(x){ys.forEach(function(y){var c=Object.assign({},p,{x:x,y:y,w:w,h:h,rotated:!p.rotated});
        if(!valid(c,p.id))return;
        var score=Math.pow((x+w/2)-(p.x+p.w/2),2)+Math.pow((y+h/2)-(p.y+p.h/2),2);
        if(!best||score<best.score-1e-7)best={candidate:c,score:score};
      });});
      return best&&best.candidate;
    }
    var rotateBtn=button('Putar 90°',function(){
      var p=state.find(function(q){return q.id===selected;});if(!p)return;
      var c=Object.assign({},p,{w:p.h,h:p.w,rotated:!p.rotated});
      var moved=false;
      if(!valid(c,p.id)){c=nearestRotatedPlacement(p);moved=!!c;}
      if(c){p.x=c.x;p.y=c.y;p.w=c.w;p.h=c.h;p.rotated=c.rotated;
        status.textContent=moved?'Rotasi berhasil · dipindahkan ke ruang terdekat':'Rotasi berhasil di posisi semula';
        status.style.color='#176b45';save();render();
      }else{status.textContent='Rotasi tidak memungkinkan: tidak ada ruang valid pada lembar ini. Geser potongan lain atau gunakan Pindah lembar.';status.style.color='#b42335';}
    });
    function moveSelected(target){
      var p=state.find(function(q){return q.id===selected;});if(!p)return;
      if(target===p.sheet){status.textContent='Potongan sudah berada pada lembar itu';return;}
      // Search legal positions on destination sheet, including edges of existing pieces.
      var xs=[settings.margin,sz[0]-settings.margin-p.w],ys=[settings.margin,sz[1]-settings.margin-p.h];
      state.forEach(function(q){if(q.sheet!==target||q.id===p.id)return;
        xs.push(q.x+q.w+settings.kerf,q.x-p.w-settings.kerf);
        ys.push(q.y+q.h+settings.kerf,q.y-p.h-settings.kerf);
      });
      var best=null;
      xs.forEach(function(x){ys.forEach(function(y){var c=Object.assign({},p,{sheet:target,x:x,y:y});
        if(valid(c,p.id)&&(!best||y<best.y-1e-7||(Math.abs(y-best.y)<1e-7&&x<best.x)))best=c;
      });});
      if(!best){status.textContent='Tidak ada ruang valid pada lembar tujuan';status.style.color='#b42335';return;}
      p.sheet=target;p.x=best.x;p.y=best.y;
      // Compact trailing empty sheets automatically; preserve indices of occupied sheets.
      activeSheet=target;save();render();status.textContent='Potongan dipindahkan ke Lembar '+(target+1);status.style.color='#176b45';
    }
    var targetSelect=document.createElement('select');targetSelect.className='tech-field';targetSelect.style.cssText='width:auto;max-width:165px;height:35px';targetSelect.setAttribute('aria-label','Lembar tujuan');toolbar.appendChild(targetSelect);
    var nextBtn=button('Pindah ke lembar dipilih',function(){moveSelected(Number(targetSelect.value));});
    var newBtn=button('+ Tambah lembar baru',function(){moveSelected(Math.max(1,...state.map(function(p){return p.sheet+1;})));});
    function refreshTargets(){var count=Math.max(1,...state.map(function(p){return p.sheet+1;}));var current=targetSelect.value;targetSelect.replaceChildren();
      for(var i=0;i<count;i++){var option=document.createElement('option');option.value=String(i);option.textContent='Lembar '+(i+1);targetSelect.appendChild(option);}
      var selectedPiece=state.find(function(p){return p.id===selected;});
      var other=Array.from(targetSelect.options).find(function(o){return !selectedPiece||Number(o.value)!==selectedPiece.sheet;});
      targetSelect.value=other?other.value:(current&&Number(current)<count?current:'0');
    }
    button('Kembalikan susunan otomatis',function(){state=seed.map(function(p){return Object.assign({},p);});selected=state[0]?.id||null;activeSheet=0;save();status.textContent='Susunan otomatis dipulihkan';status.style.color='#176b45';render();});
    render();save();result.appendChild(wrap);
  }
  function v7Nest(panels,stockW,stockH,kerf,margin){
    var usableW=stockW-2*margin,usableH=stockH-2*margin;
    if(!(usableW>0&&usableH>0))return {error:'Margin terlalu besar dibanding ukuran lembar master.'};
    var sheets=[];
    function orientations(p){return p.w===p.h?[[p.w,p.h,false]]:[[p.w,p.h,false],[p.h,p.w,true]];}
    var invalidPanel=false;
    panels.forEach(function(panel){
      if(invalidPanel)return;
      var best=null;
      // Try every existing shelf and both orientations; choose the tightest fit.
      sheets.forEach(function(sheet,si){sheet.shelves.forEach(function(shelf,ri){
        orientations(panel).forEach(function(o){var w=o[0],h=o[1],x=shelf.usedWidth+(shelf.placed?kerf:0);
          if(h<=shelf.h+1e-8&&x+w<=usableW+1e-8){var score=(usableW-(x+w))+Math.max(0,shelf.h-h)*0.20;
            if(!best||score<best.score)best={kind:'shelf',sheet:si,shelf:ri,x:x,y:shelf.y,w:w,h:h,rotated:o[2],score:score};}
        });
      });});
      if(best){var sh=sheets[best.sheet],sl=sh.shelves[best.shelf];sl.usedWidth=best.x+best.w;sl.placed=true;sh.placed.push({x:margin+best.x,y:margin+best.y,w:best.w,h:best.h,group:panel.group,rotated:best.rotated});return;}
      // No shelf fits: open a new shelf on an existing sheet if possible.
      var newShelf=null;
      sheets.forEach(function(sheet,si){orientations(panel).forEach(function(o){var w=o[0],h=o[1],y=sheet.usedHeight+(sheet.shelves.length?kerf:0);
        if(w<=usableW+1e-8&&y+h<=usableH+1e-8){var score=(usableH-(y+h))+(usableW-w)*0.20;
          if(!newShelf||score<newShelf.score)newShelf={sheet:si,y:y,w:w,h:h,rotated:o[2],score:score};}
      });});
      if(newShelf){var sh=sheets[newShelf.sheet];sh.shelves.push({y:newShelf.y,h:newShelf.h,usedWidth:newShelf.w,placed:true});sh.usedHeight=newShelf.y+newShelf.h;sh.placed.push({x:margin,y:margin+newShelf.y,w:newShelf.w,h:newShelf.h,group:panel.group,rotated:newShelf.rotated});return;}
      // Create a new stock sheet; choose orientation with the least remaining bounding waste.
      var choices=orientations(panel).filter(function(o){return o[0]<=usableW+1e-8&&o[1]<=usableH+1e-8;}).map(function(o){return {w:o[0],h:o[1],rotated:o[2],score:(usableW-o[0])*(usableH-o[1])};}).sort(function(a,b){return a.score-b.score;});
      if(!choices.length){invalidPanel=true;return;}
      var c=choices[0],sheet={usedHeight:c.h,shelves:[{y:0,h:c.h,usedWidth:c.w,placed:true}],placed:[{x:margin,y:margin,w:c.w,h:c.h,group:panel.group,rotated:c.rotated}]};sheets.push(sheet);
    });
    if(invalidPanel)return {error:'Ada ukuran potongan yang tidak muat pada area lembar setelah margin. Periksa ukuran, orientasi, atau margin.'};
    return {sheets:sheets,usableArea:usableW*usableH};
  }

  // MM05_BAR_V1 - kalkulator batang independen; tidak mengubah v7Nest.
  function mm05IsBar(o){
    if(!o)return false;
    var u=String(o.unit_label||o.unit||'').toLowerCase();
    return /\bbatang\b/.test(u)||String(o.unit||'').toLowerCase()==='btg';
  }
  function mm05BarLength(o){
    var a=Array.isArray(o.specification_attributes)?o.specification_attributes:[];
    var x=a.find(function(a){return /panjang|length/i.test(String(a.name||''));});
    if(!x)return null;
    var n=Number(String(x.value).replace(',','.'));if(!(n>0))return null;
    var u=String(x.unit||'').toLowerCase().trim();
    if(u==='mm'||/milimeter|millimeter/.test(u))return n/10;
    if(u==='cm'||/sentimeter|centimeter/.test(u))return n;
    if(u==='m'||/^(meter|metre)$/.test(u))return n*100;
    if(u==='km'||/kilometer/.test(u))return n*100000;
    return null; // satuan tak dikenal: jangan mengarang konversi
  }
  // MM05_JOIN_MANUAL_V1: manual-only joins; estimated segments, no structural approval.
  function mm05BarCalc(row,o,result,line){
    var length=mm05BarLength(o);
    if(!length){line('Panjang master atau satuannya belum tersedia di MM-05.');return;}
    var rows=Array.from(row.querySelectorAll('.v6-size-row'));
    var requests=[],total=0;
    for(var i=0;i<rows.length;i++){
      var p=Number(rows[i].querySelector('.v6-length').value),q=Number(rows[i].querySelector('.v6-qty').value);
      if(!(p>0)||!Number.isFinite(p)||!Number.isInteger(q)||q<1){line('Isi panjang dan jumlah pcs yang valid.');return;}
      if(total+q>2500){line('Maksimum 2.500 potongan per simulasi.');return;}
      total+=q;for(var j=0;j<q;j++)requests.push(p);
    }
    var kerf=Number(row.querySelector('.v7-kerf')?.value||0),margin=Number(row.querySelector('.v7-margin')?.value||0);
    if(!Number.isFinite(kerf)||kerf<0||kerf>5||!Number.isFinite(margin)||margin<0||margin>50){line('Kerf 0–5 cm, margin 0–50 cm.');return;}
    var capacity=length-2*margin,mode='join'; // MM05_JOIN_AUTO_V2
    if(capacity<=kerf){line('Kapasitas batang tidak cukup setelah kerf dan margin.');return;}
    if(mode!=='join'&&requests.some(function(p){return p+kerf>capacity+1e-8;})){
      line('Potongan melebihi panjang master. Mode Potong Biasa tidak mengizinkan sambungan.',true);
      line('Jika pekerjaan mengizinkan, Production harus memilih “Dengan sambungan” secara manual.');return;
    }
    var qtyInput=row.querySelector('.dynamic-quantity-fields input[name$="[quantity]"]');if(qtyInput)qtyInput.value=String(total);
    var segments=[],joinCount=0,segmentLimit=5000;
    for(var r=0;r<requests.length;r++){
      var remain=requests[r],parts=[];
      if(mode==='join'&&remain+kerf>capacity+1e-8){
        while(remain>1e-7){
          var part=Math.min(remain,capacity-kerf);
          if(!(part>0)||segments.length>=segmentLimit){line('Terlalu banyak segmen untuk disimulasikan.');return;}
          segments.push({p:part,job:r+1});parts.push(part);remain-=part;
          if(remain<1e-7)remain=0;
        }
        joinCount+=parts.length-1;
      }else segments.push({p:remain,job:r+1});
    }
    segments.sort(function(a,b){return b.p-a.p;});
    var bars=[];
    segments.forEach(function(s){var b=bars.find(function(b){return b.used+s.p+kerf<=capacity+1e-8;});if(!b){b={used:0,parts:[]};bars.push(b);}b.used+=s.p+kerf;b.parts.push(s);});
    var totalLength=requests.reduce(function(s,x){return s+x;},0);
    line('Mode: '+(mode==='join'?'SAMBUNG OTOMATIS BILA DIPERLUKAN':'POTONG BIASA')+'.',true);
    line('Metode alokasi batang: First Fit Decreasing (estimasi, bukan optimasi global).');
    line('Panjang master: '+length.toLocaleString('id-ID')+' cm | Kebutuhan: '+total+' pcs.');
    line('Pembelian: '+bars.length+' batang utuh | Segmen: '+segments.length+' | Titik sambungan: '+joinCount+'.',true);
    line('Total panjang bersih: '+totalLength.toLocaleString('id-ID')+' cm. Kerf '+kerf+' cm/segmen; margin '+margin+' cm/ujung.');
    // MM05_BAR_PLAN_UI_V1: visual only; allocation, quantity, pricing and saving unchanged.
    var plan=document.createElement('section');plan.className='mm05-bar-plan';
    plan.style.cssText='margin:14px 0;padding:14px;border:1px solid #cdddf6;border-radius:12px;background:#fff;box-shadow:0 2px 9px rgba(27,65,120,.05)';
    var heading=document.createElement('div');heading.style.cssText='font-weight:800;color:#183762;font-size:13px;margin-bottom:13px';heading.textContent='✂ Rencana Pemotongan per Batang';plan.appendChild(heading);
    var unique=Array.from(new Set(segments.map(function(s){return String(Number(s.p.toFixed(4)));}))).map(Number).sort(function(a,b){return a-b;});
    var palette=['#347de1','#10a8aa','#7858d8','#dd8c25','#d45b8a','#308b61','#687cbd','#bd6948'];
    function fmt(n){return n.toLocaleString('id-ID',{maximumFractionDigits:2});}
    function colorFor(n){var idx=unique.findIndex(function(v){return Math.abs(v-n)<0.0001;});return palette[(idx<0?0:idx)%palette.length];}
    bars.forEach(function(b,i){
      var outer=document.createElement('div');outer.style.cssText='display:flex;align-items:center;gap:10px;margin:10px 0;min-width:0';
      var badge=document.createElement('div');badge.style.cssText='width:83px;flex:0 0 83px;text-align:center;padding:8px 3px;border:1px solid #c9d9f1;border-radius:8px;background:#f3f7fe;color:#1a3764;font-size:11px;line-height:1.4';
      var strong=document.createElement('strong');strong.textContent='Batang '+(i+1);badge.appendChild(strong);
      var sub=document.createElement('div');sub.textContent=fmt(length)+' cm';badge.appendChild(sub);outer.appendChild(badge);
      var track=document.createElement('div');track.style.cssText='display:flex;flex:1;min-width:0;height:43px;overflow:hidden;border:1px solid #cddbf1;border-radius:8px;background:#f0f4fa';
      b.parts.forEach(function(part){
        var tile=document.createElement('div');tile.style.cssText='box-sizing:border-box;min-width:0;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;border-right:1px solid rgba(255,255,255,.8);color:white;font-size:10px;font-weight:750;background:'+colorFor(part.p)+';width:'+((part.p+kerf)/length*100)+'%';
        tile.title=fmt(part.p)+' cm · P'+part.job+(kerf?' · kerf '+fmt(kerf)+' cm':'');
        var label=document.createElement('span');label.style.cssText='white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;padding:0 2px';label.textContent=fmt(part.p)+' cm';tile.appendChild(label);
        if((part.p+kerf)/length>=.085){var id=document.createElement('small');id.style.cssText='font-size:9px;font-weight:500;opacity:.95';id.textContent='P'+part.job;tile.appendChild(id);}
        track.appendChild(tile);
      });
      var remainder=Math.max(0,capacity-b.used),grossRest=Math.max(0,length-b.used-2*margin);
      if(grossRest>0.00001){
        var leftover=document.createElement('div');leftover.style.cssText='min-width:0;display:flex;align-items:center;justify-content:center;text-align:center;color:#36516f;font-size:10px;font-weight:700;background:repeating-linear-gradient(135deg,#edf3fc,#edf3fc 5px,#dce8f8 5px,#dce8f8 9px);width:'+(grossRest/length*100)+'%';
        leftover.textContent=(grossRest/length>=.095?'Sisa '+fmt(remainder)+' cm':'');leftover.title='Sisa area kerja '+fmt(remainder)+' cm';track.appendChild(leftover);
      }
      outer.appendChild(track);plan.appendChild(outer);
      if(grossRest/length<.095 && remainder>0){var tiny=document.createElement('div');tiny.style.cssText='text-align:right;color:#526987;font-size:10px;margin:-7px 0 8px';tiny.textContent='Sisa batang '+(i+1)+': '+fmt(remainder)+' cm';plan.appendChild(tiny);}
    });
    var legend=document.createElement('div');legend.style.cssText='display:flex;gap:13px;flex-wrap:wrap;border-top:1px solid #e7edf7;padding-top:11px;margin-top:13px;font-size:10px;color:#3e5573';
    unique.forEach(function(n){var count=segments.filter(function(s){return Math.abs(s.p-n)<.0001;}).length;var item=document.createElement('span');item.style.cssText='display:inline-flex;align-items:center;gap:5px';var sw=document.createElement('i');sw.style.cssText='display:inline-block;width:19px;height:11px;border-radius:3px;background:'+colorFor(n);item.appendChild(sw);item.appendChild(document.createTextNode('Potongan '+fmt(n)+' cm ('+count+' segmen)'));legend.appendChild(item);});
    var left=document.createElement('span');left.style.cssText='display:inline-flex;align-items:center;gap:5px';var sw=document.createElement('i');sw.style.cssText='display:inline-block;width:19px;height:11px;border:1px solid #cbd9ed;border-radius:3px;background:repeating-linear-gradient(135deg,#edf3fc,#edf3fc 5px,#dce8f8 5px,#dce8f8 9px)';left.appendChild(sw);left.appendChild(document.createTextNode('Sisa material'));legend.appendChild(left);plan.appendChild(legend);
    result.appendChild(plan);
    if(o.reference_price===null||o.reference_price===undefined)line('Harga referensi tidak tersedia.');
    else {var price=Number(o.reference_price);line('Estimasi biaya pembelian: '+mm05Rupiah(bars.length*price)+'.',true);}
    if(mode==='join')line('PERINGATAN: simulasi tidak memasukkan overlap, bahan penguat, kekuatan sambungan, atau biaya pengelasan. Wajib verifikasi teknis Production sebelum digunakan.',true);
    line('Sisa teoritis belum menjadi stok; pola potong harus diverifikasi Production.');
  }
  function mm05BarUi(row,isBar){
    var box=row.querySelector('[data-v6-sizes]');if(!box)return;
    var joinControl=box.querySelector('.mm05-join-control');if(joinControl){joinControl.style.display='none';var joinSelect=joinControl.querySelector('select');if(joinSelect){joinSelect.value='join';joinSelect.disabled=true;}}
    var heading=box.querySelector('strong');if(heading)heading.textContent=isBar?'Daftar Panjang Potongan Batang (Multi-Size)':'Daftar Ukuran Kebutuhan (Multi-Size)';
    box.querySelectorAll('.v6-size-row').forEach(function(r){var w=r.querySelector('.v6-width');if(w){w.closest('label').style.display=isBar?'none':'';w.disabled=isBar;}});
    var margin=box.querySelector('.v7-margin');if(margin){var label=margin.closest('label');if(label)label.childNodes[0].textContent=isBar?'Margin ujung batang (cm)':'Margin tepi lembar (cm)';}
  }
  function recalcMaterial(row){
    var result=row.querySelector('.mm05-calculation');if(!result)return;
    var select=row.querySelector('.mm05-select'),o=mm05Catalog.find(function(x){return String(x.id)===select.value});
    var fmt=function(n){return Number(n).toLocaleString('id-ID',{maximumFractionDigits:2});};
    result.replaceChildren();var line=function(s,bold){var d=document.createElement('div');if(bold){var strong=document.createElement('strong');strong.textContent=s;d.appendChild(strong);}else d.textContent=s;result.appendChild(d);};
    if(!o){line('Pilih material MM-05 untuk menghitung kebutuhan lembar.');return;}
    var category=row.querySelector('[data-material-category]');if(mm05IsBar(o)){mm05BarCalc(row,o,result,line);return;}if(!category||category.value!=='sheet'){line('Kalkulator lembar hanya berlaku untuk kategori lembaran/plat.');return;}
    var sz=sheetSize(o),sizes=v6Sync(row),kerfInput=row.querySelector('.v7-kerf'),marginInput=row.querySelector('.v7-margin');
    var kerf=kerfInput&&kerfInput.value!==''?Number(kerfInput.value):0,margin=marginInput&&marginInput.value!==''?Number(marginInput.value):0;
    if(!Number.isFinite(kerf)||kerf<0||kerf>5||!Number.isFinite(margin)||margin<0||margin>50){line('Kerf harus 0–5 cm dan margin 0–50 cm.');return;}
    if(!sz||!sizes.length||sizes.some(function(x){return !(x.p>0&&x.l>0&&Number.isInteger(x.q)&&x.q>0);})){line('Isi semua ukuran dan jumlah pcs dengan angka valid. Ukuran master harus tersedia.');return;}
    var stockArea=sz[0]*sz[1],usableW=sz[0]-2*margin,usableH=sz[1]-2*margin,used=0,panels=[],details=[];
    if(!(usableW>0&&usableH>0)){line('Margin terlalu besar dibanding ukuran lembar master.');return;}
    for(var si=0;si<sizes.length;si++){
      var x=sizes[si];
      // Setiap kebutuhan adalah satu potongan utuh. Rotasi 90 derajat diperbolehkan,
      // tetapi pemotongan menjadi panel yang lebih kecil tidak dilakukan otomatis.
      var fits=(x.p<=usableW+1e-8&&x.l<=usableH+1e-8)||(x.l<=usableW+1e-8&&x.p<=usableH+1e-8);
      if(!fits){line('Ukuran '+(si+1)+' ('+x.p+' × '+x.l+' cm) tidak muat utuh pada lembar master setelah margin, termasuk bila diputar 90°. Tidak dilakukan pembagian otomatis.');return;}
      if(panels.length+x.q>2500){line('Simulasi dibatasi maksimal 2.500 potongan untuk menjaga kinerja browser.');return;}
      used+=x.p*x.l*x.q;
      for(var j=0;j<x.q;j++)panels.push({w:x.p,h:x.l,group:si+1});
      details.push('Ukuran '+(si+1)+': '+x.p+' × '+x.l+' cm, '+x.q+' pcs → '+x.q+' potongan utuh');
    }
    panels.sort(function(a,b){return Math.max(b.w,b.h)-Math.max(a.w,a.h)||b.w*b.h-a.w*a.h;});
    var nested=v7Nest(panels,sz[0],sz[1],kerf,margin);if(nested.error){line(nested.error);return;}
    var sheets=nested.sheets,sheetCount=sheets.length,netRemnant=Math.max(0,sheetCount*nested.usableArea-used),grossRemainder=Math.max(0,sheetCount*stockArea-used);
    // Ringkasan visual lembaran: hanya presentasi, tanpa mengubah perhitungan.
    var summary=document.createElement('section');summary.className='sjr-sheet-summary';
    summary.style.cssText='background:#fff;border:1px solid #d2e1f7;border-radius:12px;padding:14px;margin:2px 0 15px;color:#19375e;line-height:1.5';
    var heading=document.createElement('div');heading.style.cssText='display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:13px';
    var headingTitle=document.createElement('strong');headingTitle.style.cssText='font-size:13px;color:#173b70';headingTitle.textContent='Ringkasan Perhitungan Lembaran';heading.appendChild(headingTitle);
    var headingCount=document.createElement('span');headingCount.style.cssText='background:#e8f5ed;color:#177149;border-radius:20px;padding:5px 10px;font-size:10px;font-weight:800';headingCount.textContent=panels.length+' potongan utuh';heading.appendChild(headingCount);summary.appendChild(heading);
    var priceAvailable=o.reference_price!==null&&o.reference_price!==undefined;
    var price=priceAvailable?Number(o.reference_price):0;
    var consumed=price*used/stockArea,purchase=sheetCount*price;
    var compactStyle=document.getElementById('sjr-sheet-summary-compact-style');if(!compactStyle){compactStyle=document.createElement('style');compactStyle.id='sjr-sheet-summary-compact-style';compactStyle.textContent='@media(max-width:600px){.sjr-sheet-summary .sjr-sheet-stats{grid-template-columns:repeat(2,minmax(0,1fr)) !important}}';document.head.appendChild(compactStyle);}
    var statGrid=document.createElement('div');statGrid.className='sjr-sheet-stats';statGrid.style.cssText='display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;margin-bottom:10px';
    function stat(label,value,sub){var card=document.createElement('div');card.style.cssText='background:#f3f7fd;border:1px solid #e2ebf8;border-radius:7px;padding:7px 6px;min-width:0';var t=document.createElement('div');t.style.cssText='font-size:9px;color:#607696;font-weight:650';t.textContent=label;card.appendChild(t);var v=document.createElement('strong');v.style.cssText='display:block;color:#173e78;font-size:14px;line-height:1.4;overflow-wrap:anywhere';v.textContent=value;card.appendChild(v);var d=document.createElement('div');d.style.cssText='font-size:9px;color:#627997';d.textContent=sub;card.appendChild(d);statGrid.appendChild(card);}
    var fmtArea=function(n){return n.toLocaleString('id-ID',{maximumFractionDigits:2})+' cm²';};
    var utilization=stockArea*sheetCount>0?used/(stockArea*sheetCount)*100:0;
    stat('Kebutuhan bahan',sheetCount+' lembar',fmt(sz[0])+' × '+fmt(sz[1])+' cm per lembar');
    stat('Total luas potongan',fmtArea(used),utilization.toLocaleString('id-ID',{maximumFractionDigits:1})+'% dari luas pembelian');
    stat('Sisa area kerja teoritis',fmtArea(netRemnant),'Setelah margin; belum diverifikasi');
    stat('Biaya pembelian',priceAvailable?mm05Rupiah(purchase):'Belum tersedia',sheetCount+' lembar master');
    summary.appendChild(statGrid);
    var usageTitle=document.createElement('div');usageTitle.style.cssText='display:flex;justify-content:space-between;gap:8px;font-size:11px;font-weight:700;margin-bottom:6px';var usageName=document.createElement('span');usageName.textContent='Pemanfaatan luas lembar';var usageValue=document.createElement('span');usageValue.textContent=utilization.toLocaleString('id-ID',{maximumFractionDigits:1})+'%';usageTitle.appendChild(usageName);usageTitle.appendChild(usageValue);summary.appendChild(usageTitle);
    var progress=document.createElement('div');progress.style.cssText='height:11px;background:#dce8f7;border-radius:20px;overflow:hidden';var fill=document.createElement('div');fill.style.cssText='height:100%;width:'+Math.min(100,Math.max(0,utilization))+'%;background:#347de1';progress.appendChild(fill);summary.appendChild(progress);
    var usageFoot=document.createElement('div');usageFoot.style.cssText='font-size:10px;color:#657b99;margin:5px 0 13px';usageFoot.textContent='Luas terpakai '+fmtArea(used)+' · Sisa bruto teoritis '+fmtArea(grossRemainder);summary.appendChild(usageFoot);
    var tableTitle=document.createElement('strong');tableTitle.style.cssText='display:block;font-size:11px;margin-bottom:7px';tableTitle.textContent='Daftar Ukuran Potongan';summary.appendChild(tableTitle);
    // Gunakan CSS grid berperan sebagai tabel agar aturan .tech-table tbody tr/td
    // milik formulir induk tidak mengubah baris ringkasan menjadi kartu.
    var table=document.createElement('div');
    table.className='sjr-sheet-sizes-grid';
    table.setAttribute('role','table');
    table.setAttribute('aria-label','Daftar Ukuran Potongan');
    table.style.cssText='width:100%;border:1px solid #dce7f7;border-radius:7px;overflow:hidden;background:#fff;margin-bottom:12px;font-size:11px';
    function sizeRow(values,header){
      var r=document.createElement('div');r.setAttribute('role','row');
      r.style.cssText='display:grid;grid-template-columns:16% 54% 30%;align-items:center;min-height:30px;'+(header?'background:#edf4fd;':'border-top:1px solid #e5edf8;');
      values.forEach(function(value,j){
        var cell=document.createElement('div');cell.setAttribute('role',header?'columnheader':'cell');cell.textContent=value;
        cell.style.cssText='padding:7px 9px;min-width:0;color:#173e78;'+(header?'font-weight:700;':'')+(j===2?'text-align:right;':'');
        r.appendChild(cell);
      });table.appendChild(r);
    }
    sizeRow(['No.','Ukuran (cm)','Jumlah'],true);
    sizes.forEach(function(x,i){sizeRow([String(i+1),fmt(x.p)+' × '+fmt(x.l),x.q+' pcs'],false);});
    summary.appendChild(table);
    if(priceAvailable){var costs=document.createElement('div');costs.style.cssText='border-top:1px solid #e1eaf6;padding-top:10px;margin-bottom:10px';var costsTitle=document.createElement('strong');costsTitle.style.cssText='font-size:11px;display:block;margin-bottom:7px';costsTitle.textContent='Rincian Estimasi Biaya';costs.appendChild(costsTitle);function costLine(k,v){var r=document.createElement('div');r.style.cssText='display:flex;justify-content:space-between;gap:10px;font-size:11px;padding:4px 0;flex-wrap:wrap';var a=document.createElement('span');a.style.color='#637996';a.textContent=k;var b=document.createElement('strong');b.textContent=v;r.appendChild(a);r.appendChild(b);costs.appendChild(r);}costLine('Nilai proporsional area potongan',mm05Rupiah(consumed));costLine('Selisih nilai teoritis',mm05Rupiah(Math.max(0,purchase-consumed)));summary.appendChild(costs);}
    result.appendChild(summary);
    renderV63Layout(row,result,sheets,sz,{kerf:kerf,margin:margin});
  }
  document.addEventListener('change',function(e){if(e.target.matches('.mm05-join-mode')){var r=e.target.closest('.dynamic-material-row');if(r)recalcMaterial(r);}});
  document.addEventListener('input',function(e){if(e.target.matches('.v7-kerf,.v7-margin,input[name*="[attributes][length_cm]"],input[name*="[attributes][width_cm]"],input[name$="[quantity]"]')){var r=e.target.closest('.dynamic-material-row');if(r)recalcMaterial(r);}});
  function mm05Apply(select, fromPicker){
    if(!select)return;
    var row=select.closest('.dynamic-material-row');if(!row)return;
    var o=mm05Catalog.find(function(x){return String(x.id)===select.value});
    var source=row.querySelector('.sjr-material-source-type');
    if(o){
      row.classList.remove('sjr-is-new-request');
      if(source)source.value='mm05_existing';
      var reason=row.querySelector('.sjr-material-request-reason');if(reason){reason.required=false;reason.value='';}
      var requestPanel=row.querySelector('.sjr-new-material-panel');if(requestPanel)requestPanel.style.display='none';
      var categoryWrap=row.querySelector('.sjr-material-category-wrap');if(categoryWrap)categoryWrap.style.display='none';
      var toggle=row.querySelector('.sjr-new-material-toggle');if(toggle){toggle.setAttribute('aria-pressed','false');toggle.textContent='+ Ajukan Material Baru';}
    } else if(source && source.value==='new_material_request'){
      row.classList.add('sjr-is-new-request');
    }
    var isBar=mm05IsBar(o);if(isBar){var cat=row.querySelector('[data-material-category]');if(cat)cat.value='profile';}var isSheet=!!o && !isBar && row.querySelector('[data-material-category]')?.value==='sheet';
    var sizesBox=row.querySelector('[data-v6-sizes]');if(sizesBox)sizesBox.style.display=(isSheet||isBar)?'block':'none';mm05BarUi(row,isBar);
    var standard=row.querySelector('[data-standard-fields]');if(standard)standard.style.display=(isSheet||isBar)?'none':'';
    var qtyFields=row.querySelector('.dynamic-quantity-fields');if(qtyFields){var qtyLabel=qtyFields.querySelector('label');if(qtyLabel)qtyLabel.style.display=(isSheet||isBar)?'none':'';}
    var name=row.querySelector('input[name$="[name]"]');
    var unit=row.querySelector('select[name$="[unit]"]');
    var spec=row.querySelector('[name$="[spec]"]');
    var panel=row.querySelector('.mm05-reference-panel');
    if(o){
      if(name)name.value=o.name;
      if(spec)spec.value=o.spec;
      if(unit){
        if(!Array.from(unit.options).some(function(x){return x.value===o.unit}))unit.add(new Option(o.unit_label||o.unit,o.unit));
        var option=Array.from(unit.options).find(function(x){return x.value===o.unit});
        if(option)option.textContent=o.unit_label||o.unit;
        unit.value=o.unit;
      }
    }
    var selected=row.querySelector('.mm05-selected-label');if(selected)selected.value=o?o.label:(name&&name.value?name.value:'Legacy / belum dipetakan');
    if(name)name.readOnly=!!o;
    if(unit)unit.disabled=!!o;
    var hidden=row.querySelector('.mm05-unit-hidden');if(hidden)hidden.remove();
    if(o&&unit){hidden=document.createElement('input');hidden.type='hidden';hidden.className='mm05-unit-hidden';hidden.name=unit.name;hidden.value=o.unit;unit.parentNode.append(hidden);}
    var thicknessField=row.querySelector('input[name$="[attributes][thickness_mm]"]');
    if(thicknessField&&thicknessField.closest('label'))thicknessField.closest('label').style.display=o?'none':'';
    if(panel){
      panel.replaceChildren();panel.style.display=o?'block':'none';
      if(o){
        var heading=document.createElement('strong');heading.textContent='Informasi Master Material MM-05';panel.appendChild(heading);
        var specs=document.createElement('div');specs.style.cssText='display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px 14px;margin-top:9px';panel.appendChild(specs);
        function detail(label,value){var d=document.createElement('div');var small=document.createElement('div');small.style.cssText='font-size:11px;color:#536b8c';small.textContent=label;var val=document.createElement('strong');val.textContent=value||'—';d.appendChild(small);d.appendChild(val);specs.appendChild(d);}
        var thickness=mm05Thickness(o.label+'; '+o.spec);
        var dims=sheetSize(o);
        detail('Material / Varian',o.label);
        // Tampilkan atribut sesuai Master MM-05, bukan asumsi semua material lembaran.
        var masterAttrs=Array.isArray(o.specification_attributes)?o.specification_attributes:[];
        if(masterAttrs.length){
          masterAttrs.forEach(function(attr){
            var val=String(attr.value??'').trim();
            var unit=String(attr.unit??'').trim();
            if(val)detail(String(attr.name||'Atribut'),val+(unit?' '+unit:''));
          });
        }else{
          detail('Spesifikasi MM-05','Belum ada nilai atribut tersimpan');
        }
        if(row.querySelector('[data-material-category]')?.value==='sheet'){
          detail('Ukuran lembar master',dims?dims[0]+' × '+dims[1]+' cm':'Tidak tersedia');
        }
        detail('Satuan',o.unit_label||o.unit);
        detail('Harga referensi',priceText(o));
        detail('Vendor',o.price_vendor||'Belum tersedia');
        detail('Harga berlaku sejak',o.price_valid_from||'Belum tersedia');
        if(o.price_valid_until)detail('Berlaku sampai',o.price_valid_until);
        var thicknessInput=row.querySelector('input[name$="[attributes][thickness_mm]"]');
        if(thicknessInput){
          var thicknessLabel=thicknessInput.closest('label');
          if(thicknessLabel)thicknessLabel.style.display='none';
          if(fromPicker&&thickness!==null)thicknessInput.value=String(thickness);
          var needed=thicknessInput.value.trim()?Number(String(thicknessInput.value).replace(',','.')):null;
          if(thickness!==null&&needed!==null&&needed!==thickness){
            var warning=document.createElement('div');warning.style.cssText='color:#a02b23;font-weight:700;margin-top:9px';
            warning.textContent='Draft lama memiliki ketebalan '+needed+' mm, berbeda dari master '+thickness+' mm. Pilih ulang material dari modal untuk menggunakan ketebalan master sebelum menyimpan.';panel.appendChild(warning);
          }
        }
        var note=document.createElement('div');note.style.cssText='margin-top:8px;color:#536b8c';note.textContent='Ukuran potongan pekerjaan diisi terpisah dan tidak diubah oleh ukuran master.';panel.appendChild(note);
      }
    }
    // SJR-V6.2: tampilkan field lanjutan hanya setelah material MM-05 dipilih.
    row.classList.toggle('mm05-awaiting-selection', !o && !(source && source.value==='new_material_request'));
    recalcMaterial(row);
  }
  function hideV63Unit(){document.querySelectorAll('.dynamic-material-row select[name$="[unit]"]').forEach(function(el){var label=el.closest('label');if(label)label.style.setProperty('display','none','important');});}
  hideV63Unit();
  document.addEventListener('click',function(e){if(e.target.closest('[data-add="materials"]'))requestAnimationFrame(hideV63Unit);});
  document.addEventListener('change',function(e){if(e.target.matches('.mm05-select'))mm05Apply(e.target);if(e.target.matches('input[name$="[attributes][thickness_mm]"]')){var row=e.target.closest('.dynamic-material-row');if(row)mm05Apply(row.querySelector('.mm05-select'));}});
  document.querySelectorAll('.dynamic-material-row').forEach(function(row){
    var source=row.querySelector('.sjr-material-source-type');
    var isNew=source && source.value==='new_material_request';
    if(isNew){
      row.classList.add('sjr-is-new-request');
      var panel=row.querySelector('.sjr-new-material-panel');if(panel)panel.style.display='block';
      var categoryWrap=row.querySelector('.sjr-material-category-wrap');if(categoryWrap)categoryWrap.style.display='block';
      var reason=row.querySelector('.sjr-material-request-reason');if(reason)reason.required=true;
      var toggle=row.querySelector('.sjr-new-material-toggle');if(toggle){toggle.setAttribute('aria-pressed','true');toggle.textContent='Batalkan Pengajuan';}
    }
    sjrPrepareNewRequestRow(row,!!isNew);
  });
  document.querySelectorAll('.mm05-select').forEach(mm05Apply);
  // SJR_MULTI_ITEM_RECALC_ON_OPEN_V1
  // Each item is an independent <details>. Recalculate after it becomes visible.
  document.querySelectorAll('details.item-card').forEach(function(itemPanel){
    itemPanel.addEventListener('toggle',function(){
      if(!itemPanel.open)return;
      requestAnimationFrame(function(){
        if(!itemPanel.open)return;
        itemPanel.querySelectorAll('.dynamic-material-row').forEach(function(materialRow){
          var select=materialRow.querySelector('.mm05-select');
          if(select && select.value && !materialRow.classList.contains('sjr-is-new-request')){
            mm05Apply(select);
          }
        });
      });
    });
  });

  function rupiah(n){return 'Rp '+Math.round(n).toLocaleString('id-ID');}
  var materialTemplate={
    sheet:[['thickness_mm','Ketebalan (mm)'],['length_cm','Panjang (cm)'],['width_cm','Lebar (cm)']],
    profile:[['profile_size','Ukuran Profil'],['wall_mm','Ketebalan Dinding (mm)'],['length_m','Panjang (m)']],
    pipe:[['diameter','Diameter / Ukuran'],['length_m','Panjang (m)']],
    cable:[['cable_type','Jenis / Ukuran'],['length_m','Panjang (m)']],
    liquid:[['variant','Warna / Varian'],['volume_l','Volume (liter)']],
    hardware:[['type','Tipe / Diameter'],['length_mm','Panjang (mm)']],
    other:[['description','Spesifikasi']]
  };
  function createStandardFields(row){
    var category=row.querySelector('[data-material-category]').value;
    var prefix=row.querySelector('[data-material-category]').name.replace(/\[category\]$/,'');
    var box=row.querySelector('[data-standard-fields]');
    box.replaceChildren();
    (materialTemplate[category]||materialTemplate.other).forEach(function(field){
      var label=document.createElement('label');label.textContent=field[1];
      var input=document.createElement('input');input.className='tech-field';input.maxLength=180;
      input.name=prefix+'[attributes]['+field[0]+']';label.appendChild(input);box.appendChild(label);
    });
  }
  document.addEventListener('click',function(e){
    var btn=e.target.closest('.material-remove-row');if(!btn)return;
    var row=btn.closest('.dynamic-material-row');if(!row)return;
    var body=row.parentElement;
    if(body.children.length===1){
      row.querySelectorAll('input').forEach(function(input){input.value='';});
      row.querySelectorAll('select').forEach(function(sel){sel.selectedIndex=0;});
      var note=row.querySelector('.legacy-material-spec');if(note)note.remove();
      createStandardFields(row);mm05Apply(row.querySelector('.mm05-select'));
    }else{row.remove();}
  });
  document.addEventListener('change',function(e){if(e.target.matches('[data-material-category]'))createStandardFields(e.target.closest('.dynamic-material-row'));});
  document.querySelectorAll('[data-tech-form]').forEach(function(form){
    form.querySelectorAll('[data-add]').forEach(function(button){
      button.addEventListener('click',function(){
        var type=button.getAttribute('data-add');
        if(type==='materials'){
          var body=form.querySelector('[data-rows="materials"]');if(!body||body.children.length>=30)return;
          var source=body.querySelector('.dynamic-material-row');if(!source)return;
          var next=Math.max(-1,...Array.from(body.querySelectorAll('[data-material-category]')).map(function(el){var m=el.name.match(/materials\[(\d+)\]/);return m?+m[1]:-1;}))+1;
          var row=source.cloneNode(true);
          row.querySelectorAll('.sjr-cp-editor').forEach(function(el){el.remove();});
          row.querySelectorAll('.sjr-existing-attachments .sjr-attachment-file').forEach(function(el){el.remove();});
          row.querySelectorAll('.sjr-pending-attachments').forEach(function(el){el.replaceChildren();});
          row.querySelectorAll('.sjr-attachment-widget').forEach(function(el){delete el.dataset.sjrAttachmentReady;});
          row.querySelectorAll('textarea[data-spec-visible]').forEach(function(el){delete el.dataset.sjrCpReady;});
          row.classList.remove('sjr-is-new-request');
          row.querySelectorAll('input,select,textarea').forEach(function(el){el.name=el.name.replace(/materials\[\d+\]/,'materials['+next+']');if(el.tagName==='SELECT')el.selectedIndex=0;else el.value='';if(el.classList.contains('sjr-material-request-reason'))el.required=false;});
          var requestPanel=row.querySelector('.sjr-new-material-panel');if(requestPanel)requestPanel.style.display='none';
          var categoryWrap=row.querySelector('.sjr-material-category-wrap');if(categoryWrap)categoryWrap.style.display='none';
          var sourceType=row.querySelector('.sjr-material-source-type');if(sourceType)sourceType.value='';
          var requestToggle=row.querySelector('.sjr-new-material-toggle');if(requestToggle){requestToggle.setAttribute('aria-pressed','false');requestToggle.textContent='+ Ajukan Material Baru';}
          var v6box=row.querySelector('[data-v6-size-rows]');if(v6box){Array.from(v6box.children).slice(1).forEach(function(el){el.remove();});v6box.querySelectorAll('input').forEach(function(el){el.value='';});}
          var legacy=row.querySelector('.legacy-material-spec');if(legacy)legacy.remove();
          createStandardFields(row);body.appendChild(row);mm05Apply(row.querySelector('.mm05-select'));return;
        }

        var body=form.querySelector('[data-rows="'+type+'"]');
        if(!body || body.children.length>=30)return;
        var i=Math.max(-1,...Array.from(body.querySelectorAll('input')).map(function(el){var m=el.name.match(/\[(\d+)\]/);return m?Number(m[1]):-1;}))+1;
        var row=document.createElement('tr');
        (type==='processes'?['name','spec','quantity','unit_price']:['name','spec','quantity']).forEach(function(key){
          var td=document.createElement('td'),input=document.createElement('input');
          input.className='tech-field';input.name=type+'['+i+']['+key+']';
          if(key==='quantity'){input.type='number';input.step='any';input.min='0.001';}if(key==='unit_price'){input.type='number';input.step='1';input.min='0';input.max='999999999999';input.className+=' tech-price';}
          td.appendChild(input);row.appendChild(td);
        });
        // Hidden compatibility field, not displayed to the user.
        var legacyUnit=document.createElement('input');legacyUnit.type='hidden';legacyUnit.name=type+'['+i+'][unit]';legacyUnit.value='';row.querySelector('td').appendChild(legacyUnit);
        var last=document.createElement('td'),del=document.createElement('button');
        del.type='button';del.className='tech-remove';del.textContent='✕';del.title='Hapus baris';
        del.addEventListener('click',function(){row.remove()});last.appendChild(del);row.appendChild(last);body.appendChild(row);
      });
    });
  });
})();
</script>

<style id="sjr-codepen-editor-style">
/* SJR_CODEPEN_EDITOR_V1 */
.sjr-spec-field textarea[data-spec-visible]{display:none!important}
.sjr-cp-editor{margin-top:8px;border:1px solid #cbd9ec;border-radius:8px;background:#fff;overflow:hidden}
.sjr-cp-toolbar{padding:9px;background:#f1f4f9;border-bottom:1px solid #dce5f1;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.sjr-cp-toolbar button,.sjr-cp-toolbar select{min-height:32px;border:1px solid #cbd9ec;border-radius:5px;background:#fff;color:#172742;padding:4px 8px;font:inherit;cursor:pointer}
.sjr-cp-toolbar button{min-width:33px;font-weight:700}
.sjr-cp-toolbar input[type=color]{height:30px;width:33px;padding:2px;border:1px solid #cbd9ec;border-radius:5px;background:white;cursor:pointer}
.sjr-cp-body{min-height:135px;max-height:420px;overflow:auto;padding:12px 14px;outline:none;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
.sjr-cp-body:focus{box-shadow:inset 0 0 0 2px #bcd4fc}
.sjr-cp-body ul,.sjr-cp-body ol{padding-left:22px}
.sjr-cp-body a{color:#2563c5;text-decoration:underline}
</style>

<script id="sjr-codepen-editor-runtime">
(function(){
'use strict';
const MAX=5000;
const allowedTags=new Set(['B','STRONG','I','EM','U','S','STRIKE','P','DIV','BR','UL','OL','LI','A','SPAN','FONT','H1','H2','H3','H4','H5','H6','BLOCKQUOTE']);
function clean(html){
  const t=document.createElement('template');t.innerHTML=String(html||'');
  function walk(parent){
    [...parent.childNodes].forEach(node=>{
      if(node.nodeType===8){node.remove();return;}
      if(node.nodeType===3)return;
      if(node.nodeType!==1){node.remove();return;}
      const tag=node.tagName;
      if(['SCRIPT','STYLE','IFRAME','OBJECT','SVG','MATH','FORM','INPUT','BUTTON','SELECT','TEXTAREA'].includes(tag)){node.remove();return;}
      walk(node);
      if(!allowedTags.has(tag)){node.replaceWith(...[...node.childNodes]);return;}
      const attrs=[...node.attributes];
      attrs.forEach(a=>node.removeAttribute(a.name));
      if(tag==='A'){
        const href=attrs.find(a=>a.name.toLowerCase()==='href')?.value||'';
        if(/^https?:\/\//i.test(href)) {node.setAttribute('href',href);node.setAttribute('rel','noopener noreferrer');}
      }
      const style=attrs.find(a=>a.name.toLowerCase()==='style')?.value||'';
      const safe=[];
      style.split(';').forEach(pair=>{
        const [rawKey,...rest]=pair.split(':');
        const k=(rawKey||'').trim().toLowerCase(),v=rest.join(':').trim().toLowerCase();
        if(k==='text-align'&&/^(left|right|center|justify)$/.test(v))safe.push(k+':'+v);
        if(k==='color'||k==='background-color'){
          if(/^#[0-9a-f]{3,8}$/.test(v)||/^rgb(a)?\([\d\s.,%]+\)$/.test(v))safe.push(k+':'+v);
        }
        if(k==='font-size'&&/^\d{1,2}px$/.test(v)&&parseInt(v)<=72)safe.push(k+':'+v);
        if(k==='font-family'&&/^(arial|georgia|times new roman|courier new|sans-serif|serif|monospace)$/.test(v.replace(/["']/g,'')))safe.push(k+':'+v);
      });
      if(safe.length)node.setAttribute('style',safe.join(';'));
      if(tag==='FONT'){
        const c=attrs.find(a=>a.name.toLowerCase()==='color')?.value;
        if(c&&/^#[0-9a-f]{3,8}$/i.test(c))node.setAttribute('color',c);
        const z=attrs.find(a=>a.name.toLowerCase()==='size')?.value;
        if(z&&/^[1-7]$/.test(z))node.setAttribute('size',z);
        const f=attrs.find(a=>a.name.toLowerCase()==='face')?.value;
        if(f&&/^(Arial|Georgia|Times New Roman|Courier New)$/i.test(f))node.setAttribute('face',f);
      }
    });
  }
  walk(t.content);return t.innerHTML;
}
function init(ta){
 if(ta.dataset.sjrCpReady)return;
 ta.dataset.sjrCpReady='1';
 const source=ta.closest('.dynamic-material-row')?.querySelector('.sjr-material-spec-hidden');
 if(!source)return;
 const wrap=document.createElement('div');wrap.className='sjr-cp-editor';
 const bar=document.createElement('div');bar.className='sjr-cp-toolbar';
 const body=document.createElement('div');body.className='sjr-cp-body';body.contentEditable='true';body.spellcheck=true;
 body.setAttribute('role','textbox');body.setAttribute('aria-multiline','true');body.setAttribute('aria-label','Spesifikasi lengkap');
 wrap.append(bar,body);ta.after(wrap);
 const initial=source.value||ta.value||'';
 if(/<\/?(?:p|div|span|strong|b|i|em|u|ul|ol|li|font|h[1-6])[\s>]/i.test(initial))body.innerHTML=clean(initial);
 else body.textContent=initial;
 let savedRange=null;
 function capture(){
  const sel=window.getSelection();
  if(sel&&sel.rangeCount&&body.contains(sel.anchorNode)&&body.contains(sel.focusNode))savedRange=sel.getRangeAt(0).cloneRange();
 }
 function restore(){
  body.focus();
  if(savedRange){
   try{const sel=window.getSelection();sel.removeAllRanges();sel.addRange(savedRange);}catch(_){}
  }
 }
 function sync(){
  const html=clean(body.innerHTML);
  source.value=html;
  ta.value=html;
  source.dispatchEvent(new Event('input',{bubbles:false}));
 }
 function cmd(name,value){
  restore();
  document.execCommand(name,false,value===undefined?null:value);
  capture();sync();
 }
 function button(text,title,name,action){
  const b=document.createElement('button');b.type='button';b.title=title;b.setAttribute('aria-label',title);
  // SJR_BOXICONS_FINAL_COMPACT: icons only; keep original handlers.
  const iconMap={'undo':'bx-undo','redo':'bx-redo','bold':'bx-bold','italic':'bx-italic','underline':'bx-underline','strikeThrough':'bx-strikethrough','justifyLeft':'bx-align-left','justifyCenter':'bx-align-middle','justifyRight':'bx-align-right','justifyFull':'bx-align-justify','insertUnorderedList':'bx-list-ul','insertOrderedList':'bx-list-ol','createLink':'bx-link','unlink':'bx-unlink','removeFormat':'bx-eraser'};
  const key=name||(title==='Tambah tautan'?'createLink':null);
  if(iconMap[key]){const i=document.createElement('i');i.className='bx '+iconMap[key];i.setAttribute('aria-hidden','true');b.append(i);}
  else b.textContent=text;
  b.addEventListener('mousedown',e=>e.preventDefault());
  b.addEventListener('click',()=>action?action():cmd(name));
  bar.append(b);return b;
 }
 function select(title,options,command){
  const sel=document.createElement('select');sel.title=title;sel.setAttribute('aria-label',title);
  options.forEach(([value,label])=>{const o=document.createElement('option');o.value=value;o.textContent=label;sel.append(o);});
  sel.addEventListener('mousedown',capture);
  sel.addEventListener('change',()=>{cmd(command,sel.value);sel.selectedIndex=0;});
  bar.append(sel);
 }
 select('Format',[['','Format'],['p','Paragraph'],['h1','Heading 1'],['h2','Heading 2'],['h3','Heading 3']], 'formatBlock');
 select('Font',[['','Font'],['Arial','Arial'],['Georgia','Georgia'],['Times New Roman','Times New Roman'],['Courier New','Courier New']], 'fontName');
 select('Ukuran',[['','Size'],['2','Small'],['3','Normal'],['4','Medium'],['5','Large'],['6','XL'],['7','XXL']], 'fontSize');
 function color(label,command){
  const inp=document.createElement('input');inp.type='color';inp.title=label;inp.setAttribute('aria-label',label);
  inp.addEventListener('mousedown',capture);
  inp.addEventListener('input',()=>cmd(command,inp.value));bar.append(inp);
 }
 color('Warna teks','foreColor');color('Warna latar','hiliteColor');
 [['↶','Undo','undo'],['↷','Redo','redo'],['B','Bold','bold'],['I','Italic','italic'],['U','Underline','underline'],['S','Strikethrough','strikeThrough'],['≡','Rata kiri','justifyLeft'],['≣','Rata tengah','justifyCenter'],['☷','Rata kanan','justifyRight'],['☰','Justify','justifyFull'],['•','Bullet','insertUnorderedList'],['1.','Numbering','insertOrderedList']].forEach(x=>button(...x));
 button('🔗','Tambah tautan',null,()=>{
   const url=prompt('Masukkan URL (https://...)');
   if(url&&/^https?:\/\//i.test(url))cmd('createLink',url);
 });
 button('×','Hapus format','removeFormat');

// SJR_TOOLBAR_REFERENCE_V2: susunan dua baris mengikuti CodePen.
button('🔗̸','Hapus tautan','unlink');
const controls=[...bar.children];
const first=document.createElement('div');first.className='sjr-cp-toolbar-head';
const second=document.createElement('div');second.className='sjr-cp-toolbar-actions';
const selects=controls.filter(el=>el.tagName==='SELECT');
const colors=controls.filter(el=>el.matches('input[type="color"]'));
selects.forEach(el=>first.appendChild(el));
colors.forEach((el,i)=>{
 const label=document.createElement('label');label.className='sjr-cp-color';
 const name=document.createElement('span');name.textContent=i===0?'Color':'Background';
 label.append(name,el);first.appendChild(label);
});
const byTitle=new Map(controls.filter(el=>el.tagName==='BUTTON').map(el=>[el.title,el]));
const order=['Undo','Redo','Bold','Underline','Italic','Strikethrough','Rata kiri','Rata tengah','Rata kanan','Justify','Numbering','Bullet','Tambah tautan','Hapus tautan','Hapus format'];
order.forEach(title=>{const b=byTitle.get(title);if(b)second.appendChild(b);});
bar.replaceChildren(first,second);

 body.addEventListener('keyup',capture);
 body.addEventListener('mouseup',capture);
 body.addEventListener('input',()=>{
   if(body.textContent.length>MAX){body.textContent=body.textContent.slice(0,MAX);}
   sync();
 });
 const form=ta.closest('form');
 if(form&&!form.dataset.sjrCpSubmit){
  form.dataset.sjrCpSubmit='1';
  form.addEventListener('submit',()=>form.querySelectorAll('.sjr-cp-body').forEach(el=>el.dispatchEvent(new Event('input'))),true);
 }
 sync();
}
function scan(root){
 if(root.matches?.('textarea[data-spec-visible]'))init(root);
 root.querySelectorAll?.('textarea[data-spec-visible]').forEach(init);
}
function boot(){
 scan(document);
 const obs=new MutationObserver(records=>records.forEach(r=>r.addedNodes.forEach(n=>{if(n.nodeType===1)scan(n);})));
 obs.observe(document.body,{childList:true,subtree:true});
 document.addEventListener('click',e=>{
  if(e.target.closest('.sjr-new-material-toggle,[data-add="materials"]'))setTimeout(()=>scan(document),0);
 });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);
else boot();
})();
</script>


<style id="sjr-toolbar-reference-style">
/* SJR_BOXICONS_FINAL_COMPACT */
.sjr-cp-toolbar{display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:6px!important;padding:8px!important;background:#eee!important;min-width:0}
.sjr-cp-toolbar-head{display:flex;align-items:center;gap:6px;flex-wrap:nowrap;min-width:0}
.sjr-cp-toolbar-head select{height:31px;min-height:31px;min-width:0!important;flex:0 1 105px;padding:3px 6px;background:#fff;font-size:11px}
.sjr-cp-toolbar-head select:nth-child(2){flex-basis:125px}
.sjr-cp-toolbar-head select:nth-child(3){flex-basis:75px}
.sjr-cp-color{display:inline-flex;align-items:center;gap:4px;height:31px;padding:2px 5px;border:1px solid #cbd9ec;border-radius:5px;background:#fff;font-size:10px;color:#172742;white-space:nowrap;flex:0 0 auto}
.sjr-cp-color input[type=color]{width:21px;height:23px;min-height:0;border:0;padding:1px}
.sjr-cp-toolbar-actions{display:grid;grid-template-columns:repeat(15,minmax(0,1fr));gap:4px;min-width:0}
.sjr-cp-toolbar-actions button{box-sizing:border-box;width:100%;min-width:0!important;height:30px;min-height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #cbd9ec;border-radius:5px;color:#172742;font-size:16px}
.sjr-cp-toolbar-actions button .bx{font-size:17px;line-height:1}
.sjr-cp-toolbar-actions button:hover{background:#e8f0ff;border-color:#a8c7f6}
.sjr-cp-toolbar-actions button:focus-visible{outline:2px solid #3478ec;outline-offset:1px}
@media(max-width:540px){.sjr-cp-toolbar-head{flex-wrap:wrap}.sjr-cp-toolbar-actions{overflow-x:auto;grid-template-columns:repeat(15,30px);padding-bottom:3px}}
</style>

<style id="sjr-attachment-v1-style">
.sjr-attachment-widget{padding:14px;border:1px solid #dbe5f4;border-radius:11px;background:#fff;margin:12px 0}
.sjr-attachment-widget>strong{display:block;margin-bottom:10px;color:#203b61}
.sjr-attachment-drop{display:flex;flex-direction:column;align-items:center;gap:7px;text-align:center;border:2px dashed #a9c3e9;border-radius:10px;background:#f5f9ff;padding:20px;cursor:pointer;position:relative;color:#254f8c}
.sjr-attachment-drop:hover,.sjr-attachment-drop:focus,.sjr-attachment-drop.sjr-drag-over{background:#eaf3ff;border-color:#3279ed;outline:none}
.sjr-upload-icon{font-size:26px}.sjr-attachment-drop small,.sjr-attachment-hint{color:#667c99;font-size:11px}
.sjr-attachment-input{position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer}
.sjr-pending-attachments,.sjr-existing-attachments{display:grid;gap:7px;margin-top:10px}
.sjr-attachment-file{display:flex;align-items:center;gap:10px;border:1px solid #e0e8f5;border-radius:8px;padding:9px 11px;background:#fff;min-width:0}
.sjr-file-symbol{font-size:19px}.sjr-file-name{flex:1;min-width:0;overflow-wrap:anywhere;font-size:12px;font-weight:650}.sjr-file-name small{display:block;color:#7286a2;font-weight:400;margin-top:3px}
.sjr-file-action{display:grid;place-items:center;width:30px;height:30px;flex-shrink:0;border:1px solid #d3dff0;border-radius:7px;background:#fff;color:#2767c8;text-decoration:none;font-size:19px;cursor:pointer}
.sjr-remove-saved,.sjr-remove-pending{color:#b42335}.sjr-attachment-hint{display:block;margin-top:9px}
</style>
<script id="sjr-attachment-v1-runtime">
(function(){'use strict';
function init(widget){if(widget.dataset.sjrAttachmentReady)return;widget.dataset.sjrAttachmentReady='1';
 var input=widget.querySelector('.sjr-attachment-input'),drop=widget.querySelector('.sjr-attachment-drop'),pending=widget.querySelector('.sjr-pending-attachments'),hidden=widget.querySelector('.sjr-existing-attachment-data');
 var selected=[];
 function sync(){var dt=new DataTransfer();selected.forEach(function(f){dt.items.add(f);});input.files=dt.files;pending.replaceChildren();selected.forEach(function(f,i){var row=document.createElement('div');row.className='sjr-attachment-file';var label=document.createElement('span');label.className='sjr-file-name';label.textContent=f.name;var meta=document.createElement('small');meta.textContent=(f.size/1048576).toFixed(2)+' MB · Belum disimpan';label.appendChild(meta);var del=document.createElement('button');del.type='button';del.className='sjr-file-action sjr-remove-pending';del.textContent='×';del.title='Batalkan file';del.onclick=function(){selected.splice(i,1);sync();};row.append(label,del);pending.appendChild(row);});}
 function add(files){Array.from(files).forEach(function(f){if(selected.length>=5){alert('Maksimal 5 file baru per penyimpanan.');return;}if(f.size>200*1048576){alert('File melebihi 200 MB: '+f.name);return;}if(!/\.(pdf|jpe?g|png|webp|docx?|xlsx?)$/i.test(f.name)){alert('Tipe file tidak didukung: '+f.name);return;}selected.push(f);});sync();}
 input.addEventListener('change',function(){var files=Array.from(input.files);add(files);});
 ['dragenter','dragover'].forEach(function(n){drop.addEventListener(n,function(e){e.preventDefault();drop.classList.add('sjr-drag-over');});});
 ['dragleave','drop'].forEach(function(n){drop.addEventListener(n,function(e){e.preventDefault();drop.classList.remove('sjr-drag-over');});});
 drop.addEventListener('drop',function(e){add(e.dataTransfer.files);});
 drop.addEventListener('keydown',function(e){if(e.target===drop&&(e.key==='Enter'||e.key===' ')){e.preventDefault();input.click();}});
 widget.addEventListener('click',function(e){var btn=e.target.closest('.sjr-remove-saved');if(!btn)return;var row=btn.closest('.sjr-attachment-file');if(!row||!confirm('Hapus lampiran ini setelah Simpan Draft?'))return;var data=[];try{data=JSON.parse(hidden.value||'[]');}catch(e){}var path=row.dataset.attachmentPath;hidden.value=JSON.stringify(data.filter(function(a){return a.path!==path;}));row.remove();});
}
function scan(){document.querySelectorAll('.sjr-attachment-widget').forEach(init);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',scan);else scan();
new MutationObserver(scan).observe(document.documentElement,{childList:true,subtree:true});
})();
</script>
<!-- SJR_DIAGRAM_ALL_ITEMS_V2: sinkronisasi preview per material, tanpa mengubah rumus cutting -->
<script id="sjr-diagram-all-items-v2">
(function(){
  'use strict';
  function sync(root){
    (root || document).querySelectorAll('.dynamic-material-row').forEach(function(row){
      var select=row.querySelector('.mm05-select');
      var calc=row.querySelector('.mm05-calculation');
      var source=row.querySelector('.sjr-material-source-type');
      if(!select || !calc)return;
      var valid=select.value!=='' && (!source || source.value!=='new_material_request');
      if(!valid){
        row.classList.remove('sjr-mm05-preview-ready');
        calc.style.removeProperty('display');
        return;
      }
      // Gunakan handler kalkulator asli yang sudah ada; tidak menghitung ulang dengan rumus baru.
      select.dispatchEvent(new Event('change',{bubbles:true}));
      row.classList.remove('mm05-awaiting-selection');
      row.classList.add('sjr-mm05-preview-ready');
      calc.style.setProperty('display','block','important');
    });
  }
  function schedule(root){requestAnimationFrame(function(){sync(root);});}
  function init(){
    sync(document);
    document.querySelectorAll('details.item-card').forEach(function(d){
      d.addEventListener('toggle',function(){if(d.open)schedule(d);});
    });
    document.addEventListener('change',function(e){
      if(e.target.matches('.mm05-select')){
        // Handler utama memproses perubahan lebih dulu; hindari memicu change rekursif.
        var row=e.target.closest('.dynamic-material-row');if(!row)return;
        requestAnimationFrame(function(){
          var calc=row.querySelector('.mm05-calculation');
          var source=row.querySelector('.sjr-material-source-type');
          var valid=e.target.value!==''&&(!source||source.value!=='new_material_request');
          row.classList.toggle('sjr-mm05-preview-ready',valid);
          if(valid){row.classList.remove('mm05-awaiting-selection');if(calc)calc.style.setProperty('display','block','important');}
          else if(calc)calc.style.removeProperty('display');
        });
      }
    });
    document.addEventListener('click',function(e){
      if(e.target.closest('[data-add="materials"]'))requestAnimationFrame(function(){sync(document);});
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
</script>
<style id="sjr-diagram-all-items-v2-style">
.dynamic-material-row.sjr-mm05-preview-ready .mm05-calculation{display:block!important}
</style>
<script id="sjr-save-draft-multisize-final">
(function(){
  function sync(form){
    if(!form || !form.matches('form[data-tech-form]')) return;
    form.querySelectorAll('.dynamic-material-row').forEach(function(row){
      var source=row.querySelector('.sjr-material-source-type');
      var isNew=row.classList.contains('sjr-is-new-request') || (source && source.value==='new_material_request');
      var variant=row.querySelector('.mm05-select');
      var cat=row.querySelector('[data-material-category]');
      var cuts=row.querySelectorAll('.v6-size-row');
      var legacy=row.querySelector('.dynamic-quantity-fields input[name$="[quantity]"]');
      var multi=!isNew && variant && variant.value && cat && (cat.value==='profile'||cat.value==='sheet') && cuts.length;
      if(multi && legacy){
        var sum=0, valid=true;
        cuts.forEach(function(c){var el=c.querySelector('.v6-qty');var n=Number(el && el.value);if(!el || !Number.isInteger(n) || n<1)valid=false;else sum+=n;});
        if(valid && sum>0)legacy.value=String(sum);
        legacy.required=false;
      }
      // Rich-text proxy textarea tanpa name tidak ikut payload form.
      // Validasi konten sebenarnya tetap di backend melalui materials.*.spec.
      row.querySelectorAll('textarea:not([name])').forEach(function(el){
        if(el.required && !el.getClientRects().length)el.required=false;
      });
    });
  }
  document.addEventListener('click',function(e){
    var b=e.target.closest('button,input[type="submit"]');
    if(b && b.form && b.type==='submit')sync(b.form);
  },true);
  document.addEventListener('submit',function(e){sync(e.target);},true);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('form[data-tech-form]').forEach(sync);});
  else document.querySelectorAll('form[data-tech-form]').forEach(sync);
})();
</script>
<script id="sjr-save-draft-hidden-field-validation-v2">
(function(){
  // Periksa hanya input lama yang tersembunyi pada material MM-05.
  // Input pada Pengajuan Material Baru tetap divalidasi seperti semula.
  function sync(form){
    if(!form || !form.matches('form[data-tech-form]')) return;
    form.querySelectorAll('.dynamic-material-row').forEach(function(row){
      var source=row.querySelector('.sjr-material-source-type');
      var isNew=row.classList.contains('sjr-is-new-request') || (source && source.value==='new_material_request');
      if(isNew) return;
      var legacy=row.querySelector('.dynamic-quantity-fields input[name$="[quantity]"]');
      if(!legacy) return;
      var label=legacy.closest('label');
      var hidden=label && (getComputedStyle(label).display==='none' || !label.getClientRects().length);
      // Jangan menonaktifkan validasi jika field memang terlihat dan digunakan.
      if(hidden) legacy.required=false;
    });
  }
  function all(){document.querySelectorAll('form[data-tech-form]').forEach(sync);}
  document.addEventListener('click',function(e){
    var button=e.target.closest('button,input[type="submit"]');
    if(button && button.form && button.type==='submit') sync(button.form);
  },true);
  document.addEventListener('submit',function(e){sync(e.target);},true);
  document.addEventListener('invalid',function(e){
    var el=e.target, form=el.form;
    if(!form || !form.matches('form[data-tech-form]')) return;
    // Hanya input quantity legacy MM-05 yang tersembunyi yang diperbaiki.
    var row=el.closest('.dynamic-material-row');
    if(!row || !el.matches('.dynamic-quantity-fields input[name$="[quantity]"]')) return;
    var source=row.querySelector('.sjr-material-source-type');
    if(row.classList.contains('sjr-is-new-request') || (source && source.value==='new_material_request')) return;
    var label=el.closest('label');
    if(label && !label.getClientRects().length){el.required=false;}
  },true);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',all);else all();
})();
</script>
<!-- SJR_MM05_LEGACY_QUANTITY_GUARD_V3 -->
<script id="sjr-mm05-legacy-quantity-guard-v3">
(function(){
  'use strict';
  var selector='.dynamic-quantity-fields input[name$="[quantity]"]';
  function syncInput(input){
    if(!input || !input.matches(selector))return;
    var row=input.closest('.dynamic-material-row');
    if(!row)return;
    var source=row.querySelector('.sjr-material-source-type');
    var isNew=row.classList.contains('sjr-is-new-request') || !!(source && source.value==='new_material_request');
    // Legacy quantity is only mandatory for an actual new-material request.
    // MM-05 Multi-Size quantities are validated from cut_sizes by Laravel.
    if(input.required!==isNew)input.required=isNew;
  }
  function sync(root){
    (root || document).querySelectorAll(selector).forEach(syncInput);
  }
  // Other scripts may reapply required after initial page load. Observe only
  // legacy quantity fields, never alter the cutting-size inputs or their values.
  var observed=new WeakSet();
  function watch(){
    document.querySelectorAll(selector).forEach(function(input){
      if(observed.has(input))return;
      observed.add(input);
      new MutationObserver(function(){syncInput(input);}).observe(input,{attributes:true,attributeFilter:['required']});
      syncInput(input);
    });
  }
  function start(){
    watch();
    document.querySelectorAll('form[data-tech-form]').forEach(function(form){
      form.addEventListener('click',function(e){
        if(e.target.closest('button[type="submit"],input[type="submit"]'))sync(form);
      },true);
    });
    new MutationObserver(watch).observe(document.body,{childList:true,subtree:true});
  }
  document.addEventListener('click',function(e){
    if(e.target.closest('.sjr-new-material-toggle,.mm05-open-picker,.mm05-picker-row-selectable')){
      queueMicrotask(function(){sync();});
    }
  },true);
  document.addEventListener('change',function(e){
    if(e.target.matches('.mm05-select,.sjr-material-source-type'))queueMicrotask(function(){sync();});
  },true);
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});
  else start();
})();
</script>
<!-- SJR_SAVE_DRAFT_UNBLOCK_V4 -->
<script id="sjr-save-draft-unblock-v4">
(function(){
  'use strict';
  function isNew(row){
    var source=row.querySelector('.sjr-material-source-type');
    return row.classList.contains('sjr-is-new-request') || !!(source && source.value==='new_material_request');
  }
  function prepare(form){
    form.querySelectorAll('.dynamic-material-row').forEach(function(row){
      if(isNew(row))return;
      // Quantity lama boleh tetap dikirim ke backend, tetapi tidak wajib
      // ketika MM-05 memakai daftar potongan Multi-Size.
      row.querySelectorAll('input[name$="[quantity]"]').forEach(function(el){
        if(el.closest('.v6-size-row'))return;
        if(el.closest('.dynamic-quantity-fields'))el.required=false;
      });
    });
  }
  function validateVisible(form){
    prepare(form);
    var fields=Array.from(form.elements).filter(function(el){
      if(!el.willValidate || el.disabled || !el.required)return false;
      if(el.type==='hidden')return false;
      if(el.closest('.sjr-new-material-panel') && getComputedStyle(el.closest('.sjr-new-material-panel')).display==='none')return false;
      // Hidden legacy controls are validated by Laravel, not browser focus.
      if(!el.getClientRects().length)return false;
      return true;
    });
    for(var i=0;i<fields.length;i++){
      if(!fields[i].checkValidity()){
        fields[i].reportValidity();
        fields[i].focus();
        return false;
      }
    }
    return true;
  }
  function init(form){
    if(form.dataset.sjrSaveUnblockV4)return;
    form.dataset.sjrSaveUnblockV4='1';
    // Prevent Chrome from blocking submission on a hidden legacy field.
    // Laravel continues to validate the actual submitted payload.
    form.noValidate=true;
    prepare(form);
    form.addEventListener('click',function(e){
      var button=e.target.closest('button[type="submit"],input[type="submit"]');
      if(!button || button.form!==form)return;
      prepare(form);
      if(!validateVisible(form)){
        e.preventDefault();e.stopImmediatePropagation();
      }
    },true);
    form.addEventListener('submit',function(e){
      prepare(form);
      if(!validateVisible(form)){
        e.preventDefault();e.stopImmediatePropagation();
      }
    },true);
  }
  function boot(){
    document.querySelectorAll('form[data-tech-form]').forEach(init);
    new MutationObserver(function(){document.querySelectorAll('form[data-tech-form]').forEach(init);})
      .observe(document.body,{childList:true,subtree:true});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot,{once:true});else boot();
})();
</script>
<script id="sjr-ui-v6-stage1-runtime">
(function(){'use strict';
function init(){
 var tabs=document.getElementById('sjr-v6-tabs');if(!tabs||tabs.dataset.ready)return;
 var cards=Array.from(document.querySelectorAll('details.item-card'));
 var buttons=Array.from(tabs.querySelectorAll('[data-sjr-v6-item]'));
 if(!cards.length||cards.length!==buttons.length)return;
 tabs.dataset.ready='1';
 function activate(index,focus){
  cards.forEach(function(card,i){card.classList.toggle('sjr-v6-active',i===index);card.classList.toggle('sjr-v6-inactive',i!==index);if(i===index)card.open=true;});
  buttons.forEach(function(btn,i){btn.setAttribute('aria-selected',String(i===index));btn.tabIndex=i===index?0:-1;});
  if(focus){buttons[index].focus({preventScroll:true});tabs.scrollIntoView({block:'start',behavior:'smooth'});}
 }
 var active=cards.findIndex(function(card){return card.open});if(active<0)active=0;
 cards.forEach(function(card,i){
  var body=card.querySelector('.item-body');if(!body)return;
  var form=body.querySelector('form[data-tech-form]');
  if(!form)return;
  var quick=document.createElement('nav');quick.className='sjr-v6-quicknav';quick.setAttribute('aria-label','Navigasi bagian teknis');
  [['Material','.dynamic-material-section'],['Proses / Jasa','[data-rows="processes"]'],['Lead Time','[name="leadtime_days"]'],['Catatan','[name="instructions"]']].forEach(function(pair){
   var target=form.querySelector(pair[1]);if(!target)return;
   var button=document.createElement('button');button.type='button';button.textContent=pair[0];button.addEventListener('click',function(){target.scrollIntoView({behavior:'smooth',block:'start'});});quick.appendChild(button);
  });
  form.parentNode.insertBefore(quick,form);
  var foot=form.querySelector('.tech-foot');if(foot){
   var dirty=document.createElement('span');dirty.className='sjr-v6-dirty';dirty.hidden=true;dirty.textContent='Perubahan belum disimpan';foot.insertBefore(dirty,foot.firstChild);
   form.addEventListener('input',function(){dirty.hidden=false;});form.addEventListener('change',function(){dirty.hidden=false;});
  }
 });
 buttons.forEach(function(btn,i){btn.addEventListener('click',function(){activate(i,true)});btn.addEventListener('keydown',function(e){if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();activate((i+(e.key==='ArrowRight'?1:-1)+buttons.length)%buttons.length,true);}});});
 document.documentElement.classList.add('sjr-v6-ready');activate(active,false);
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
<script id="sjr-ui-v6-stage2-runtime">
(function(){'use strict';
function init(){
 if(document.documentElement.dataset.sjrV6Stage2==='1')return;
 var forms=Array.from(document.querySelectorAll('form[data-tech-form]'));
 if(!forms.length)return;
 document.documentElement.dataset.sjrV6Stage2='1';
 forms.forEach(function(form,fi){
  var material=form.querySelector('.dynamic-material-section');
  var processes=form.querySelector('[data-rows="processes"]');
  var lead=form.querySelector('[name="leadtime_days"]');
  var notes=form.querySelector('[name="instructions"]');
  if(!material||!processes||!lead||!notes)return;
  function topChild(el){while(el&&el.parentElement!==form)el=el.parentElement;return el;}
  var panels=[material,topChild(processes),topChild(lead),topChild(notes)];
  if(panels.some(function(p){return !p})||new Set(panels).size!==4)return;
  var subnav=document.createElement('div');subnav.className='sjr-v6-subtabs';subnav.setAttribute('role','tablist');subnav.setAttribute('aria-label','Persiapan teknis');
  var labels=[['▤','Material'],['⚒','Proses / Jasa'],['◷','Lead Time'],['✎','Catatan']];
  var subbuttons=labels.map(function(pair,i){
   var b=document.createElement('button');b.type='button';b.className='sjr-v6-subtab';b.setAttribute('role','tab');
   b.innerHTML='<span class="sjr-v6-subtab-icon" aria-hidden="true">'+pair[0]+'</span><span>'+pair[1]+'</span>';
   b.addEventListener('click',function(){activateSection(i)});subnav.appendChild(b);return b;
  });
  var techHeader=form.closest('.inline-tech')?.querySelector('.inline-tech-head');
   if(techHeader){
     techHeader.appendChild(subnav);
   }else{
     form.insertBefore(subnav,panels[0]);
   }
  function activateSection(i){
   panels.forEach(function(p,j){p.classList.toggle('sjr-v6-section-hidden',i!==j)});
   subbuttons.forEach(function(b,j){b.setAttribute('aria-selected',String(i===j));b.tabIndex=i===j?0:-1});
   if(i===0)refreshMaterial();
   form.dispatchEvent(new CustomEvent('sjr:v6:tab-activated',{bubbles:true,detail:{section:i}}));
   window.dispatchEvent(new Event('resize'));
  }
  subbuttons.forEach(function(b,i){b.addEventListener('keydown',function(e){if(e.key==='ArrowRight'||e.key==='ArrowLeft'){e.preventDefault();var n=(i+(e.key==='ArrowRight'?1:-1)+subbuttons.length)%subbuttons.length;activateSection(n);subbuttons[n].focus();}})});
  var tbody=material.querySelector('tbody[data-rows="materials"]');
  var add=material.querySelector('[data-add="materials"]');
  if(!tbody||!add){activateSection(0);return;}
  var matnav=document.createElement('div');matnav.className='sjr-v6-material-tabs';matnav.setAttribute('role','tablist');matnav.setAttribute('aria-label','Material pada item');
  var table=material.querySelector('.table-scroll');material.insertBefore(matnav,table);
  var activeRow=null,observer=null,queued=false;
  function rows(){return Array.from(tbody.querySelectorAll(':scope > tr.dynamic-material-row'))}
  function materialName(row,i){
   var field=row.querySelector('.mm05-selected-label');
   var input=row.querySelector('input[name$="[name]"]');
   var name=(field&&field.value||input&&input.value||'').trim();
   if(!name||name==='Belum dipilih')name='Material '+(i+1);
   return name.length>27?name.slice(0,26)+'…':name;
  }
  function activateMaterial(row){
   var all=rows();if(!all.includes(row))row=all[0]||null;activeRow=row;
   all.forEach(function(r){r.classList.toggle('sjr-v6-material-hidden',r!==row)});
   Array.from(matnav.querySelectorAll('[data-sjr-material-tab]')).forEach(function(b){
    var isActive=all[Number(b.dataset.sjrMaterialTab)]===row;
    b.setAttribute('aria-selected',String(isActive));b.tabIndex=isActive?0:-1;
   });
   window.dispatchEvent(new Event('resize'));
   if(row)row.dispatchEvent(new CustomEvent('sjr:v6:material-activated',{bubbles:true}));
  }
  function refreshMaterial(){
   var all=rows();if(!all.length)return;
   if(!all.includes(activeRow))activeRow=all[0];
   matnav.replaceChildren();
   all.forEach(function(row,i){
    var b=document.createElement('button');b.type='button';b.className='sjr-v6-material-tab';b.dataset.sjrMaterialTab=String(i);
    b.setAttribute('role','tab');b.textContent=(i+1)+'. '+materialName(row,i);
    b.addEventListener('click',function(){activateMaterial(row)});matnav.appendChild(b);
   });
   var plus=document.createElement('button');plus.type='button';plus.className='sjr-v6-material-tab sjr-v6-add-material';plus.textContent='+ Tambah Material';
   plus.addEventListener('click',function(){
    var before=new Set(rows());add.click();
    requestAnimationFrame(function(){var created=rows().find(function(r){return !before.has(r)});if(created)activeRow=created;refreshMaterial();});
   });matnav.appendChild(plus);
   activateMaterial(activeRow);
  }
  observer=new MutationObserver(function(mutations){
   if(!mutations.some(function(m){return m.type==='childList'}))return;
   if(queued)return;queued=true;requestAnimationFrame(function(){queued=false;refreshMaterial()});
  });observer.observe(tbody,{childList:true});
  form.addEventListener('input',function(e){if(e.target.closest('.dynamic-material-row')&&e.target.matches('.mm05-selected-label,input[name$="[name]"]')){var b=matnav.querySelector('[data-sjr-material-tab="'+rows().indexOf(e.target.closest('.dynamic-material-row'))+'"]');if(b)b.textContent=(rows().indexOf(e.target.closest('.dynamic-material-row'))+1)+'. '+materialName(e.target.closest('.dynamic-material-row'),rows().indexOf(e.target.closest('.dynamic-material-row')));}});
  form.addEventListener('change',function(e){if(e.target.closest('.dynamic-material-row'))requestAnimationFrame(refreshMaterial)});
  refreshMaterial();activateSection(0);
 });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>
<script id="sjr-ui-v6-tabs-menyatu-runtime">
(function(){'use strict';
function init(){
 var tabs=document.getElementById('sjr-v6-tabs');
 if(!tabs||tabs.closest('.sjr-v6-unified-workspace'))return;
 var cards=Array.from(document.querySelectorAll('details.item-card'));
 if(!cards.length||cards.length!==tabs.querySelectorAll('[data-sjr-v6-item]').length)return;
 var parent=tabs.parentElement;
 if(!parent||cards.some(function(card){return card.parentElement!==parent}))return;
 // Only relocate contiguous item cards; do not disturb unrelated page elements.
 var node=tabs.nextElementSibling;
 for(var i=0;i<cards.length;i++){
  if(node!==cards[i])return;
  node=node.nextElementSibling;
 }
 var wrapper=document.createElement('div');wrapper.className='sjr-v6-unified-workspace';
 parent.insertBefore(wrapper,tabs);wrapper.appendChild(tabs);
 cards.forEach(function(card){wrapper.appendChild(card)});
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
</script>

<script id="sjr-v6-material-dropdown-runtime">
/* SJR_V6_MATERIAL_DROPDOWN_NAV_V1 */
(function () {
  'use strict';

  function setup(tabs) {
    if (tabs.dataset.materialDropdownReady === '1') return;

    var buttons = Array.from(
      tabs.querySelectorAll('.sjr-v6-material-tab')
    );

    if (!buttons.length) return;

    var nav = document.createElement('div');
    nav.className = 'sjr-material-dropdown-nav';

    var main = document.createElement('div');
    main.className = 'sjr-material-dropdown-main';

    var label = document.createElement('label');
    label.className = 'sjr-material-dropdown-label';

    var select = document.createElement('select');
    select.className = 'sjr-material-dropdown-select';
    select.setAttribute('aria-label', 'Pilih material');

    label.textContent = 'Pilih material';

    main.appendChild(label);
    main.appendChild(select);

    var prev = document.createElement('button');
    prev.type = 'button';
    prev.className = 'sjr-material-dropdown-arrow';
    prev.textContent = '‹';
    prev.title = 'Material sebelumnya';

    var next = document.createElement('button');
    next.type = 'button';
    next.className = 'sjr-material-dropdown-arrow';
    next.textContent = '›';
    next.title = 'Material berikutnya';

    nav.appendChild(main);
    nav.appendChild(prev);
    nav.appendChild(next);

    tabs.parentNode.insertBefore(nav, tabs);

    var activeIndex = 0;
    var currentButtons = [];

    function refresh() {
      currentButtons = Array.from(
        tabs.querySelectorAll('.sjr-v6-material-tab')
      ).filter(function (b) {
        return b.isConnected;
      });

      if (!currentButtons.length) {
        nav.style.display = 'none';
        return;
      }

      nav.style.display = '';
      var selectedButton = currentButtons.findIndex(function (b) {
        return b.getAttribute('aria-selected') === 'true'
          || b.classList.contains('active')
          || b.classList.contains('is-active');
      });

      if (selectedButton >= 0) {
        activeIndex = selectedButton;
      } else {
        activeIndex = Math.min(
          activeIndex,
          currentButtons.length - 1
        );
      }

      select.replaceChildren();

      currentButtons.forEach(function (b, i) {
        var option = document.createElement('option');
        option.value = String(i);
        option.textContent = b.textContent.trim()
          || ('Material ' + (i + 1));
        select.appendChild(option);
      });

      select.value = String(activeIndex);
      label.textContent =
        'Material ' + (activeIndex + 1) +
        ' dari ' + currentButtons.length;

      prev.disabled = activeIndex === 0;
      next.disabled =
        activeIndex === currentButtons.length - 1;
    }

    function activate(i) {
      if (i < 0 || i >= currentButtons.length) return;

      activeIndex = i;

      // Klik tab asli agar logika material lama tetap dipakai.
      currentButtons[i].click();

      refresh();
    }

    select.addEventListener('change', function () {
      activate(Number(select.value));
    });

    prev.addEventListener('click', function () {
      activate(activeIndex - 1);
    });

    next.addEventListener('click', function () {
      activate(activeIndex + 1);
    });

    // Amati perubahan jumlah dan status tab.
    var observer = new MutationObserver(function () {
      if (setup._refreshPending) return;
      setup._refreshPending = true;

      requestAnimationFrame(function () {
        setup._refreshPending = false;
        refresh();
      });
    });

    observer.observe(tabs, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['aria-selected', 'class']
    });

    tabs.dataset.materialDropdownReady = '1';
    tabs.classList.add('sjr-material-nav-ready');

    refresh();
  }

  function scan() {
    document.querySelectorAll(
      '.sjr-v6-material-tabs'
    ).forEach(setup);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }

  new MutationObserver(function () {
    scan();
  }).observe(document.documentElement, {
    childList: true,
    subtree: true
  });
})();
</script>

<script src="{{ asset('vendor/tabler-1.7.5/js/tabler.min.js') }}" defer></script>
<script id="sjr-tabler-preview-safety">document.addEventListener('submit',function(event){event.preventDefault();event.stopImmediatePropagation();window.alert('Mode preview Tabler: penyimpanan dinonaktifkan. Gunakan halaman produksi untuk menyimpan.');},true);</script>
</body></html>
<style id="sjr-v6-2-picker-only">
.dynamic-material-row.mm05-awaiting-selection .mm05-legacy-name,
.dynamic-material-row.mm05-awaiting-selection .material-standard-fields,
.dynamic-material-row.mm05-awaiting-selection .v6-sizes,
.dynamic-material-row.mm05-awaiting-selection .dynamic-quantity-fields,
.dynamic-material-row.mm05-awaiting-selection .legacy-material-spec,
.dynamic-material-row.mm05-awaiting-selection .mm05-calculation { display:none !important; }
</style>

<style id="sjr-v6-3-hide-unit">.dynamic-material-row .dynamic-quantity-fields label:has(select[name$="[unit]"]){display:none!important}</style>

<style id="sjr-new-material-four-fields">
.dynamic-material-row.sjr-is-new-request .sjr-material-category-wrap,
.dynamic-material-row.sjr-is-new-request .mm05-reference-panel,
.dynamic-material-row.sjr-is-new-request .material-standard-fields,
.dynamic-material-row.sjr-is-new-request .v6-sizes,
.dynamic-material-row.sjr-is-new-request .mm05-calculation{display:none!important}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel{display:block!important}
.dynamic-material-row.sjr-is-new-request .dynamic-quantity-fields{display:block!important}
.dynamic-material-row.sjr-is-new-request .dynamic-quantity-fields label:has(select[name$="[unit]"]){display:none!important}
.dynamic-material-row.sjr-is-new-request .sjr-material-spec-visible{min-height:130px}

/* Perapian card Pengajuan Material Baru: field berurutan dan lebih lega. */
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel{
  display:flex!important;
  flex-direction:column;
  gap:14px;
  padding:16px!important;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel > label{
  display:block!important;
  width:100%;
  margin:0!important;
  line-height:1.5;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel .tech-field{
  display:block;
  width:100%;
  margin-top:7px;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel > strong{
  margin-bottom:0;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel > p{
  margin:0 0 2px!important;
  line-height:1.6;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel small{
  line-height:1.5;
}

/* Nama Material dan Quantity berdampingan dalam satu baris di dalam card. */
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel .sjr-new-material-basic-grid{
  display:grid!important;
  grid-template-columns:minmax(0,1.35fr) minmax(150px,.65fr);
  gap:12px 16px;
  width:100%;
  margin:2px 0 4px;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-basic-grid > label{
  display:block!important;
  min-width:0;
  width:100%;
  margin:0!important;
  grid-column:auto!important;
  line-height:1.5;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-basic-grid .tech-field{
  display:block;
  width:100%;
  box-sizing:border-box;
  margin-top:7px;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel > label{
  display:block!important;
  width:100%;
  margin:0 0 14px!important;
  line-height:1.55;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel textarea{
  display:block;
  width:100%;
  min-height:130px;
  box-sizing:border-box;
  margin-top:8px;
}
.dynamic-material-row.sjr-is-new-request .sjr-new-material-panel input[type="file"]{
  display:block;
  width:100%;
  box-sizing:border-box;
  margin-top:8px;
}
@media(max-width:640px){
  .dynamic-material-row.sjr-is-new-request .sjr-new-material-panel .sjr-new-material-basic-grid{
    grid-template-columns:minmax(0,1fr);
    gap:12px;
  }
}
</style>
