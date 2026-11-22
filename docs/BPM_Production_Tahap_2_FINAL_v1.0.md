# BPM Production — Tahap 2: Penerimaan Brief Klien

**Kode:** PROD-02  
**Versi:** FINAL v1.0  
**Status:** FINAL / LOCKED — disetujui pengguna 12 Oktober 2026  
**Sumber acuan:** `Flowchart SMS 2024 24082026.xlsx`, sheet `24082026 (PROD)`, aktivitas nomor 2.  
**Lingkup:** spesifikasi BPM Tahap 2 saja. Dokumen Excel sumber tidak diubah.

## 1. Tujuan dan batas proses
Marketing menerima kebutuhan klien melalui komunikasi verbal ataupun nonverbal (termasuk WhatsApp, telepon, email, meeting, atau lainnya) dan mencatatnya dalam BPM **sebelum Tahap 3 — Penyusunan Brief Marketing**. Catatan awal tidak harus memuat detail teknis final. Pemeriksaan kelayakan teknis tetap berada di Tahap 5 yang telah FINAL / LOCKED v1.0.

**PIC utama:** Marketing. **Input:** permintaan klien. **Output:** permintaan tercatat dengan nomor resmi (`RECORDED`) atau catatan berstatus akhir lainnya sesuai aturan. **Tahap berikutnya:** Tahap 3.

## 2. Data wajib dan opsional
| Field | Aturan |
|---|---|
| Nama klien | Wajib sebelum submit |
| Sumber komunikasi | Wajib sebelum submit |
| Jenis kebutuhan | Wajib sebelum submit |
| Deskripsi permintaan | Wajib sebelum submit |
| PIC klien | Opsional, boleh menyusul |
| Target waktu | Opsional |
| Lokasi pekerjaan | Opsional |
| Lampiran pendukung | Opsional |
| PIC Marketing, waktu pencatatan, ID Draft | Otomatis oleh BPM |
| Nomor permintaan resmi | Dibuat BPM setelah submit berhasil |

Field wajib tidak boleh kosong saat submit. Informasi dapat bersifat awal/estimasi; bila relevan di tahap selanjutnya diberi penanda Provisional sesuai ketentuan Tahap 5.

## 3. Penomoran dan alur utama
1. Klien menyampaikan permintaan; Marketing dapat mulai merekamnya sebagai `DRAFT` dengan ID internal, tanpa nomor resmi.
2. Marketing boleh menyimpan dan memperbarui Draft; BPM mencatat riwayat perubahan.
3. BPM memeriksa field wajib dan menawarkan hasil deteksi kemungkinan duplikasi.
4. Marketing meninjau temuan duplikasi dan memutuskan apakah ini permintaan baru, kelanjutan, atau duplikat.
5. Untuk permintaan baru yang lengkap, Marketing melakukan submit; BPM menerbitkan nomor resmi unik dan mengubah status ke `RECORDED`.
6. Tahap 3 menggunakan nomor permintaan sebagai referensi permanen; awal Tahap 3 direkam sebagai event, tanpa mengubah status utama Tahap 2.

**Format penomoran contoh:** `REQ-2026-0001`; format teknis dapat diputuskan saat desain aplikasi. Nomor tidak bisa diedit, tidak digunakan ulang, dan tetap tersimpan jika dibatalkan.

## 4. Penanganan permintaan serupa
BPM **mendeteksi dan menyarankan**, bukan otomatis menyatukan permintaan, dengan mengacu pada klien, jenis pekerjaan, lokasi, periode/target waktu, dan deskripsi. Keputusan tetap pada Marketing:
- **Permintaan baru:** nomor baru setelah submit.
- **Kelanjutan:** tautkan informasi kepada permintaan utama.
- **Duplikat:** tandai catatan `DUPLICATE`, dengan referensi ke permintaan utama dan alasan.

Untuk kelanjutan/duplikat yang diputuskan saat masih Draft, usulan implementasi yang termasuk dalam persetujuan ini adalah menyimpan ID internal tanpa perlu menerbitkan nomor resmi baru. Data tidak dihapus; keputusan dan alasan dicatat di audit trail.

## 5. Status dan transisi
| Status | Makna |
|---|---|
| `DRAFT` | Permintaan sedang dicatat/dilengkapi, dapat diedit Marketing |
| `RECORDED` | Data minimum terpenuhi, sudah submit dan nomor resmi diterbitkan |
| `CANCELLED` | Permintaan dibatalkan sesuai otorisasi tahapan, riwayat tetap disimpan |
| `DUPLICATE` | Catatan ditetapkan duplikat oleh Marketing, tertaut permintaan utama |

`INCOMPLETE`/`COMPLETE` adalah indikator validasi field di Draft, bukan status utama. `STAGE_3_STARTED` adalah event audit, bukan status utama. Untuk pembatalan saat sudah masuk tahap lain, `CANCELLATION_REQUESTED` digunakan sebagai status **permohonan terpisah** dan tidak langsung mengganti status utama.

## 6. Kewenangan pembatalan (disepakati: berdasarkan tahapan)
- **Draft dan Recorded, sebelum Tahap 3:** Marketing dapat membatalkan dengan alasan yang wajib diisi.
- **Sejak Tahap 3 dimulai:** pembatalan harus melalui pemeriksaan dampak dan persetujuan sesuai proses yang sedang berlangsung; tidak boleh langsung ditetapkan `CANCELLED` hanya oleh Marketing.
- **Tahap 5 atau lebih:** periksa pula keputusan Produksi, dokumen, aktivitas dan komitmen yang sudah berjalan.
- Detail pejabat penyetuju untuk Tahap 3–4 dan tahap berikutnya **ditentukan pada pembahasan tahap masing-masing**; bukan izin tersirat untuk membatalkan tanpa otorisasi.
- Pembatalan tidak menghapus nomor, dokumen, maupun audit trail.

## 7. Audit trail wajib
Peristiwa: `DRAFT_CREATED`, `DRAFT_UPDATED`, `DUPLICATE_CHECKED`, `DUPLICATE_DECISION`, `REQUEST_RECORDED`, `STAGE_3_STARTED`, `CANCELLATION_REQUESTED`, `CANCELLATION_APPROVED`, `REQUEST_CANCELLED` (sesuai kejadian). Rekam pengguna/role, waktu, data lama dan baru, sumber komunikasi, alasan keputusan duplikasi/pembatalan, nomor resmi dan relasi dokumen. Riwayat append-only dari antarmuka operasional; koreksi menjadi event baru dan tidak menimpa peristiwa sebelumnya.

## 8. Hak akses dan notifikasi
Marketing membuat/mengubah Draft, submit, memutuskan duplikasi, membatalkan sebelum Tahap 3, dan mengajukan pembatalan setelahnya. BPM melakukan validasi field, deteksi duplikasi, penomoran dan pencatatan. Asst Manager Produksi, Manager Produksi, serta Admin Produksi memperoleh akses relevan sesuai penugasan/tahap, bukan approval teknis di Tahap 2. Notifikasi minimum: permintaan berhasil direkam, potensi duplikasi, pembatalan, dan permintaan pembatalan yang memerlukan keputusan pihak terkait.

## 9. Batas penguncian
**Terkunci:** prinsip dan aturan dalam spesifikasi PROD-02 v1.0 ini, sesuai persetujuan pengguna. **Tidak ikut terkunci:** Tahap 3, 4, 6, 7; detail pejabat approval pembatalan setelah Tahap 3, format teknis ID/nomor dan rancangan antarmuka final. **Tahap 5:** tetap FINAL / LOCKED v1.0 tanpa perubahan. Setiap perubahan spesifikasi terkunci memerlukan persetujuan eksplisit pengguna dan versi baru.
