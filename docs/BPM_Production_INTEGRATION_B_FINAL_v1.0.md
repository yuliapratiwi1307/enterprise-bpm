# BPM Production — INTEGRATION-B FINAL / LOCKED v1.0

**Judul:** Status, Transisi, Event-Based Handover, Pengendalian Tugas dan Audit Trail Antartahap PROD-02 s.d. PROD-07  
**Ditetapkan:** 13 Oktober 2026  
**Status:** FINAL / LOCKED v1.0 — disetujui pengguna  
**Posisi:** Spesifikasi integrasi tambahan; tidak mengubah spesifikasi FINAL PROD-02 s.d. PROD-07 maupun INTEGRATION-A.  
**Sumber proses:** Sheet `24082026 (PROD)` dalam `Flowchart SMS 2024 24082026.xlsx` (tidak diubah).

## 1. Tujuan dan batasan
Menjaga perpindahan pekerjaan yang sah dan konsisten di PROD-02–07, menghindari tugas dan penerbitan duplikat, mempertahankan jejak versi, menangani return/clarification/retry secara berbeda, serta menegakkan kontrol Provisional, revisi dan ON HOLD yang berlaku. Ketentuan kewenangan di masing-masing PROD dan relasi dari INTEGRATION-A tetap menjadi sumber kebenaran.

## 2. Prinsip disetujui
1. **Event-Based Handover dengan readiness gate:** handover/tugas lanjutan diaktifkan oleh peristiwa bisnis yang sah, bukan menunggu penutupan seluruh tugas nonkritis.
2. **Version aware:** handover dan tugas menyimpan Case_ID, Scope_ID dan Source_Version_ID.
3. **Idempotent:** event/submit/retry yang sama tidak menciptakan dokumen, handover atau tugas ganda.
4. **Impact based:** Task_ID dan histori dipertahankan pada revisi; Minor diperiksa, Major direview, Critical menerapkan ON HOLD hanya pada aktivitas terdampak dan dependensinya. Aktivitas yang belum dipastikan bebas dari risiko Critical ditahan sementara hingga dinilai; aktivitas independen tetap berjalan.
5. **Acknowledge bukan approval atau izin eksekusi:** sesuai aturan masing-masing PROD, termasuk kontrol aktivitas Level 3.
6. **Immutable audit:** catatan yang telah ada tidak ditimpa atau dihapus oleh operasional; koreksi adalah event baru.

## 3. Transisi dan event trigger
| Transisi | Event integrasi | Persyaratan dan keluaran |
|---|---|---|
| PROD-02 → PROD-03 | `CLIENT_REQUEST_RECORDED` | Client Request berstatus RECORDED; aktifkan pekerjaan Brief Marketing untuk Marketing. |
| PROD-03 → PROD-04 | `BRIEF_MARKETING_SUBMIT_REQUESTED` | Hanya bila readiness PROD-03 terpenuhi; menjalankan transaksi Submit & Distribute PROD-04, **bukan** auto-submit karena sekadar READY. |
| PROD-04 → PROD-05 | `BRIEF_MARKETING_DELIVERED_TO_ASST_MANAGER` | Snapshot resmi berhasil tersedia bagi Asst Manager; tugas validasi aktif meski belum Acknowledge manual. |
| PROD-05 → PROD-06 | `VALIDATION_PERMITS_TECHNICAL_REVIEW` | Hasil validasi dan kewenangan Manager Produksi mengizinkan kajian, termasuk batasan Provisional. Tidak meniadakan final data gate Level 3. |
| PROD-06 → PROD-07 | `PRODUCTION_BRIEF_READY` | Draft READY_FOR_ISSUANCE atau READY_WITH_NOTES; menyiapkan tugas Issue, **bukan** menerbitkan otomatis. |
| PROD-07 → PROD-08 | `PRODUCTION_BRIEF_DELIVERED_TO_ADMIN` | Brief resmi tersedia pada Admin Produksi dan batas pemanfaatannya dipenuhi; detail HPP tetap kewenangan PROD-08 mendatang. |

## 4. Objek dan identitas teknis
- `DocumentVersion`: snapshot/versi dokumen resmi atau draft, sesuai INTEGRATION-A.
- `WorkflowTask`: `Task_ID`, `Production_Case_ID`, `Scope_ID`, `Process_Code`, `Task_Type`, `Source_Version_ID`, `Recipient_Role`, `Assignee_ID`, `Task_Status`, `Idempotency_Key`, pembatasan.
- `HandoverRecord`: `Handover_ID`, `Event_ID`, versi sumber, tujuan/kewajiban, status dan waktu.
- `ActivityControl`: kontrol REVIEW_REQUIRED, ON_HOLD, dan pembatasan terkait tanpa menyamakan status dokumen.
- Rekam hubungan `Production_Case_ID → Document_Version_ID → Event_ID → Handover_ID → Task_ID`.
- Perubahan/pelepasan PIC tidak mengganti Task_ID. Kewajiban dua penerima pada PROD-07 memiliki record terpisah pada satu peristiwa Issue.

## 5. Idempotency dan distribusi
- Prinsip identifikasi kewajiban: `Production_Case_ID + Scope_ID + Process_Code + Task_Type + Source_Version_ID + Recipient_Identity + Obligation_Type` beserta ID peristiwa/siklus bila tugas berulang sah; implementasi wajib menghindari collision dan race condition.
- Submit ganda dalam transaksi yang sama mengembalikan hasil transaksi sah pertama. Event berulang, retry, dan notifikasi ulang menggunakan objek lama.
- Kegagalan distribusi satu penerima tidak membuat ulang tugas penerima yang berhasil. Retry hanya untuk penerima gagal.
- Revisi resmi baru menghasilkan kewajiban Acknowledge/distribusi baru per penerima dan versi, tanpa pengulangan otomatis seluruh pekerjaan substansial.
- Tugas Design Request yang dilepas kembali ke task pool menggunakan Task_ID sama. Pengalihan PIC TRR mempertahankan identitas TRR/tugas.
- Proses snapshot/nomor/event/tugas harus memiliki batas transaksi yang konsisten.

## 6. Perubahan sumber saat tugas aktif
1. BPM menelusuri tugas berdasarkan Source_Version_ID/Scope_ID dan merekomendasikan penilaian dampak.
2. Kewenangan klasifikasi dan keputusan mengikuti tahap asal dan tahap terdampak (PROD-05/06/07), tidak diambil alih INTEGRATION-B.
3. **Tidak terdampak:** tugas berjalan, Task_ID tidak berubah. **Minor:** catat dan periksa seperlunya. **Major:** review/penyesuaian komponen terdampak. **Critical:** ON HOLD terbatas pada aktivitas terdampak dan dependensinya. Ketidakpastian relevan ditahan hingga dinilai.
4. Hasil yang sudah dikirim dan referensi versi lama tetap disimpan. Tugas baru hanya dibuat bila merupakan pekerjaan baru yang nyata dengan identitas dan alasan berbeda.
5. ON HOLD dilepas hanya setelah prasyarat dan keputusan berwenang dipenuhi; data Provisional bukan izin pelaksanaan Level 3 atau pembelian irreversible.

## 7. Tiga jalur pengembalian / pengiriman ulang
| Jalur | Perilaku |
|---|---|
| `RETURN_FOR_REVISION` | Mencatat pengembali, alasan, bagian, versi dan tindak lanjut; perubahan pasca-Submit/Issue tunduk pada revisi PROD terkait. Revisi resmi baru memicu handover berbasis versi baru, histori lama tetap tersedia. |
| `CLARIFICATION_REQUEST` | Permintaan penjelasan yang tidak otomatis mencabut status, membuat revisi, menutup tugas atau menghentikan proses; jika mengubah substansi, ikuti mekanisme revisi resmi. |
| `DELIVERY_RETRY` | Mengulang pengiriman versi yang sama dan kewajiban yang sama tanpa membuat revisi/tugas baru; SLA penerima dimulai setelah dokumen benar-benar tersedia sesuai PROD-04/07. |

## 8. Status handover integrasi
- `PENDING_TRIGGER`: menunggu event sah.
- `TRIGGERED`: event terjadi, gate sedang diverifikasi.
- `ACTIVATED`: tugas/kewajiban tersedia.
- `DELIVERY_EXCEPTION`: permasalahan per penerima, tidak menyamakan status seluruh dokumen.
- `CLOSED`: kewajiban handover diselesaikan sesuai jenisnya.
- `SUPERSEDED` **sebagai penanda versi handover**, tidak otomatis menutup semua tugas aktif. Harus ada assessment dampak.

Lapisan dashboard terpisah: status Case, dokumen, handover, task, Acknowledge, ActivityControl dan outstanding. Jangan menganggap `ACK_OVERDUE` sebagai tidak sahnya Issue, atau `ISSUED_WITH_NOTES` sebagai izin produksi fisik.

## 9. SLA yang diwarisi
- PROD-04: Asst Manager Acknowledge Brief Marketing ≤4 jam kerja, pengingat jam ke-3; tidak memblokir validasi, auto-ack saat mulai validasi; Admin hanya copy.
- PROD-07: Asst Manager dan Admin masing-masing Acknowledge Brief Produksi ≤4 jam kerja, pengingat jam ke-3; auto-ack saat memulai aktivitas terkait, overdue dipantau per penerima; tidak memblokir pekerjaan yang sah.
- PROD-06: deadline TRR ditentukan Manager Produksi per permintaan.
- PROD-03: deadline Design Request mengikuti kewenangan Marketing yang sudah dikunci.
- Tidak ada SLA baru lintas proses yang mengganti ketentuan di atas.

## 10. Event audit lintas proses
`HANDOVER_TRIGGERED`, `HANDOVER_ACTIVATED`, `TASK_ACTIVATED`, `DUPLICATE_EVENT_IGNORED`, `DOCUMENT_RETURNED`, `CLARIFICATION_REQUESTED`, `DELIVERY_RETRIED`, `TASK_IMPACT_ASSESSED`, `TASK_ON_HOLD`, `TASK_REACTIVATED`, `HANDOVER_CLOSED`. Masing-masing menyimpan Event_ID, Case/Scope, versi, aktor, timestamp, objek terdampak, keputusan dan alasan yang relevan. Rekaman tidak dapat diedit/dihapus via operasional.

## 11. Kriteria uji integrasi
- Double Submit menghasilkan satu snapshot dan satu set tugas yang benar.
- Retry distribusi hanya memperbaiki penerima gagal; validasi tidak terduplikasi.
- Mulai validasi/HPP memicu Acknowledge otomatis untuk penerima dan revisi yang benar.
- Pergantian PIC/self-release mempertahankan Task_ID.
- Revisi Minor tidak memblokir tugas independen, Major mereview komponen, Critical ON HOLD hanya aktivitas terdampak/dependensi.
- TRR/Design Request nonkritis tetap dimonitor tanpa menghalangi handover yang telah sah.
- Event terlambat dari versi lama diverifikasi, tidak diartikan sebagai kewajiban revisi baru.
- Revisi Brief Produksi berdampak pada HPP sesuai PROD-07, tanpa menciptakan aturan finalisasi HPP baru di PROD-08.

## 12. Hubungan dengan spesifikasi terkunci
PROD-02 RECORDED; PROD-03 Design Request Self-Assignment; PROD-04 Submit & Distribute, snapshot dan ACK; PROD-05 Provisional, impact based revalidation dan limited ON HOLD; PROD-06 TRR dan kesiapan Draft; PROD-07 Issue, dua ACK, distribusi dan dampak HPP; INTEGRATION-A ID, penomoran, Production Case, Scope_ID dan immutable version links. Semua tidak diubah oleh spesifikasi ini.

## 13. Pengesahan dan kontrol perubahan
Pengguna menyetujui seluruh **Draft Final INTEGRATION-B Review v1.0** pada 13 Oktober 2026 untuk dikunci sebagai **FINAL / LOCKED v1.0** dan disimpan terpisah. Perubahan terhadap isi dokumen ini atau spesifikasi PROD-02–07 dan INTEGRATION-A membutuhkan pembahasan dan persetujuan eksplisit baru. Langkah selanjutnya: INTEGRATION-C (revisi dan propagasi dampak lintas tahapan), masih DRAFT.
