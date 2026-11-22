# BPM Production — INTEGRATION-C
## Pengendalian Revisi dan Propagasi Dampak Lintas Tahapan

**Status:** FINAL / LOCKED v1.0  
**Tanggal pengesahan:** 13 Oktober 2026  
**Cakupan:** PROD-02 sampai PROD-07; integrasi dampak awal ke PROD-08 (belum ditetapkan)  
**Prinsip non-perubahan:** Dokumen Excel sumber, PROD-02 s.d. PROD-07, INTEGRATION-A dan INTEGRATION-B tidak diubah. Ketentuan spesifik dokumen-dokumen terkunci tersebut tetap mengikat.

## 1. Tujuan
Mendeteksi dan menangani dampak revisi dokumen sumber atas dokumen, lingkup (Scope_ID), tugas, Technical Reference Request (TRR), Design Request, handover, dan referensi ke HPP berdasarkan ketergantungan nyata, tanpa menghentikan pekerjaan independen.

## 2. Prinsip yang disahkan
- **Dependency-Based Impact Propagation:** penelusuran menyeluruh sepanjang rantai relasi yang relevan, tidak otomatis menandai semua objek dalam Production Case.
- Satu Change Event dapat memiliki hasil assessment berbeda untuk setiap objek: NO_IMPACT, MINOR, MAJOR, CRITICAL, atau UNDETERMINED (sementara).
- Sistem merekomendasikan klasifikasi; keputusan substantif ditetapkan otoritas tahap yang relevan.
- Perubahan Critical menerapkan ON HOLD hanya terhadap aktivitas terdampak, dependensinya, dan aktivitas berpotensi terdampak yang belum dapat dipastikan aman.
- Kegiatan yang tidak terdampak dapat tetap berlangsung; Task_ID, versi historis, dan audit dipertahankan.
- Snapshot resmi tidak ditimpa dan referensi sumber tidak diperbarui diam-diam.
- Pembatasan informasi Provisional dan Level 3 dari PROD-05 tidak berubah.

## 3. Alur pengendalian
1. Perubahan sumber atau perubahan Draft yang telah digunakan proses turunan terdeteksi.
2. BPM mencatat Change Event, versi lama/baru, data berubah, sumber, alasan, dan aktor.
3. BPM menelusuri dependensi dokumen, Scope_ID, Task_ID, dan ketergantungan data atau aktivitas hingga seluruh objek relevan teridentifikasi.
4. BPM membuat rekomendasi Impact Assessment per objek, bukan satu klasifikasi untuk satu Case.
5. Otoritas terkait menilai dan mengonfirmasi dampak serta langkah tindak lanjut.
6. Minor: audit/pemeriksaan; Major: review/penyesuaian; Critical: ON HOLD terbatas dan revalidasi; UNDETERMINED: pemeriksaan lanjutan dengan penahanan aktivitas berisiko yang relevan.
7. Revalidasi, perubahan dokumen, penyelesaian tindak lanjut, dan pelepasan hold yang berwenang dicatat.
8. Change Event menjadi RESOLVED setelah kewajiban perubahannya selesai; ini bukan status selesai Production Case.

## 4. Struktur ChangeEvent
Field minimum: `Change_Event_ID`, `Production_Case_ID`, `Source_Document_ID`, `Old_Version_ID`, `New_Version_ID` (atau Draft Version ID), `Changed_Fields`, `Change_Reason`, `Affected_Scope_IDs`, `Initiated_By`, `Change_Timestamp`, `Assessment_Status`.

Draft internal yang belum dipakai pihak lain dicatat dalam histori kerja. Draft yang sudah menjadi dasar tugas lain memerlukan pemeriksaan relevansi. Revisi resmi menghasilkan Change Event yang unik; retry tidak menggandakan event. Perubahan yang berkaitan boleh dikelompokkan untuk assessment tetapi detail setiap perubahan tetap tersimpan. Change Event bukan persetujuan revisi.

## 5. Dependency Analysis dan ImpactAssessment
Penelusuran mencakup (a) relasi dokumen/versi, (b) pohon lingkup Scope_ID, dan (c) data serta aktivitas yang bergantung pada data sumber. Tiap objek terkait memiliki penilaian dan dasar keputusan tersendiri.

| Hasil | Makna | Tindakan |
|---|---|---|
| NO_IMPACT | Tidak terdapat dampak material, terbukti dengan alasan | Boleh lanjut; alasan tercatat |
| MINOR | Perubahan administratif/tidak material | Audit dan pemeriksaan sederhana |
| MAJOR | Perubahan memerlukan pembaruan isi, kalkulasi, atau aktivitas | Review dan penyesuaian bagian terdampak |
| CRITICAL | Dasar kelayakan, keselamatan, atau data kritis tidak memadai | ON HOLD aktivitas terdampak dan dependensinya |
| UNDETERMINED | Bukti belum cukup untuk klasifikasi | Assessment lanjutan; bagian berpotensi Critical ditahan sementara |

UNDETERMINED bukan klasifikasi final yang menggantikan Minor/Major/Critical pada PROD-05. Objek yang telah dinyatakan NO_IMPACT dapat tetap bekerja, meskipun objek lain pada Case yang sama CRITICAL.

## 6. Kewenangan
- Marketing menangani informasi dan perubahan permintaan klien serta mengelola Design Request menurut PROD-02/03.
- Asst Manager Produksi menetapkan klasifikasi dan revalidasi perubahan Brief Marketing menurut PROD-05.
- Manager Produksi menetapkan konsekuensi teknis/revisi Brief Produksi, penggunaan TRR, dan tindakan sesuai PROD-06/07.
- Admin Produksi menindaklanjuti indikasi dampak HPP; otorisasi final dan prosedur HPP akan ditetapkan di PROD-08.
- Otorisasi pelepasan ON HOLD mengikuti sumber penangguhan dan aturan tahap terkait. Integrasi tidak memindahkan kewenangan antar-departemen.

## 7. ON HOLD dan pelepasan
Penahanan hanya pada aktivitas langsung terdampak, tugas dependennya, atau tugas yang belum terbukti aman dari kemungkinan dampak Critical. Sistem menjaga beberapa alasan hold secara independen. Satu alasan selesai tidak melepaskan hold bila alasan lainnya masih aktif.

Pelepasan hanya jika: informasi/permasalahan telah ditangani; assessment dan revalidasi relevan selesai; versi sumber yang sah ditetapkan; tidak ada dependensi kritis aktif; serta otoritas relevan menyetujui dan tercatat. Setelah lepas, tugas yang relevan dilanjutkan menggunakan Task_ID yang sama. Tugas baru hanya untuk pekerjaan yang secara sah memang berbeda dan memiliki alasan tertulis.

## 8. Notifikasi
Notifikasi dikirim berdasarkan objek dan tanggung jawab, bukan broadcast seluruh Production Case:
- BM perlu revalidasi: Marketing + Asst Manager;
- kajian teknis/BP terpengaruh: Manager Produksi;
- DR: Marketing + PIC Desain terkait;
- TRR: Manager Produksi + PIC TRR terkait;
- dampak HPP: Admin Produksi + Manager Produksi;
- ON HOLD/pelepasan: PIC aktivitas dan pejabat relevan;
- assessment tertunda: pengambil keputusan yang ditetapkan.

Pesan memuat dokumen sumber, versi lama/baru, Scope_ID terdampak, tindakan, dan Change Event ID. Notifikasi tidak menggantikan Acknowledge revisi baru sesuai PROD-04/07.

## 9. Aktivitas paralel
Tugas yang tidak terdampak tetap berlangsung. Tugas yang sebagian terdampak ditinjau bagian relevannya dan bagian berisiko ditahan jika perlu. Hasil DR/TRR yang diserahkan setelah perubahan sumber dipertahankan sebagai histori dan diperiksa relevansinya sebelum digunakan terhadap versi terbaru. Pembatalan/penggantian tugas mengikuti otoritas tahap; tidak terjadi secara otomatis hanya karena revisi.

## 10. Status integrasi
Status Change Event: `CHANGE_DETECTED`, `IMPACT_ASSESSMENT_PENDING`, `IMPACT_ASSESSED`, `REVALIDATION_REQUIRED`, `ACTION_IN_PROGRESS`, `RESOLVED`. Hasil ImpactAssessment per objek: `NO_IMPACT`, `MINOR`, `MAJOR`, `CRITICAL`, `UNDETERMINED`. ON HOLD adalah kontrol aktivitas tersendiri, bukan status Change Event atau approval dokumen.

## 11. Audit trail minimum
`CHANGE_EVENT_CREATED`, `DEPENDENCY_DISCOVERED`, `IMPACT_RECOMMENDED`, `IMPACT_CONFIRMED`, `REVALIDATION_REQUESTED`, `ACTIVITY_ON_HOLD`, `HOLD_RELEASE_REQUESTED`, `HOLD_RELEASE_APPROVED`, `TASK_REACTIVATED`, `CHANGE_EVENT_RESOLVED`.

Simpan aktor, waktu, relasi versi dan Scope_ID, objek terdampak, penjelasan dampak, otoritas, bukti, serta dasar keputusan. Audit tidak boleh dihapus/ditimpa via fitur operasional; koreksi menggunakan event baru.

## 12. Kriteria penerimaan / skenario uji
1. BM revisi Minor tidak menghentikan tugas independen.
2. Perubahan material utama menemukan kajian teknis dan perhitungan terkait.
3. Revisi Critical menahan hanya aktivitas/dependensi terdampak.
4. Dua BP turunan dari BM sama dapat memiliki hasil assessment berbeda.
5. TRR selesai setelah revisi sumber: hasil disimpan tetapi relevansinya wajib ditinjau.
6. HPP yang merujuk BP direvisi ditandai REVIEW_REQUIRED sejauh terdampak, tanpa memutuskan approval HPP.
7. Tugas ON HOLD menolak aksi operasional terlarang.
8. Beberapa alasan hold dicek semua sebelum pelepasan.
9. Revalidasi dan persetujuan pelepasan yang lengkap mengizinkan tugas aktif dilanjutkan dengan identitas stabil.
10. Retry Change Event tidak menggandakan assessment/tugas.

## 13. Keselarasan dan batasan
Dokumen ini adalah lapisan integrasi tambahan yang merujuk PROD-02 sampai PROD-07 serta INTEGRATION-A/B FINAL v1.0 tanpa mengeditnya. Prosedur pembatalan lintas tahap dikembangkan dalam INTEGRATION-D; otorisasi final HPP dalam PROD-08. Apabila timbul konflik implementasi, keputusan yang sudah FINAL pada tahapan terkait tidak diganti tanpa persetujuan revisi eksplisit.

**Pengesahan:** Pengguna menyetujui seluruh Draft Final INTEGRATION-C Review v1.0 dan memerintahkan penguncian sebagai FINAL / LOCKED v1.0 pada 13 Oktober 2026.
