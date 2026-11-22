# MM-02.1 R1 Parent Lock Remediation

Tanggal: 9 November 2026

## 1. Bug dan Akar Penyebab

`MmVariantService::updateVariant()` sebelumnya memvalidasi keberadaan material tujuan dan status aktifnya, tetapi tidak memeriksa nilai spesifikasi yang sudah tersimpan sebelum mengganti `material_id`. Akibatnya, varian dapat dipindahkan ke material kategori lain sementara nilai lama tetap terhubung ke definisi atribut kategori asal.

## 2. Implementasi Parent Lock

Pada perubahan `material_id` yang benar-benar berbeda, service kini:

1. Memastikan material tujuan aktif dengan guard yang sudah ada.
2. Memeriksa `$variant->attributes()->exists()`.
3. Jika ada nilai spesifikasi, melempar `ValidationException` pada field `material_id` dengan pesan: **“Material induk tidak dapat diubah karena varian sudah memiliki nilai spesifikasi.”**

Update SKU dan nama tanpa perubahan parent tetap menggunakan validasi lama. Parent dapat dipindahkan bila belum ada nilai spesifikasi. Nilai spesifikasi tidak dihapus atau diubah oleh guard ini. Matriks role, route, migration, supplier, quote, harga, dan HPP tidak diubah.

## 3. File yang Diubah

- `app/Services/MmVariantService.php` — menambahkan validasi Parent Lock di `updateVariant()`.
- `tests/Feature/MmSpecificationTest.php` — menambahkan regression tests melalui HTTP Procurement untuk re-parent, tujuan nonaktif, dan update nama.
- `docs/audits/MM02-1_R1_PARENT_LOCK_REMEDIATION.md` — laporan hasil remediation.

Sebelum edit, kedua file PHP disalin byte-identik ke backup eksternal:

`/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-1-R1-parent-lock-20261109-083049`

Checksum dan salinan awal diverifikasi. Repository sudah memiliki banyak perubahan lain; semuanya dipertahankan. Tidak ada commit atau push.

## 4. Hasil Regression Tests

| Skenario | Hasil |
|---|---|
| Procurement mencoba mengganti parent saat spesifikasi tersimpan | PASS — error `material_id` dan pesan persis sesuai; parent, SKU, nama, jumlah serta seluruh kolom nilai spesifikasi tetap sama |
| Procurement mengganti parent tanpa nilai spesifikasi ke material aktif | PASS — relasi parent berubah, SKU dan nama tetap |
| Procurement mengganti parent ke material nonaktif | PASS — request mendapat validation error `material_id`, parent tidak berubah |
| Procurement mengubah nama tanpa mengganti parent walau spesifikasi tersimpan | PASS — nama berubah, parent/SKU/nilai spesifikasi tetap |
| ADMIN_BPM dan role read-only mencoba mutasi langsung | PASS — dicakup oleh authorization regression test yang ada; request ditolak |

Perintah focused:

```text
php artisan test --compact tests/Feature/MmSpecificationTest.php tests/Feature/MmAuthorizationTest.php
```

Hasil setelah formatting: **18 tests passed, 222 assertions**. Tidak ada focused test yang gagal.

## 5. Pemeriksaan Kualitas

| Pemeriksaan | Hasil |
|---|---|
| PHP syntax `MmVariantService.php` | PASS |
| PHP syntax `MmSpecificationTest.php` | PASS |
| Pint terbatas pada dua file PHP yang berubah | PASS |
| Pint check pada dua file PHP yang berubah | PASS |
| `git diff --check`/whitespace pada dua file PHP yang berubah | PASS |

Tidak ada global formatter atau full test suite yang dijalankan.

## 6. Database Safety

- Automated tests diarahkan hanya ke database test PostgreSQL `enterprise_bpm_test` dan role `enterprise_bpm_test_user`.
- `tests/bootstrap.php` mensyaratkan environment dan target tersebut; `Tests\TestCase` memeriksa konfigurasi efektif, identitas koneksi PostgreSQL, serta pembatasan role sebelum test berjalan. Guard berhasil dilewati oleh kedua focused suites.
- `.env.testing` hanya diperiksa metadata-nya: file reguler, mode `600`; isinya tidak dibaca. Config cache tidak ditemukan.
- Kedua feature test suite menggunakan `DatabaseTransactions`.
- Tidak ada migration atau perintah database destruktif yang dijalankan. `enterprise_bpm_browser` dan `enterprise_bpm` tidak digunakan atau dimutasi oleh pengujian.

## 7. Risiko Tersisa

- Guard memeriksa nilai spesifikasi yang sudah ada saat request update diproses. Tidak ada pengujian konkurensi untuk dua request simultan yang menambah nilai spesifikasi dan mengganti parent; jika sistem membutuhkan jaminan race-free untuk kondisi tersebut, perlu rancangan locking/transaksi dan regression test terpisah.
- Full suite dan browser E2E tidak dijalankan; hasil laporan ini terbatas pada focused MM authorization/specification tests.

## 8. Keputusan

**PASS — R1 Parent Lock**

Seluruh acceptance criteria R1 yang diminta lulus: perpindahan parent dengan nilai spesifikasi ditolak tanpa perubahan data; perpindahan tanpa nilai ke material aktif tetap berjalan; tujuan nonaktif tetap ditolak; perubahan nama non-parent tetap berhasil; dan tes authorization tetap lulus.

Pekerjaan MM-02.2 tidak dimulai. Menunggu persetujuan terpisah sebelum milestone tersebut dibuka.
