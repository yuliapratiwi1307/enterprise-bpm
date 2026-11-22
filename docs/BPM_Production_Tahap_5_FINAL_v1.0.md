# BPM Production — Tahap 5: Validasi Brief Marketing
**Status:** FINAL / LOCKED v1.0  
**Tanggal persetujuan:** 12 Oktober 2026  
**Ruang lingkup:** Tahap 5 saja. Tahap 2–4 dan 6–7 tetap DRAFT / OPEN.  
**Dokumen sumber:** Flowchart SMS 2024 24082026.xlsx, sheet PROD (tidak diubah).

## Identitas
- Kode yang digunakan dalam rancangan: PROD-05.
- Input: Brief Marketing yang diserahkan melalui Tahap 4.
- Pemilik validasi: Asst Manager Produksi.
- Pemberi keputusan atas penggunaan data Provisional: Manager Produksi.
- Pengirim/pemilik perbaikan Brief Marketing: Marketing.
- Output: brief tervalidasi (dengan atau tanpa catatan), atau dikembalikan untuk revisi; hasil yang diizinkan menjadi input Tahap 6.

## Keputusan final
1. Asst Manager Produksi memeriksa kecukupan informasi dan dapat mengembalikan brief ke Marketing dengan alasan yang tercatat.
2. Berlaku **validasi bersyarat**: informasi kritis perlu memadai, namun boleh berupa estimasi awal (*Provisional*) dengan sumber dan catatan; informasi nonkritis bisa menyusul.
3. Kriteria awal informasi kritis: jenis pekerjaan, konsep, kuantitas, spesifikasi utama, target waktu; visualisasi dan detail lainnya menjadi wajib sesuai dampaknya terhadap pekerjaan.
4. Asst Manager memberikan hasil validasi dan rekomendasi; **Manager Produksi** memutuskan kelanjutan aktivitas berbasis data Provisional.
5. Data Provisional dapat dipakai untuk kajian awal, HPP estimasi, lead time, analisis risiko, dan persiapan terbatas yang aman. **Tidak ada pengecualian Level 3**: produksi fisik dan pembelian material yang tidak dapat dibatalkan harus memakai data teknis relevan yang sudah final dan memenuhi approval tahap berikutnya.
6. Selama Draft, perubahan tidak menaikkan nomor revisi. Begitu Submitted, versi menjadi snapshot yang tidak dapat ditimpa; submit perubahan berikutnya menghasilkan revisi baru.
7. Validasi ulang didasarkan pada **dampak revisi**: Minor = pemeriksaan administratif; Major = validasi teknis ulang; Critical = evaluasi risiko dan penangguhan aktivitas terdampak.
8. BPM mendeteksi perbedaan antarversi dan merekomendasikan kategori Minor/Major/Critical. Asst Manager memvalidasi kategorinya dan menuliskan alasan bila mengubah rekomendasi. Penurunan klasifikasi rekomendasi Critical memerlukan persetujuan Manager Produksi.
9. Revisi Critical menyebabkan **ON HOLD otomatis hanya pada aktivitas terdampak dan aktivitas turunannya yang bergantung pada data berubah**. Aktivitas yang belum dapat dinyatakan tidak terdampak ditangguhkan sementara sampai diperiksa; aktivitas yang terbukti tidak terdampak boleh terus berjalan.
10. Status ON HOLD hanya dilepas setelah evaluasi dan keputusan Manager Produksi, sesuai batas kewenangan dan syarat data final yang relevan.

## Status workflow yang disetujui
- `DRAFT`: Marketing menyusun atau mengedit brief.
- `SUBMITTED`: versi brief telah dikirim.
- `UNDER_REVIEW`: Asst Manager memeriksa.
- `RETURNED`: brief dikembalikan, Marketing wajib perbaiki dan resubmit.
- `VALIDATED`: informasi memenuhi kriteria validasi.
- `VALIDATED_WITH_NOTES`: cukup untuk kajian, ada informasi/catatan Provisional.
- `PENDING_MANAGER`: membutuhkan keputusan Manager Produksi.
- `APPROVED_FOR_REVIEW`: diizinkan melanjutkan kajian awal sesuai batas.
- `APPROVED_CONDITIONAL`: aktivitas tertentu boleh dilanjutkan dengan pembatasan.
- `ON_HOLD`: status kontrol untuk aktivitas terdampak, bukan pengganti status dokumen.

## Audit trail, revisi dan notifikasi
- Audit trail aplikasi bersifat append-only: waktu, pengguna/role, dokumen dan revisi, tindakan, status lama/baru, field yang berubah dan nilai sebelum/sesudah, status data Provisional/Final, alasan, kategori dampak yang direkomendasikan/disahkan, hasil validasi, keputusan Manager, cakupan ON HOLD, waktu pelepasan, dan dampak pada proses/dokumen turunannya.
- Riwayat keputusan dan versi dokumen sebelumnya tidak dihapus atau ditimpa melalui aplikasi operasional. Koreksi dicatat sebagai event baru.
- Pemberitahuan: Submit kepada Asst Manager; Return kepada Marketing; validasi/permintaan keputusan kepada Manager bila relevan; revisi Major/Critical kepada pemangku kepentingan terdampak; ON HOLD dan pelepasan kepada PIC aktivitas terkait.
- Akses: Marketing mengelola isi brief, Asst Manager melakukan validasi, Manager Produksi mengambil keputusan bersyarat dan penangguhan, Admin Produksi melihat brief relevan untuk proses berikutnya. Semua akses sesuai penugasan dan tidak mengizinkan perubahan log audit.

## Kendali perubahan
Spesifikasi Tahap 5 ini disetujui pengguna sebagai **FINAL / LOCKED v1.0**. Perubahan berikutnya wajib diminta/disetujui secara eksplisit dan diterbitkan sebagai versi baru; jangan diam-diam menimpa keputusan. Kesepakatan ini tidak mengunci Tahap 2–4 dan 6–7 atau mengesahkan seluruh flowchart.
