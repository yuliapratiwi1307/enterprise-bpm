# MM-02.2 Concurrency Remediation

Tanggal: 9 November 2026

Modul: MM-05 Master Material

## 1. Ringkasan

Remediasi ini menutup race pada write paths yang dapat mengubah parent varian atau menambahkan histori pada varian yang sama. Semua operasi terkait sekarang mengambil row lock pada record `mm_material_variants` dalam transaksi. Re-parent ditolak jika varian memiliki nilai spesifikasi, supplier quote, atau material price.

Dua tes PostgreSQL dengan proses dan koneksi independen membuktikan serialisasi interleaving antara spec-write dan re-parent. Hasil focused tests saat ini: **25 tests passed, 257 assertions**. Keputusan ini tidak menyatakan seluruh jalur perubahan kategori MM race-safe; temuan terpisah dicatat pada bagian 7.

## 2. Cacat dan Keputusan yang Disetujui

Parent Lock sequential sebelumnya memeriksa keberadaan relasi lalu mengubah `material_id`, sementara request lain dapat menyimpan nilai spesifikasi secara simultan. Transaction di masing-masing request tanpa lock bersama tidak cukup untuk menyerialisasi keputusan tersebut.

Keputusan owner yang diterapkan:

- Gunakan mitigasi concurrency, bukan menganggap pemeriksaan sequential cukup.
- Jangan izinkan re-parent jika terdapat nilai spesifikasi, histori supplier quote, atau histori material price.
- Cakupan MM-02.2 adalah fungsi inti Master Material.
- Supplier/quote/pricing CRUD dan integrasi HPP tetap di luar scope.

## 3. Implementasi

| File | Perubahan |
|---|---|
| `app/Services/MmVariantService.php` | `updateVariant()` menjalankan transaksi, mengambil ulang row varian dengan `lockForUpdate()`, memvalidasi payload terhadap row terkini, lalu memeriksa atribut, quote, dan price sebelum perubahan parent. `setAttributes()` mengambil lock yang sama sebelum membaca parent/kategori dan menyimpan nilai. |
| `app/Services/MmSupplierQuoteService.php` | `recordQuote()` mengunci row varian di dalam transaksi sebelum validasi status aktif dan pembuatan histori quote. |
| `app/Services/MmMaterialPriceService.php` | `recordPrice()` mengunci row varian di dalam transaksi sebelum validasi status aktif, kecocokan quote, dan pembuatan histori harga. |
| `tests/Feature/MmSpecificationTest.php` | Regression coverage untuk penolakan re-parent atas setiap jenis histori, gabungan histori, serta update non-parent yang diizinkan. |
| `tests/Feature/MmVariantConcurrencyTest.php` | Dua tes PostgreSQL yang menggunakan proses worker PHP terpisah dan koneksi baru. |

Perubahan parent yang ditolak melempar `ValidationException` pada field `material_id`; perubahan atribut dan histori tidak dihapus. Perubahan parent, penulisan spesifikasi, pencatatan quote, dan pencatatan price memakai row lock varian yang sama. Tidak ada perubahan pada route, role matrix, migration, schema, UI, HPP, atau modul BPM lain.

## 4. Bukti Uji Konkurensi

Worker tes menerima konfigurasi koneksi aktif melalui pipe lokal, membuat koneksi PostgreSQL baru, dan melaporkan PID backend. Test induk menahan transaksi serta lock pertama, lalu memastikan worker kedua benar-benar menunggu dengan `pg_stat_activity.wait_event_type = 'Lock'` sebelum transaksi induk di-commit. Ini bukan dua operasi berurutan yang diberi label concurrency.

| Interleaving | Urutan yang diuji | Hasil yang dibuktikan |
|---|---|---|
| Spesifikasi lebih dahulu | Spec-write memegang lock; worker re-parent menunggu; spec-write commit | Worker melanjutkan terhadap state baru, menerima validation error `material_id`, parent tetap material lama, nilai spesifikasi tetap tersimpan. |
| Parent lebih dahulu | Re-parent memegang lock; worker spec-write menunggu; parent commit | Worker melanjutkan terhadap parent baru, menolak definisi kategori lama, parent baru tetap tersimpan, nilai spesifikasi tidak tercipta. |

Test tersebut menjalankan method service aplikasi aktual melalui koneksi kedua. Fixture dibuat dengan kode unik, dan cleanup dilakukan di `finally`. Hanya PostgreSQL `enterprise_bpm_test` yang digunakan.

## 5. Hasil Regression dan Kualitas

Perintah:

```text
php artisan test --compact tests/Feature/MmSpecificationTest.php tests/Feature/MmAuthorizationTest.php tests/Feature/MmVariantConcurrencyTest.php
```

Hasil terakhir setelah formatting:

```text
25 tests passed, 257 assertions
```

Pemeriksaan lain:

| Pemeriksaan | Hasil |
|---|---|
| Dua tes konkurensi PostgreSQL | PASS, 2 tests / 10 assertions |
| Focused specification + authorization + concurrency | PASS, 25 tests / 257 assertions |
| PHP syntax, lima file PHP berubah | PASS |
| Pint, hanya lima file PHP berubah | PASS |
| Scoped trailing-whitespace scan, lima file PHP berubah | PASS |
| Full application suite | NOT RUN |
| Browser E2E | NOT RUN |

Satu percobaan awal harness menggunakan `fork` dan memutus koneksi PostgreSQL yang diwarisi oleh proses induk; tidak ada klaim lulus dari percobaan itu. Harness diganti dengan proses PHP terpisah, kemudian dua tes konkurensi dan focused suite lengkap lulus.

## 6. Database Safety dan Preservation

- Metadata `.env.testing` diperiksa tanpa membaca isinya: regular file, mode `600`, 447 bytes.
- `bootstrap/cache/config.php` tidak ada.
- PHPUnit bootstrap dan `Tests\TestCase` menegakkan environment, database, koneksi PostgreSQL, serta role testing sebelum suite menulis.
- Test run berhasil melewati guard dan hanya menulis fixture pada `enterprise_bpm_test`.
- Tidak ada migration, rollback, seeding destruktif, atau perintah database lain yang dijalankan.
- `enterprise_bpm_browser` dan database production/legacy `enterprise_bpm` tidak digunakan atau dimutasi.
- Backup pra-edit untuk empat file PHP yang telah ada memiliki checksum valid; manifest berada di:
  `/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-2-concurrency-20261109-092610/SHA256SUMS.txt`
- Backup tersebut berada di luar repository. Tidak ada commit atau push.

## 7. Risiko dan Batas Remediasi

1. **Perubahan kategori material/definisi belum diserialisasi dengan row lock varian.** `MmMasterDataService::updateMaterial()` dan `MmAttributeDefinitionService::updateDefinition()` memiliki guard sequential atas relasi yang sudah ada, tetapi tidak mengambil lock yang sama dengan `setAttributes()`. Perubahan kategori yang bertabrakan dengan penulisan spesifikasi masih berpotensi mengubah kecocokan kategori setelah pemeriksaan. Ini tidak diubah atau diuji concurrency pada remediasi ini.
2. Foreign key dan unique constraint menjamin referensi serta keunikan; keduanya tidak menegakkan kesamaan kategori lintas tabel.
3. Tidak ada test concurrency terpisah untuk record quote/price melawan re-parent. Kesamaan lock dianalisis melalui source; regression suite menguji aturan histori secara non-concurrent.
4. Full suite, browser E2E, dan seluruh modul aplikasi tidak dijalankan. Hasil tidak membuktikan full application regression.
5. Append-only audit event belum menjadi bagian dari scope yang disetujui.

## 8. Keputusan

**REMEDIASI CONCURRENCY: PASS WITH FINDINGS**

Race pada interleaving spec-write/re-parent serta locking lifecycle quote/price telah ditangani pada jalur yang disetujui dan skenario concurrency terukur lulus. Finding kategori material/definisi tetap terbuka; sebelum fitur MM-02.2 mengubah kategori, perlu remediasi tersendiri atau penerimaan risiko eksplisit dengan batas operasional dan regression criteria.
