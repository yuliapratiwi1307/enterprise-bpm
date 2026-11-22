# BPM Production — Tahap 4: Distribusi Brief Marketing

**Kode:** PROD-04
**Versi:** FINAL / LOCKED v1.0
**Tanggal persetujuan:** 13 Oktober 2026
**Status:** Disetujui pengguna. Perubahan hanya melalui persetujuan eksplisit berikutnya.
**Sumber:** Flowchart SMS 2024 24082026.xlsx — sheet `24082026 (PROD)`, aktivitas 4. Dokumen ini merupakan spesifikasi BPM hasil kesepakatan dan bukan perubahan file sumber.

## 1. Identitas dan tujuan
- Departemen pengirim/PIC: Marketing.
- Penerima utama: Asst Manager Produksi.
- Penerima salinan: Admin Produksi, tanpa Acknowledge wajib.
- Input: Brief Marketing berstatus `READY_FOR_SUBMISSION` atau `READY_WITH_NOTES` dari Tahap 3.
- Output: Brief Marketing versi terkunci terdistribusi dan tugas validasi Tahap 5 tersedia.
- Proses berikutnya: Tahap 5 — Validasi Brief Marketing.
- SLA Acknowledge: **4 jam kerja** sejak dokumen berhasil tersedia.
- Tujuan: memastikan distribusi versi yang tepat, penerima yang tepat, konfirmasi penerimaan, notifikasi, status, dan audit trail.

## 2. Alur utama
1. Marketing melakukan Submit & Distribute pada Brief Marketing yang siap menurut Tahap 3.
2. BPM menyimpan dan mengunci snapshot/versi dokumen yang dikirim, lalu mendistribusikan otomatis kepada Asst Manager Produksi (utama) dan Admin Produksi (salinan).
3. Setelah distribusi berhasil, BPM membuat/menyediakan tugas validasi Tahap 5 dan menjalankan SLA 4 jam kerja.
4. Asst Manager dapat menekan Acknowledge secara manual **atau langsung memulai validasi Tahap 5**. Memulai validasi otomatis mencatat Acknowledge jika belum ada.
5. Acknowledge adalah konfirmasi penerimaan saja, **bukan persetujuan terhadap isi brief**.
6. Admin Produksi hanya menerima salinan dan tidak diwajibkan memberi Acknowledge.

## 3. Status dan indikator
**Status distribusi utama:**
- `PENDING_DELIVERY`: menunggu atau sedang diproses distribusinya.
- `PENDING_ACKNOWLEDGEMENT`: distribusi berhasil; konfirmasi utama belum ada.
- `ACKNOWLEDGED`: penerimaan dikonfirmasi manual atau otomatis saat memulai validasi.
- `DELIVERY_FAILED`: distribusi gagal, memerlukan pemulihan.

**Indikator tambahan (bukan status utama):**
- `ACK_OVERDUE`: belum Acknowledge setelah 4 jam kerja.
- `DELIVERY_RETRYING`: percobaan pemulihan pengiriman sedang berlangsung.

Status Tahap 4 dipisahkan dari status validasi Tahap 5 (`UNDER_REVIEW`, `VALIDATED`, `RETURNED`, dan lainnya). `ACK_OVERDUE` tidak memblokir validasi Tahap 5.

## 4. SLA dan notifikasi
- SLA dimulai ketika distribusi dinyatakan berhasil dan dokumen tersedia, bukan semata saat tombol Submit ditekan.
- Perhitungan menggunakan kalender kerja BPM; waktu istirahat, jam di luar operasional, dan hari libur terkonfigurasi tidak dihitung.
- Pada jam kerja ke-0: notifikasi brief baru kepada Asst Manager Produksi dan notifikasi salinan kepada Admin Produksi.
- Pada jam kerja ke-3: **satu pengingat** kepada Asst Manager jika belum Acknowledge.
- Pada jam kerja ke-4: indikator `ACK_OVERDUE` dan notifikasi kepada **Asst Manager Produksi serta Marketing**, tanpa eskalasi otomatis ke Manager Produksi.
- Acknowledge menghentikan pengingat yang belum terkirim. Ketika Acknowledge berhasil, Marketing menerima konfirmasi.
- Revisi baru: Asst Manager dan Admin Produksi menerima notifikasi versi baru.
- Distribusi gagal: Marketing dan administrator BPM mendapat peringatan; pemulihan berhasil diberitahukan kepada Marketing dan pihak relevan.
- Saluran utama notifikasi ialah internal BPM; WhatsApp/email adalah opsi integrasi di masa depan.

## 5. Distribusi gagal dan kegagalan parsial
- Distribusi berhasil apabila akses dokumen untuk kedua penerima tercatat dan tugas validasi Tahap 5 untuk Asst Manager Produksi tersedia.
- Jika distribusi gagal, status `DELIVERY_FAILED`; SLA belum dimulai; error dan waktu dicatat.
- BPM mencoba pengiriman ulang dengan mekanisme idempotent agar tidak membuat tugas atau salinan ganda.
- Jika tetap gagal, Marketing dan administrator BPM diberitahu untuk penanganan.
- Jika hanya distribusi salinan Admin Produksi yang gagal, tugas validasi Asst Manager boleh tersedia, namun distribusi **belum dinyatakan lengkap** hingga salinan pulih; ini merupakan kasus uji implementasi khusus dan tidak boleh memunculkan duplikasi tugas.
- Saat seluruh syarat distribusi berhasil dipenuhi, SLA dihitung dari waktu keberhasilan sebenarnya.

## 6. Kontrol versi dan hubungan dengan tahap lain
- Setiap Submit menghasilkan versi resmi yang tidak dapat ditimpa.
- Setiap revisi yang disubmit didistribusikan ulang sebagai versi terpisah; Acknowledge Rev. 01 tidak menjadi Acknowledge Rev. 02.
- Riwayat dokumen, penerima, dan waktu penerimaan tiap versi disimpan.
- Persyaratan kesiapan dari Tahap 3 FINAL/LOCKED berlaku tetap.
- Aturan Provisional, perubahan Minor/Major/Critical, validasi ulang dan ON HOLD aktivitas terdampak mengacu pada Tahap 5 FINAL/LOCKED, tidak ditetapkan ulang di Tahap 4.

## 7. Hak akses
- **Marketing:** Submit, membaca status distribusi, menerima pemberitahuan, menindaklanjuti kegagalan.
- **Asst Manager Produksi:** akses brief, Acknowledge, memulai validasi Tahap 5.
- **Admin Produksi:** akses salinan brief sesuai hak akses.
- **BPM:** otomatis mengunci versi, mendistribusikan, menyediakan tugas, memantau SLA, notifikasi dan audit.
- **Administrator BPM:** menangani kegagalan teknis tanpa berwenang mengubah isi brief melalui mekanisme pemulihan.
- Tidak ada approval tambahan untuk distribusi jika kesiapan Tahap 3 telah terpenuhi.

## 8. Audit trail
BPM merekam minimal:
- `BRIEF_SUBMITTED`: pengguna, waktu, nomor brief, revisi.
- `DISTRIBUTION_STARTED`: penerima, waktu, referensi versi.
- `DOCUMENT_DELIVERED`: waktu tersedia dan hasil pengiriman.
- `DOCUMENT_VIEWED`: pengguna dan waktu akses.
- `ACKNOWLEDGEMENT_COMPLETED`: pengguna, waktu, metode manual/otomatis.
- `REMINDER_SENT`: penerima dan waktu.
- `ACK_OVERDUE_DETECTED`: waktu keterlambatan.
- `DELIVERY_FAILED`: waktu, alasan dan penerima terdampak.
- `DELIVERY_RETRIED`: tiap percobaan dan hasilnya.
- `BRIEF_REDISTRIBUTED`: referensi revisi lama/baru serta penerima.

Audit trail untuk pengguna operasional bersifat append-only; tidak boleh ditimpa atau dihapus. Acknowledge otomatis dan manual dibedakan. Kegagalan sebelumnya tetap disimpan meski retry kemudian berhasil.

## 9. Keputusan final disetujui
1. Distribusi otomatis dengan Acknowledge Asst Manager Produksi.
2. Admin Produksi menerima salinan tanpa Acknowledge wajib.
3. Tahap 5 boleh dimulai langsung setelah distribusi berhasil; memulai validasi mencatat Acknowledge otomatis.
4. SLA Acknowledge empat jam kerja.
5. Notifikasi pengingat pada jam kerja ketiga dan `ACK_OVERDUE` pada jam kerja keempat.
6. Keterlambatan diinformasikan kepada Asst Manager Produksi dan Marketing, tanpa eskalasi otomatis ke Manager Produksi.
7. `ACK_OVERDUE` tidak menghalangi validasi Tahap 5.
8. Distribusi gagal/parsial ditangani dengan pencatatan error, pengiriman ulang tanpa duplikasi, dan notifikasi.
9. Status distribusi terpisah dari status validasi dan status indikator SLA.
10. Setiap versi dokumen memiliki distribusi, konfirmasi, dan audit trail tersendiri.

**Catatan perubahan:** Semua perubahan berikutnya memerlukan pembukaan kunci/persetujuan baru dari pengguna. Tahap 2, 3, dan 5 tidak diubah oleh dokumen ini.
