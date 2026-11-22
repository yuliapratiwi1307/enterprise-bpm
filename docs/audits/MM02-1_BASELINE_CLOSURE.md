# MM-02.1 Baseline Closure

Tanggal penutupan: 9 November 2026

Modul: MM-05 Master Material

## 1. Ringkasan Baseline

MM-02.1 memiliki fondasi master material, kategori, satuan, spesifikasi, varian/SKU, dan nilai spesifikasi yang terhubung. Dokumentasi penutupan ini melengkapi—bukan mengganti—riwayat audit berikut:

- [MM-02.1 Final Audit](./MM02-1_FINAL_AUDIT.md)
- [Baseline Preservation Report](./MM02-1_BASELINE_PRESERVATION.md)
- [MM-02.2 Readiness Audit](./MM02-2_READINESS_AUDIT.md)
- [R1 Parent Lock Remediation](./MM02-1_R1_PARENT_LOCK_REMEDIATION.md)

Preservasi terdahulu mencakup 31 file proyek, dengan checksum manifest valid. Sebelum remediation R1, dua file sumber yang akan diedit juga dibackup dan checksum-nya diverifikasi. Backup eksternal:

- Baseline: `/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-1-preservation-20261109-054334`
- Sebelum edit R1: `/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-1-R1-parent-lock-20261109-083049`

Audit sekarang memverifikasi ulang kedua checksum backup R1 dan manifest preservasi. Repository pada awal pemeriksaan tetap memiliki perubahan sebelumnya: branch `main`, 24 tracked modifications, 454 path untracked yang diekspansi, dan tidak ada staged changes. Semua perubahan tersebut dipertahankan.

## 2. R1 Parent Lock

### Cacat asal

Sebelum R1, `MmVariantService::updateVariant()` mengizinkan `material_id` berubah selama material tujuan ada dan aktif. Bila varian telah memiliki nilai spesifikasi, nilai itu tetap merujuk ke definisi kategori material lama.

### Kebijakan yang disetujui

Project owner menyetujui **Opsi A — Parent Lock**: parent material tidak boleh diganti ketika varian sudah memiliki nilai spesifikasi. Jika tidak ada nilai spesifikasi, re-parent tetap diperbolehkan ke material aktif.

### Implementasi yang diverifikasi

Service memeriksa material tujuan aktif dan, untuk perubahan parent, memeriksa keberadaan relasi `attributes()`. Jika ada, service melempar `ValidationException` pada `material_id` dengan pesan yang disetujui. Perubahan parent yang ditolak tidak menyimpan perubahan SKU/nama pada request yang sama dan tidak mengubah atau menghapus nilai spesifikasi. Update nama/SKU dengan parent tetap tidak diblokir oleh Parent Lock.

### Bukti regression

Feature tests Procurement pada `MmSpecificationTest` saat ini mencakup:

- Menolak re-parent dengan spesifikasi, termasuk memastikan error/pesan pada `material_id`, parent/SKU/nama tetap sama, dan semua kolom serta identitas definisi nilai tetap utuh.
- Mengizinkan re-parent tanpa spesifikasi ke material aktif.
- Menolak material tujuan nonaktif.
- Mengizinkan perubahan nama ketika spesifikasi ada dan parent tetap sama.

Focused suite authorization dan specification dijalankan kembali pada audit ini:

```text
php artisan test --compact tests/Feature/MmSpecificationTest.php tests/Feature/MmAuthorizationTest.php
18 tests passed, 222 assertions
```

PHP syntax pada service dan test juga diperiksa ulang. Suite menggunakan `DatabaseTransactions`.

## 3. Penerimaan MM-02.1

| Area | Bukti dan klasifikasi |
|---|---|
| Material master CRUD | Automated HTTP tests menguji create/update/deactivate Procurement; route dan controller diperiksa. |
| Kategori dan satuan | Automated tests mencakup create/update/deactivate, kode duplikat, dimensi, dan referensi nonaktif. |
| Definisi atribut | Automated tests mencakup create/update/deactivate, kode duplikat dalam kategori, tipe, dan aturan satuan. |
| Varian dan SKU | Automated tests mencakup create/update/deactivate varian, SKU duplikat, material/unit, dan role. Source menerapkan validasi uniqueness saat update; test khusus untuk mengganti SKU ke nilai baru belum terpisah. |
| Persistensi nilai spesifikasi | Automated tests mencakup typed values, validasi kategori/required/duplicate, penyimpanan dan render ulang yang tercatat dalam audit sebelumnya. |
| Parent Lock | Current focused regression membuktikan penolakan tanpa perubahan data dan jalur non-parent yang tetap diizinkan. |
| Procurement authorization | Current focused tests mencakup mutasi HTTP Procurement. |
| Batas role read-only | Current authorization regression mencakup ADMIN_BPM, ADMIN_PRODUKSI, dan ASST_MANAGER_PRODUKSI; mutasi langsung ditolak. Marketing/Designer dan guest ditolak pada endpoint yang diuji. |
| Route | Route list terkini menunjukkan 22 route MM-05 di bawah middleware `web` dan `auth`. |
| Duplicate/inactive reference validation | Automated tests dan source inspection menunjukkan validasi kode/SKU, referensi aktif, dan relasi kategori pada operasi yang diuji. |

### Jenis bukti dan batas klaim

- **Automated:** dua feature test file MM saat ini lulus 18 tests/222 assertions; database guard berhasil dilewati.
- **Source inspection:** controller/service, authorization, validasi, migration/foreign keys/unique constraints, dan Parent Lock diperiksa. Ini bukan pengganti tes concurrency.
- **Manual browser:** riwayat sebelumnya melaporkan alur Procurement untuk satuan, material, definisi atribut, varian, penyimpanan nilai spesifikasi, dan persistensi setelah refresh. Tidak diulang pada penutupan ini; dicatat sebagai laporan manual terdahulu, bukan verifikasi browser baru.
- **Belum terverifikasi:** full application suite, browser visual/E2E independen pada audit ini, dan konkurensi simultan.

Test environment terkini diperiksa tanpa membaca isi `.env.testing`: file reguler mode `600`; config cache tidak ada. PHPUnit memaksa PostgreSQL `enterprise_bpm_test`, dan `Tests\TestCase` memeriksa identitas serta hak database sebelum setup test. Tidak ada migration dijalankan; database browser dan produksi tidak digunakan.

## 4. Temuan Konkurensi dan Batasan

Parent Lock melindungi request berurutan, tetapi belum menjadi jaminan atomik terhadap dua request simultan. Lihat analisis terperinci pada [MM-02.2 Readiness Reassessment](./MM02-2_READINESS_REASSESSMENT.md). Foreign key dan unique constraint yang ada tidak menegakkan invariant lintas tabel bahwa definisi spesifikasi harus cocok dengan kategori material parent.

Keterbatasan lain yang tetap tercatat:

- Full test suite dan browser E2E tidak dijalankan ulang.
- Successful edit SKU ke kode baru belum memiliki regression assertion tersendiri, meskipun aturan unik pada update diterapkan oleh service dan duplikasi diuji.
- Audit/provenance memakai kolom actor dan timestamp; tidak ada bukti event log append-only untuk histori perubahan.
- Supplier/quote/harga mempunyai fondasi model/schema/service tetapi belum memiliki CRUD UI/controller/route terverifikasi.
- Global `git diff --check` sebelumnya menemukan trailing whitespace lama di view PROD-03; file itu di luar scope dan tidak diubah.

## 5. Keputusan Milestone

**MM-02.1 CLOSED — PASS WITH FINDINGS**

Kriteria fungsional/authorization yang diuji dan remediation R1 lulus. Status ini tidak menyatakan full application regression atau browser E2E lulus. Residual concurrency risk R1 dinilai **MEDIUM / OPEN**, bukan dianggap diselesaikan atau diterima secara implisit. Penutupan milestone tidak dengan sendirinya mengizinkan implementasi MM-02.2.

## Addendum: Concurrency Remediation dan Batas Baseline

Tanggal addendum: 9 November 2026

Perubahan lanjutan setelah penutupan awal menangani interleaving antara perubahan parent varian dan penulisan histori yang disetujui. Perubahan ini tidak menghapus catatan atau hasil audit historis di atas; bukti dan batas yang lebih baru dicatat pada:

- [MM-02.2 Concurrency Remediation](./MM02-2_CONCURRENCY_REMEDIATION.md)
- [Spesifikasi MM-02.2 Master Material Core](../specifications/MM02-2_MASTER_MATERIAL_CORE_SPEC.md)
- [MM-02.2 Final Readiness Gate](./MM02-2_FINAL_READINESS_GATE.md)

`MmVariantService` kini mengunci row varian dalam transaksi untuk re-parent dan penulisan nilai spesifikasi. Re-parent ditolak jika varian memiliki nilai spesifikasi, histori supplier quote, atau histori material price. Jalur record quote dan record price mengambil lock row varian yang sama. Tes PostgreSQL memakai proses dan koneksi independen membuktikan serialisasi dua interleaving spec-write/re-parent yang diuji.

Verifikasi gabungan terkini:

```text
php artisan test --compact tests/Feature/MmSpecificationTest.php tests/Feature/MmAuthorizationTest.php tests/Feature/MmVariantConcurrencyTest.php
25 tests passed, 257 assertions
```

Database guard mengarahkan tes ke `enterprise_bpm_test`; tidak ada migration dijalankan dan database browser/production tidak digunakan. Syntax, scoped Pint, dan pemeriksaan trailing whitespace pada lima file PHP yang berubah lulus. Full suite serta browser E2E tidak dijalankan.

### Status akhir dan batas klaim

Status MM-02.1 tetap **CLOSED — PASS WITH FINDINGS**. R1 concurrency race pada write paths parent/specification/quote/price yang dicakup dinyatakan tertangani berdasarkan tes di atas; ini bukan klaim bahwa seluruh invariant lintas tabel race-free. Perubahan kategori material dan perubahan kategori definisi atribut masih memakai guard pemeriksaan yang tidak diserialisasi dengan row-lock variant write paths. Risiko itu belum diperbaiki maupun diuji secara concurrency dan tetap dicatat sebagai temuan readiness.

Backup sebelum perubahan concurrency, termasuk lima file awal dan manifest SHA-256, diverifikasi di:

`/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-2-concurrency-20261109-092610`

Repository tetap memiliki perubahan sebelumnya. Tidak ada commit/push, migration, perubahan role, route, schema, HPP, atau modul lain yang dilakukan untuk remediation ini. MM-02.2 belum diimplementasikan; lihat readiness gate untuk keputusan saat ini.
