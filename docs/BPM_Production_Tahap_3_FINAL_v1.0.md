# BPM Production — Tahap 3 (PROD-03)
## Penyusunan Brief Marketing — FINAL / LOCKED v1.0

**Tanggal pengesahan:** 13 Oktober 2026  
**Status:** FINAL / LOCKED berdasarkan persetujuan pengguna atas Draft Review v1.0.  
**Referensi:** Flowchart SMS 2024, sheet `24082026 (PROD)`; keputusan Tahap 2 dan Tahap 5 yang telah dikunci.  
**Catatan:** Dokumen spesifikasi ini berdiri sendiri dan tidak mengubah Excel sumber.

### 1. Identitas dan tujuan
- Kode: `PROD-03`.
- Nama: Penyusunan Brief Marketing.
- Departemen penanggung jawab: Marketing.
- Departemen pendukung: Desain, ketika diperlukan.
- Input: permintaan klien `RECORDED` dari Tahap 2.
- Output: Brief Marketing siap didistribusikan melalui Tahap 4.
- Tujuan: menerjemahkan kebutuhan klien menjadi Brief Marketing yang dapat dipahami Produksi, dengan bantuan Desain bila diperlukan.

### 2. Alur utama
1. Marketing membuka permintaan klien `RECORDED` dan membuat Draft Brief Marketing.
2. Marketing menyusun enam komponen: jenis pekerjaan, konsep, visualisasi, spesifikasi, durasi, dan kuantitas.
3. Bila perlu dukungan Desain, Marketing membuat Design Request di BPM yang terhubung ke Brief Marketing.
4. Desainer mengambil tugas sendiri dari Task Pool, mengerjakan, dan mengirim hasil kepada Marketing untuk ditinjau.
5. Marketing menerima hasil atau meminta revisi. Penyusunan Brief Marketing dapat berlangsung paralel dengan pengerjaan Desain.
6. BPM memeriksa kelengkapan serta Design Request kritis yang belum selesai.
7. Brief memperoleh kesiapan `READY_FOR_SUBMISSION` atau `READY_WITH_NOTES` dan dilanjutkan ke Tahap 4. Validasi kelayakan untuk Produksi tetap dilakukan pada Tahap 5.

### 3. Struktur Brief Marketing
Enam komponen utama: **jenis pekerjaan, konsep pekerjaan, visualisasi, spesifikasi pekerjaan, durasi, kuantitas**. Data estimasi atau belum final diberi penanda `PROVISIONAL` beserta nilai dan sumber/dasar informasi. Kecukupan informasi bagi kajian produksi ditentukan melalui Tahap 5, bukan secara otomatis di Tahap 3.

### 4. Self-Assignment, Self-Release, revisi
- Marketing membuat Design Request dan menentukannya sebagai tugas bagi Task Pool Desain.
- Desainer yang berwenang menekan **Take Task**; BPM secara atomik menetapkannya sebagai satu-satunya PIC aktif (tidak boleh ada dua PIC aktif untuk satu tugas).
- PIC Desainer bertanggung jawab hingga hasil diterima, tugas dilepaskan, atau tugas dibatalkan secara sah.
- Desainer boleh **Release Task** sendiri tanpa approval, tetapi wajib mengisi alasan. Tugas kembali `AVAILABLE`. Nomor, file, progres dan sejarah penugasan tidak dihapus.
- Desainer mengunggah hasil; Marketing memilih **Accept Result** (`COMPLETED`) atau **Request Revision** dengan catatan (`REVISION_REQUIRED`).
- Revisi kembali ke Desainer sebelumnya. Ia masih boleh melakukan Release Task; setelah itu Desainer lain dapat mengambil tugas.
- `RELEASED` merupakan peristiwa audit, bukan status proses tetap.

### 5. Klasifikasi Kritis / Nonkritis
- BPM merekomendasikan kategori Design Request dari dampaknya terhadap pemahaman pekerjaan; **Marketing menetapkan kategori final dengan alasan**.
- **Kritis:** hasil Desain menentukan konsep utama, ukuran, spesifikasi, atau kelayakan pemahaman pekerjaan. Design Request wajib `COMPLETED` sebelum Brief Marketing dinyatakan siap.
- **Nonkritis:** hasil berupa pelengkap yang tidak menghalangi pemahaman dasar pekerjaan. Brief Marketing dapat `READY_WITH_NOTES`, dengan outstanding, PIC, deadline, dan dampaknya tercatat.
- Perubahan klasifikasi harus direkam (sebelum/sesudah, alasan, identitas dan waktu); BPM menghitung ulang kesiapan. Perubahan setelah Submit mengikuti revisi dan pemeriksaan dampak di Tahap 5.
- Pembatalan Design Request kritis mensyaratkan pemeriksaan ulang kesiapan. Jika ada informasi pengganti yang memadai dari klien, Marketing melampirkannya.

### 6. Deadline dan keterlambatan
- Deadline awal ditetapkan Marketing.
- Desainer dapat **mengajukan perubahan** beserta alasan dan tanggal usulan; Marketing menyetujui atau menolak.
- Hanya setelah disetujui BPM memperbarui deadline; keputusan ditolak mempertahankan deadline lama.
- BPM memantau pengingat menjelang deadline dan indikator `OVERDUE` setelah target terlewati; `OVERDUE` bukan status utama dan tidak otomatis membatalkan/melepas tugas.
- Pengingat H-1 merupakan pengaturan awal yang dapat dikonfigurasi. Semua tanggal target dan perubahan disimpan dalam audit trail.

### 7. Pembatalan Design Request
- Marketing berwenang membatalkan dengan **alasan wajib**.
- Jika tugas belum diambil (`AVAILABLE`), dapat langsung ditetapkan `CANCELLED`.
- Jika tugas sudah diambil, BPM menerbitkan instruksi penghentian dan status `CANCELLATION_PENDING_ACK`; pekerjaan dihentikan dan Desainer wajib mengonfirmasi **penerimaan instruksi**, bukan memberikan approval/veto.
- Setelah konfirmasi penerimaan, tugas menjadi `CANCELLED`. Selama menunggu konfirmasi, BPM tetap menandai pekerjaan untuk dihentikan dan memberi pengingat; hasil baru tidak dapat diajukan sebagai penyelesaian.
- `COMPLETED` tetap menjadi riwayat pekerjaan selesai; pembatalan tidak menghapus file, nomor tugas, progres, atau audit trail.
- Pembatalan tugas kritis menyebabkan pemeriksaan ulang kesiapan Brief Marketing.

### 8. Status workflow
**Brief Marketing**
- `DRAFT`: sedang disusun Marketing.
- `READY_FOR_SUBMISSION`: seluruh persyaratan kesiapan terpenuhi.
- `READY_WITH_NOTES`: siap dengan outstanding nonkritis tercatat.
- `SUBMITTED`: dikirim melalui Tahap 4; aturan revisi berlaku sesuai Tahap 5.

**Design Request**
- `AVAILABLE`: tersedia di Task Pool.
- `IN_PROGRESS`: diambil dan dikerjakan PIC aktif.
- `SUBMITTED_FOR_REVIEW`: hasil menunggu tinjauan Marketing.
- `REVISION_REQUIRED`: hasil dikembalikan kepada PIC sebelumnya.
- `CANCELLATION_PENDING_ACK`: pekerjaan dihentikan, menunggu konfirmasi penerimaan.
- `COMPLETED`: hasil diterima Marketing.
- `CANCELLED`: tugas dibatalkan.

Satu Brief Marketing dapat memiliki beberapa Design Request dengan status berbeda. Status Brief dan tugas Desain dikelola secara terpisah.

### 9. Tanggung jawab dan akses
- **Marketing:** membuat dan mengubah Draft Brief, mengajukan Design Request, memutuskan klasifikasi Kritis/Nonkritis, menentukan target, menyetujui perubahan deadline, meminta revisi/menerima hasil, membatalkan Design Request dan memfinalisasi Brief Marketing.
- **Desainer:** mengambil tugas sendiri, mengerjakan dan menyerahkan hasil, meminta perubahan deadline, melepaskan tugas dengan alasan, mengerjakan revisi dan mengonfirmasi penerimaan pembatalan.
- **BPM:** mengelola Task Pool, menjamin satu PIC aktif, memvalidasi kesiapan, memberi rekomendasi klasifikasi, mengelola status, menyimpan versi, audit dan notifikasi.
- **Asst Manager Produksi:** memvalidasi Brief Marketing pada Tahap 5, bukan mengambil alih keputusan penyusunan Tahap 3.

### 10. Audit Trail dan notifikasi
Rekam peristiwa utama secara append-only: `BRIEF_DRAFT_CREATED`, `DESIGN_REQUEST_CREATED`, `TASK_TAKEN`, `TASK_RELEASED`, `CLASSIFICATION_SET`, `CLASSIFICATION_CHANGED`, `DEADLINE_CHANGE_REQUESTED`, `DEADLINE_CHANGE_DECIDED`, `DESIGN_RESULT_SUBMITTED`, `REVISION_REQUESTED`, `DESIGN_RESULT_ACCEPTED`, `CANCELLATION_REQUESTED`, `CANCELLATION_ACKNOWLEDGED`, `DESIGN_CANCELLED`, `BRIEF_READY`, dan `BRIEF_FINALIZED`/`BRIEF_SUBMITTED` saat terjadi.

Setiap catatan menyimpan pengguna/role, timestamp, referensi Brief/Design Request, aksi, nilai sebelum/sesudah bila ada, alasan/keputusan, dan versi lampiran. Pengguna operasional tidak dapat mengedit atau menghapus peristiwa lampau. Sistem memberikan notifikasi kepada Desainer mengenai tugas/revisi/pembatalan serta kepada Marketing mengenai hasil, perubahan deadline, keterlambatan dan peristiwa terkait. Interval pengingat H-1 dapat dikonfigurasi.

### 11. Konsistensi lintas tahap dan penguncian
- Tahap 2 (`PROD-02`) tetap FINAL / LOCKED: proses hanya dimulai dari permintaan klien `RECORDED`.
- Tahap 5 (`PROD-05`) tetap FINAL / LOCKED: data Provisional, batas Level 3, mekanisme revisi setelah Submit, validasi dampak Minor/Major/Critical, audit trail serta ON HOLD terdampak tetap berlaku.
- Tahap 4, 6, dan 7 belum dikunci. Mekanisme distribusi pada Tahap 4 akan dirumuskan tersendiri.
- **Status dokumen:** FINAL / LOCKED v1.0 atas persetujuan pengguna. Perubahan di masa mendatang memerlukan permintaan revisi eksplisit, bukan pengeditan diam-diam.
