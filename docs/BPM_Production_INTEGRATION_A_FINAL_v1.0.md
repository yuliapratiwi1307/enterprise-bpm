# BPM Production — INTEGRATION-A
## Struktur Dokumen, Penomoran, Relasi Versi, dan Pengendalian Lingkup

**Status:** FINAL / LOCKED v1.0  
**Tanggal persetujuan:** 13 Oktober 2026  
**Cakupan:** PROD-02 sampai PROD-07  
**Kedudukan:** Spesifikasi integrasi terpisah; tidak mengubah spesifikasi PROD-02 s.d. PROD-07 yang telah FINAL / LOCKED, maupun file Excel flowchart sumber. Jika ketentuan integrasi ditafsirkan bertentangan dengan otorisasi atau gerbang kontrol tahapan yang terkunci, kontrol tahap terkait tetap berlaku dan konflik harus diajukan untuk keputusan perubahan eksplisit.

### 1. Tujuan dan prinsip
Menjamin identitas permanen, relasi induk–turunan, ruang lingkup, versi sumber, integritas riwayat, dan penelusuran dampak perubahan untuk Client Request, Brief Marketing, Design Request, Brief Produksi, Technical Reference Request (TRR), serta hubungan awal dengan HPP. Production Case adalah wadah sistem, bukan tahapan atau gerbang approval baru.

### 2. Model hubungan
- Satu Production Case memiliki satu Client Request utama; dibuat otomatis saat Client Request menjadi RECORDED.
- Satu Client Request dapat menghasilkan **lebih dari satu Brief Marketing** dengan lingkup terpisah dan eksplisit; satu Brief Marketing tetap merujuk satu Client Request utama dalam rancangan v1.0.
- Satu Brief Marketing dapat menghasilkan **lebih dari satu Brief Produksi** menurut paket teknis yang jelas; satu Brief Produksi merujuk revisi sumber Brief Marketing yang spesifik dalam rancangan v1.0.
- Satu Brief Marketing dapat mempunyai beberapa Design Request; satu Draft Brief Produksi dapat mempunyai beberapa TRR. Penugasan, pembatalan dan kewenangannya tetap mengikuti PROD-03 dan PROD-06.
- Setiap dokumen berada dalam Production Case yang sama dengan dokumen sumber. Relasi HPP terhadap versi Brief Produksi dicatat; kardinalitas, kalkulasi dan approval HPP secara rinci diputuskan dalam PROD-08.
- Tidak ada penggabungan otomatis antardokumen, permintaan, atau lingkup.

### 3. Production Case dan pengendalian Scope_ID
- Production Case: nomor contoh `PC-2026-0001`, terkait Client Request `REQ-2026-0001`, mengelompokkan seluruh BM, DR, BP, TRR dan referensi HPP; tidak menambah approval atau mengubah PIC tahapan.
- Pemecahan pekerjaan pada Client Request yang sama tidak membuat Case baru. Jika dibatalkan, Case dan riwayat dokumennya tidak dihapus; status mengikuti keputusan dan dampak proses.
- `Scope_ID` mengidentifikasi paket/lingkup secara terpisah dari nomor dokumen. Minimum: deskripsi, batas lingkup, kuantitas, satuan, sumber/parent scope, penanggung jawab, identitas Case, dan status.
- Marketing bertanggung jawab atas pembagian lingkup Brief Marketing; Manager Produksi atas pembagian lingkup Brief Produksi.
- BPM memperingatkan potensi duplikasi lingkup/kuantitas, tidak otomatis menggabungkan, menghapus atau menghitung ulang paket. Pemecahan paket tidak boleh meniadakan referensi TRR Kritis, validasi atau pembatasan Provisional/Level 3.

### 4. Nomor independen per jenis dokumen
Format `PREFIX-YYYY-NNNN` dengan tahun saat nomor resmi pertama diterbitkan, urutan terpisah per jenis dokumen, minimal 4 digit dan dapat berkembang melampaui 9999. Format: `PC`, `REQ`, `BM`, `DR`, `BP`, `TRR`; format `HPP` merupakan acuan awal yang harus diselaraskan saat PROD-08 dibahas.
- `PC-YYYY-NNNN`: otomatis ketika Client Request resmi RECORDED.
- `REQ-YYYY-NNNN`: saat Submit / RECORDED sesuai PROD-02.
- `BM-YYYY-NNNN`: saat Submit resmi pertama melalui PROD-04, dengan aturan revisi sesuai PROD-05.
- `DR-YYYY-NNNN`: ketika Design Request resmi diterbitkan/tersedia untuk mekanisme tugas PROD-03.
- `TRR-YYYY-NNNN`: ketika Manager Produksi resmi mengajukan TRR di PROD-06.
- `BP-YYYY-NNNN`: saat Manager Produksi pertama kali menerbitkan Brief Produksi pada PROD-07.
- `HPP-YYYY-NNNN`: usulan identitas pada Tahap 8, belum mengunci detail PROD-08.
Nomor resmi unik, otomatis, stabil sepanjang umur dokumen; tidak berubah karena revisi, tahun berikutnya, perubahan nama, perubahan PIC, maupun pemecahan paket. Nomor yang sudah dialokasikan tidak dipakai ulang; jeda urutan dapat diaudit. Hubungan ke induk tidak dimasukkan dalam nomor dokumen, tetapi disimpan melalui kunci relasi.

### 5. Identitas Draft, dokumen, revisi resmi
- **Document ID:** ID internal permanen, dibuat ketika objek mulai disimpan, termasuk Draft.
- **Draft Version ID:** penunjuk stabil terhadap draft/versi pekerjaan agar Design Request atau TRR dapat terhubung sebelum nomor resmi diterbitkan.
- **Official Revision ID:** snapshot resmi yang dikunci ketika Submit atau Issue; nomor tampilan `Rev.00`, `Rev.01`, dst. untuk dokumen terkait.
- Revisi Brief Marketing setelah Submit mengikuti PROD-05; revisi Brief Produksi setelah Issue mengikuti PROD-07. Perubahan Draft tidak otomatis menaikkan nomor revisi resmi. Hasil revisi DR/TRR tidak otomatis menciptakan revisi Brief Marketing/Produksi.
- Nomor BP resmi yang baru diberikan pada Issue tidak memutus riwayat TRR yang terkait Draft Version ID: BPM menyimpan pemetaan draft-ke-revisi resmi.
- Revisi yang sudah resmi tidak dapat ditimpa. Saat revisi Brief Produksi berikutnya diterbitkan, versi sebelumnya menjadi SUPERSEDED untuk penggunaan baru namun tetap tersedia sebagai histori, mengikuti kontrol PROD-07.

### 6. Relasi revisi sumber
- BM merujuk Case, Client Request dan lingkup.
- DR merujuk BM beserta Draft/Revision ID relevan.
- BP merujuk Official Revision ID BM dan Scope_ID tertentu.
- TRR merujuk Draft Version ID BP beserta sumber BM yang relevan; pada Issue ditambahkan pemetaan ke versi BP resmi.
- HPP harus merujuk Official Revision ID BP yang dipakai; ketentuan proses HPP detail menjadi ruang lingkup PROD-08.
- Ketika revisi sumber baru muncul, relasi histori pada versi turunan lama **tidak** diubah diam-diam. Perubahan harus dinilai, diputuskan, dan dicatat sesuai otoritas tahapan.

### 7. Propagasi dampak sumber
BPM mengidentifikasi turunan dan aktivitas yang mengacu ke versi sumber lama, lalu mengirim pemberitahuan dan rekomendasi pemeriksaan dampak; keputusan substantif tetap pada otoritas dalam PROD-03, PROD-05, PROD-06, dan PROD-07.
- Belum mulai: periksa kelayakan menggunakan sumber terbaru.
- Sedang dikerjakan: PIC menerima notifikasi dan pemeriksaan relevansi.
- Sudah terbit: snapshot tetap utuh; perubahan bila perlu menghasilkan revisi baru.
- Minor: pemeriksaan sederhana/audit. Major: review bagian terkait. Critical: ON HOLD hanya kegiatan terdampak, dependensi downstream, dan kegiatan yang belum dapat dipastikan bebas dampak, sesuai PROD-05.
- Hasil DR/TRR yang tiba setelah revisi sumber tetap tersimpan dan dinilai relevansinya sebelum digunakan.
- HPP mengikuti impact-based control pada PROD-07; pekerjaan tidak terdampak tetap dapat berlangsung.
- BPM dilarang melakukan silent merge, silent overwrite, penghapusan histori, otomatis menyelesaikan TRR kritis akibat revisi sumber, atau mencabut ON HOLD tanpa keputusan/otorisasi sah.

### 8. Pembatalan dan retensi
Draft yang dibatalkan, request pendukung yang dibatalkan, dan Client Request yang dibatalkan tidak menyebabkan penghapusan jejak audit dan dokumen terkait. Pembatalan lintas tahapan memerlukan pemeriksaan dampak. Rincian orkestrasi pembatalan dibahas di INTEGRATION-D; mekanisme yang sudah dikunci pada masing-masing tahap tetap berlaku.

### 9. Rancangan minimum data
`ProductionCase(Case_ID, Case_Number, Client_Request_ID)`; `Document(Document_ID, Document_Type, Document_Number, Case_ID)`; `DocumentVersion(Version_ID, Document_ID, Revision, Status, Snapshot)`; `DocumentRelation(Relation_ID, Source_Version_ID, Target_Version_ID, Relation_Type)` dengan dukungan Draft Version ID; `WorkScope(Scope_ID, Parent_Scope_ID, Case_ID, Description, Quantity, Unit)`; `DocumentScope(Document_ID, Scope_ID, Relationship)`; `ImpactAssessment(Assessment_ID, Source_Change_ID, Classification, Decision)`; `AuditEvent(Event_ID, Actor_ID, Action, Timestamp, Object_ID)`.
Alokasi nomor, penguncian snapshot, dan pencatatan distribusi harus konsisten dan tahan retry. Kegagalan distribusi tidak boleh menyebabkan Issue atau Submit duplikat.

### 10. Konsistensi, kewenangan, dan batasan
- PROD-02: nomor REQ resmi saat RECORDED.
- PROD-03: Design Request dan Self-Assignment tetap berlaku.
- PROD-04: Submit/Distribute BM dan snapshot tetap berlaku.
- PROD-05: keputusan validasi, kenaikan revisi BM setelah Submit, Provisional, pengendalian impact-based dan Level 3 tetap berlaku.
- PROD-06: TRR, kategorisasi, deadline dan kesiapan Draft BP tetap berlaku.
- PROD-07: Issue BP, distribusi, Acknowledge, revisi, HPP impact-based tetap berlaku.
- Klasifikasi dampak perubahan BM tetap mengikuti otoritas Asst Manager Produksi pada PROD-05; keputusan teknis BP tetap di tangan Manager Produksi.
- Spesifikasi ini **FINAL/LOCKED** berdasarkan persetujuan pengguna, berdiri sendiri, tanpa mengubah berkas Excel sumber atau versi FINAL masing-masing tahap. Integrasi status/handover, revisi lintas tahapan, dan pembatalan operasional akan diperinci dalam INTEGRATION-B, C dan D tanpa melakukan perubahan diam-diam.
