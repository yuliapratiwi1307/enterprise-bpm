# BPM Production — INTEGRATION-D FINAL / LOCKED v1.0

**Nama:** Pembatalan, Penghentian Pekerjaan, Scope_ID dan Aktivitas Paralel  
**Pengesahan pengguna:** 13 Oktober 2026  
**Status:** FINAL / LOCKED v1.0  
**Cakupan:** PROD-02 sampai PROD-07; integrasi dengan INTEGRATION-A, B dan C.  
**Posisi dokumen:** Lapisan koordinasi pembatalan lintas tahap, tidak mengganti kewenangan maupun status dalam spesifikasi PROD-02–07. Tidak mengubah Excel sumber atau spesifikasi FINAL lain. Detil otorisasi HPP dan konsekuensi biaya dibahas pada PROD-08.

## 1. Tujuan dan keputusan kunci
Mengendalikan pembatalan Client Request atau lingkup kerja secara berbasis dampak, mempertahankan riwayat dan output yang sudah ada, memastikan penghentian aktual kegiatan terdampak, serta mengizinkan kegiatan yang terbukti independen untuk tetap berjalan.

Dua keputusan eksplisit pengguna:
1. **Impact-Based Cancellation Control:** ketika pembatalan diajukan, BPM menelusuri Scope_ID dan dependensi, segera mengamankan aktivitas berisiko (terutama komitmen baru yang tidak dapat dibatalkan) pada lingkup yang berpotensi terdampak, tanpa menghentikan seluruh Production Case secara otomatis. Pembatalan permanen tetap memerlukan keputusan sesuai kewenangan.
2. **PIC acknowledgement dengan eskalasi:** PIC aktif wajib mengonfirmasi instruksi penghentian dan melaporkan progres. Jika PIC tidak dapat dihubungi, penanggung jawab departemen dapat memverifikasi penghentian berdasarkan bukti dan audit trail. PIC tidak mempunyai hak veto. Verifikasi pengganti **bukan** Acknowledge oleh PIC dan tidak boleh dicatat seolah-olah PIC mengonfirmasi.

## 2. Prinsip pengendalian
- Pembatalan, `ON_HOLD`, `SUPERSEDED`, dan revisi adalah konsep berbeda dan tidak saling menggantikan.
- Dokumen resmi dan riwayat tugas bersifat immutable/append-only dari antarmuka operasional; tidak ada penghapusan otomatis.
- Pengamanan awal dapat berlaku saat permohonan pembatalan masih dinilai; penghentian permanen memerlukan otorisasi bisnis dan/atau teknis terkait.
- Pengendalian dilakukan per Scope_ID, dokumen, tugas dan dependensi, bukan stop seluruh Case secara menyeluruh.
- Hasil pekerjaan/biaya yang sudah terjadi tidak dibatalkan secara retroaktif.
- Penghentian kerja fisik harus dilakukan secara aman oleh personel operasional berwenang; perubahan status dalam BPM tidak membuktikan penghentian fisik.
- Tidak ada kewenangan otomatis untuk membatalkan kontrak, pengadaan, atau transaksi keuangan pihak ketiga.

## 3. Workflow Cancellation Case
1. `CANCELLATION_REQUESTED`: pemohon memberikan alasan, objek, Scope_ID, dan waktu.
2. Immediate Risk Screening: BPM/penanggung jawab mengidentifikasi aktivitas dan komitmen berisiko; pembatasan awal diberlakukan hanya pada yang relevan.
3. Dependency & Scope Assessment: menggunakan Production Case, Document Version, Scope_ID, Task_ID dan dependency dari INTEGRATION-A/C, serta handover INTEGRATION-B.
4. Authority Decision: pengaju dan pihak tahap yang berwenang memutuskan pembatalan, penolakan, atau tindak lanjut/klarifikasi.
5. Stop Work Instruction: perintah kepada PIC untuk pekerjaan yang sah dibatalkan, dengan cakupan dan waktu yang jelas.
6. PIC Acknowledge atau supervisor verification melalui jalur eskalasi; hasil kerja terakhir dilaporkan.
7. Progress & Commitment Reconciliation: progres, output, biaya, material, pengadaan, kewajiban pihak ketiga, dan HPP terkait dicatat serta diteruskan kepada pemilik kewenangan.
8. Cancellation Case Closure jika seluruh kewajiban penanganan dan bukti penghentian terpenuhi.

Penolakan pembatalan tidak otomatis melepas pembatasan lain; pelepasan mengikuti otorisasi dan kondisi ON HOLD INTEGRATION-C.

## 4. Kewenangan per tahap
| Kondisi | Kewenangan/penanganan |
|---|---|
| Sebelum PROD-03 | Marketing dapat membatalkan Client Request dengan alasan sesuai PROD-02. |
| PROD-03 berjalan | Marketing menangani kebutuhan klien dan Design Request; konfirmasi Desainer mengikuti PROD-03. |
| PROD-04 selesai distribusi | Asst Manager diberi informasi dampak validasi; distribusi resmi dan audit tidak dihapus. |
| PROD-05 tervalidasi | Asst Manager menilai dampak validasi; Manager Produksi menilai dampak teknis yang relevan. |
| PROD-06 berjalan | Manager Produksi berwenang atas kajian/penanganan TRR; pembatalan referensi Kritis memicu pemeriksaan ulang kesiapan. |
| PROD-07 telah Issue | Manager Produksi menentukan tindak lanjut teknis; snapshot Brief Produksi yang telah diterbitkan tetap historis. |
| HPP dimulai | Admin Produksi diberi penanda untuk evaluasi HPP; aturan approval/koreksi finansial menunggu PROD-08. |
| Pengadaan/komitmen eksternal | Ditangani berdasarkan prosedur dan otorisasi pemilik komitmen yang berlaku. |

Marketing tetap menangani komunikasi kebutuhan klien, tetapi tidak mendapat wewenang otomatis untuk membatalkan pekerjaan teknis produksi di luar perannya. Keputusan yang melibatkan beberapa otoritas dipecah menjadi kewajiban review masing-masing.

## 5. Pembatalan parsial Scope_ID
- Pengaju memilih Scope_ID yang akan dibatalkan. BPM menelusuri anak lingkup, paket Brief Marketing/Brief Produksi, Design Request, TRR, dokumen, tugas dan aktivitas terkait sampai dependensi tidak langsung.
- Tiap aktivitas diklasifikasikan: **A dibatalkan**, **B perlu assessment/pembatasan aktivitas berisiko**, **C independen dan dapat terus berjalan**.
- Independensi dibuktikan dan dicatat; pembatalan lingkup rangka, misalnya, bisa berdampak pada finishing tetapi tidak otomatis pada signage mandiri.
- Pembatalan satu Scope_ID tidak membatalkan seluruh Production Case atau menghapus dokumen/tugas yang sudah diterbitkan.
- Pekerjaan sudah selesai dan biaya yang sudah timbul tetap dicatat.

## 6. Penghentian menurut kondisi tugas
| Kondisi | Perlakuan |
|---|---|
| AVAILABLE/belum PIC | Tugas pembatalan sah tidak lagi dapat diambil dari pool. |
| Ditugaskan tetapi belum mulai | Instruksi dikirim kepada PIC. |
| IN_PROGRESS | PIC menjalankan penghentian aman dan melaporkan progres serta kendala. |
| SUBMITTED_FOR_REVIEW | Output tetap historis; review lanjutan diputuskan pihak berwenang. |
| COMPLETED | Tidak dibatalkan secara retroaktif. |
| REVISION_REQUIRED | Revisi dapat dihentikan jika kebutuhan dasar dibatalkan secara sah. |
| Sudah ON HOLD karena sebab lain | Alasan ON HOLD tetap tercatat dan tidak otomatis dilepas. |

Untuk Design Request dan TRR, transisi `CANCELLATION_PENDING_ACK` dan `CANCELLED` mengikuti dokumen PROD terkait; lapisan Stop Work Instruction tidak mengganti status itu.

## 7. Konfirmasi dan eskalasi
- PIC aktif menerima instruksi, melakukan Acknowledge, dan memberi laporan progres/output/komitmen terakhir.
- Tidak merespons tidak menangguhkan berlakunya instruksi penghentian yang sah.
- Jika tidak dapat dihubungi, atasan/penanggung jawab departemen memeriksa apakah pekerjaan benar-benar dihentikan dengan bukti dan waktu, melaporkan sisa kewajiban, lalu dapat menyelesaikan kewajiban *verifikasi pengganti* yang diaudit.
- Jangan menyimpan verifikasi atasan sebagai Acknowledge PIC.
- Jika pekerjaan fisik masih berlangsung, perlu tindakan nyata sesuai prosedur keselamatan dan kewenangan lapangan; tidak boleh ditutup berdasarkan status sistem semata.
- Belum ditentukan SLA eskalasi universal; interval pengingat bersifat konfigurasi sesuai kebijakan perusahaan dan tidak menghapus syarat verifikasi.

## 8. Status lapisan integrasi
**Cancellation Case:** `CANCELLATION_REQUESTED`, `IMPACT_ASSESSMENT_PENDING`, `CANCELLATION_APPROVED`, `CANCELLATION_REJECTED`, `CANCELLATION_IN_PROGRESS`, `CANCELLATION_COMPLETED`.

**Stop Work Instruction:** `STOP_REQUESTED`, `STOP_INSTRUCTED`, `STOP_ACK_PENDING`, `STOP_ACKNOWLEDGED`, `STOP_RECONCILIATION_PENDING`, `STOP_COMPLETED`.

Verifikasi pengganti direkam sebagai event khusus, bukan memalsukan status Acknowledge PIC. Status koordinasi ini tidak menimpa status tugas resmi PROD-03/06. Retry/event duplikat mengikuti idempotency INTEGRATION-B.

## 9. Aktivitas paralel, biaya dan komitmen
- Tiga keluaran penilaian pembatalan: A hentikan sesuai keputusan; B review dan batasi aktivitas berisiko; C lanjut setelah dibuktikan independen.
- Cakupan dihitung berdasarkan dependency dan Scope_ID, bukan klasifikasi Minor/Major/Critical yang khusus revisi pada INTEGRATION-C.
- Progres, output, material digunakan, pesanan/material tertunda, biaya muncul, komitmen eksternal, dokumen resmi, dan HPP yang telah disusun wajib diinventarisasi.
- Komitmen eksternal dan penyesuaian biaya dialihkan ke pemilik kewenangan terkait, tidak dibatalkan oleh BPM otomatis.
- HPP terdampak diberi penanda untuk ditindaklanjuti sesuai PROD-07 dan kelak PROD-08.

## 10. Notifikasi dan audit
Notifikasi selektif menurut fungsi: Marketing untuk kebutuhan klien, Asst Manager untuk validasi, Manager Produksi untuk kajian/Brief Produksi/TRR, PIC Desain/TRR yang aktif, Admin Produksi untuk HPP terdampak, atasan PIC untuk eskalasi, dan pemilik komitmen eksternal jika ada.

Audit append-only minimal: `CANCELLATION_REQUESTED`, `CANCELLATION_RISK_SCREENED`, `CANCELLATION_DEPENDENCIES_IDENTIFIED`, `CANCELLATION_IMPACT_ASSESSED`, `CANCELLATION_DECIDED`, `STOP_WORK_INSTRUCTED`, `STOP_WORK_ACKNOWLEDGED`, `STOP_WORK_ESCALATED`, `STOP_WORK_VERIFIED_BY_SUPERVISOR`, `WORK_PROGRESS_RECONCILED`, `UNAFFECTED_WORK_CONFIRMED`, `SCOPE_CANCELLATION_COMPLETED`, `CANCELLATION_CASE_CLOSED`. Setiap event menyimpan aktor, waktu, alasan, versi sumber, Scope_ID, Task_ID dan bukti sesuai jenisnya.

## 11. Syarat penutupan
Cancellation Case dapat `CANCELLATION_COMPLETED` hanya bila keputusan sah; seluruh lingkup dan aktivitas terkait dinilai; penghentian operasional yang disyaratkan terverifikasi (oleh PIC atau supervisor sesuai jalur); progres, output, biaya dan komitmen tercatat; kewajiban eksternal yang belum selesai dialihkan formal; dokumen/histori dipertahankan; serta pekerjaan independen yang dilanjutkan punya dasar assessment. Penutupan sebagian tidak otomatis menutup Production Case utama.

## 12. Uji penerimaan minimum
- Pembatalan sebelum PROD-03 mengikuti kewenangan Marketing.
- Pembatalan DR/TRR aktif memicu instruksi kepada PIC dan prosedur acknowledgement semula.
- PIC tidak merespons: eskalasi dan bukti verifikasi atasan, bukan ACK palsu.
- Pembatalan satu dari beberapa Scope_ID tidak menutup yang independen.
- Hasil Completed dan Brief Produksi ISSUED tidak dihapus secara retroaktif.
- Kewajiban pengadaan eksternal dialihkan; tidak dibatalkan sistem otomatis.
- Penolakan pembatalan tidak menghapus alasan ON HOLD lain.
- Event pembatalan berulang tidak membuat Cancellation Case atau tugas penghentian ganda.
- Penghentian fisik tidak dinyatakan tuntas tanpa bukti aman.

## 13. Kompatibilitas, penguncian dan batas
Mengikuti kewenangan PROD-02/03/05/06/07; distribusi dan ack PROD-04/07 tidak diubah. Identitas dokumen, Production Case, Scope_ID dan revisi mengikuti INTEGRATION-A; Task_ID, handover dan idempotency INTEGRATION-B; dependency analysis dan pelepasan ON HOLD INTEGRATION-C. Spesifikasi tahap sebelumnya dan spreadsheet sumber tidak dimodifikasi. Pengembangan atau perubahan aturan FINAL memerlukan persetujuan pengguna secara eksplisit dan versi baru.
