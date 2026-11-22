# MM-02.1 Baseline Preservation Report

Tanggal: 9 November 2026

## 1. Ringkasan

Baseline lokal MM-02.1 telah disalin ke backup bertanggal di luar repository. Salinan seluruh 31 file proyek yang dipilih cocok byte-per-byte dengan sumber, seluruh checksum pada manifest awal valid, dan patch diff tracked lengkap telah ditambahkan sebagai artefak pelengkap dengan checksum terpisah.

Tidak ada file aplikasi, migration, konfigurasi database, atau data proyek yang diubah oleh kegiatan preservasi ini. Tidak ada commit atau push. Dua laporan audit ini merupakan satu-satunya file baru di repository.

## 2. Git Baseline

- Root: `/Users/bayusuwardani/Documents/Codex/LaravelApps/webapp-saya`
- Branch: `main`
- Staged changes: 0
- Tracked modifications: 24
- Untracked entries: 130
- Total entries pada `git status --short --branch` (tidak termasuk baris header branch): 154
- Repository tidak bersih saat baseline diambil; perubahan terdahulu dipertahankan.
- Pada status singkat, `docs/audits/` dilaporkan sebagai satu direktori untracked. Karena itu jumlah entry singkat tetap 154 setelah laporan dibuat; inventaris untracked yang diekspansi bertambah tepat dua path laporan ini.

Daftar 24 path tracked yang berubah pada baseline:

```text
CLAUDE.md
app/Http/Controllers/MarketingBriefController.php
app/Http/Controllers/Prod02Controller.php
app/Models/ClientBrief.php
app/Providers/AppServiceProvider.php
app/Services/BrowserTestingRuntimeGuard.php
app/Services/Prod05ValidationService.php
bootstrap/app.php
composer.json
composer.lock
phpunit.xml
resources/views/dashboard.blade.php
resources/views/layouts/app.blade.php
resources/views/prod02/form.blade.php
resources/views/prod02/index.blade.php
resources/views/prod03/briefs/form.blade.php
resources/views/prod03/briefs/index.blade.php
resources/views/prod03/briefs/show.blade.php
resources/views/prod03/design-requests/form.blade.php
resources/views/prod03/design-requests/show.blade.php
routes/web.php
tests/TestCase.php
tests/Unit/BrowserTestingRuntimeGuardTest.php
tests/bootstrap.php
```

Snapshot lengkap `git status --short --branch`, daftar 130 path untracked, nama/status diff, statistik diff, branch, dan diff `routes/web.php` disimpan di:

`/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-1-preservation-20261109-054334/git-baseline/`

Diff penuh seluruh file tracked yang berubah juga tersedia sebagai `tracked-diff-full.supplemental-20261109.patch` di direktori tersebut. Artefak tambahan ini dibuat dengan nama baru, tidak menimpa isi backup awal. Pemindaian lokal untuk pola literal credential tidak menemukan kandidat sebelum patch disimpan.

### Perbedaan dengan laporan audit terdahulu

[MM02-1_FINAL_AUDIT.md](./MM02-1_FINAL_AUDIT.md) mencatat baseline 24 tracked modifications dan 129 untracked entries. Snapshot baru berisi 130 untracked entries. Perbedaan satu entri konsisten dengan penambahan file laporan final tersebut setelah baseline audit lama diambil; baseline baru dan snapshot eksternal menjadi acuan terkini. Laporan final terdahulu tidak diubah.

## 3. File MM-02.1 yang Dipreservasi

Empat file yang secara khusus diidentifikasi sebagai hasil audit MM-02.1:

- `app/Services/MmMasterDataService.php`
- `tests/Feature/MmAuthorizationTest.php`
- `tests/Feature/MmSpecificationTest.php`
- `docs/audits/MM02-1_FINAL_AUDIT.md`

Backup juga mencakup sumber MM terkait yang sudah ada: controller, model, service, migration fondasi, route file dan diff, seluruh Blade view MM-05, feature tests, serta file backup service yang memang sudah ada di worktree. Inventaris lengkap per file, ukuran, dan SHA-256 tercatat pada `MANIFEST.tsv`.

Pemeriksaan silang menunjukkan guard perubahan kategori material masih terdapat pada `MmMasterDataService`, dan test HTTP untuk guard tersebut masih ada. Dua focused test file juga dijalankan ulang pada audit ini dan lulus dengan 14 test/201 assertion.

## 4. Lokasi dan Verifikasi Backup

Lokasi:

`/Users/bayusuwardani/Documents/Codex/LaravelApps/backups/MM02-1-preservation-20261109-054334`

Hasil verifikasi:

- 31 file di subdirektori `project/` dibandingkan langsung dengan file sumber; semuanya identik.
- Seluruh 38 entri checksum/manifest pada backup awal valid dan jumlah entrinya cocok.
- Patch tracked tambahan memiliki SHA-256 tersendiri pada `git-baseline/tracked-diff-full.supplemental-20261109.sha256`.
- Backup tidak memuat `.env`, `.env.testing`, file bernama credential/password/secret, atau private-key file.
- Pemeriksaan pola credential pada diff `routes/web.php` dan patch tracked tambahan tidak menemukan kandidat literal.
- `.env` tidak dibaca. Untuk pemeriksaan test, `.env.testing` hanya diperiksa keberadaan, jenis file, ukuran, dan permission; tidak disalin.

## 5. Discrepancy dan Batasan

- Worktree tetap memiliki 24 tracked modifications dan banyak file untracked dari pekerjaan terdahulu, termasuk perubahan lintas modul. Tidak ada perubahan tersebut yang dibersihkan, ditimpa, atau di-commit.
- Hasil pemeriksaan whitespace menemukan satu trailing whitespace pada perubahan tracked yang sudah ada di `resources/views/prod03/briefs/index.blade.php:90`. File tersebut berada di luar lingkup dan tidak diubah.
- Backup melindungi keadaan yang teramati saat snapshot diambil; laporan ini tidak menyatakan perubahan terdahulu sebagai baseline Git yang bersih atau sebagai perubahan dari tugas ini.

## 6. Perubahan oleh Tugas Ini

Hanya dua dokumen audit yang dibuat di repository:

- `docs/audits/MM02-1_BASELINE_PRESERVATION.md`
- `docs/audits/MM02-2_READINESS_AUDIT.md`

Selain kedua laporan tersebut, pekerjaan hanya menghasilkan backup eksternal dan hasil pemeriksaan non-destruktif. Tidak ada migration, rollback, seeding, commit, atau push.
