# PROD-02 — Paket implementasi lokal Tahap 1

Acuan: `BPM_Production_Tahap_2_FINAL_v1.0.md` (FINAL/LOCKED 12 Oktober 2026).

## Batas fitur

**Berfungsi setelah integrasi:** menyimpan DRAFT parsial, memperbarui DRAFT milik Marketing, daftar Draft, indikator COMPLETE/INCOMPLETE, saran kandidat duplikasi deterministik untuk Marketing, keputusan Permintaan Baru/Kelanjutan/Duplikat dengan audit append-only, serta penolakan akses non-Marketing.

**Belum diaktifkan:** CANCELLED dan request cancellation, lampiran upload, notifikasi, PROD-03. Submit brief baru dan penomoran resmi sekarang tersedia setelah pemeriksaan duplikasi dan keputusan Permintaan Baru Marketing.

## Pemasangan tanpa menghapus pekerjaan lama

1. BACKUP proyek dan DB terlebih dahulu. Periksa nama tabel agar tidak konflik dengan migration yang sudah ada.
2. Salin file di dalam folder `app/`, `database/`, dan `resources/` ke path yang identik pada proyek `~/Documents/Codex/LaravelApps/webapp-saya/`. **Jangan mengganti direktori proyek secara keseluruhan.**
3. Route PROD-02 sudah terdaftar di `routes/web.php`. **Jangan menempelkan `routes/prod02_routes_snippet.php` lagi** karena dapat mendaftarkan route duplikat.
4. Login lokal tersedia untuk akun pada tabel `users`. Lima percobaan login gagal per kombinasi email dan alamat IP dibatasi dalam jendela 60 detik. Setelah login, pengguna diarahkan ke Dashboard; modul Production ditampilkan untuk role `MARKETING`. Sistem ini belum memiliki permission granular.
5. Di Terminal dari folder proyek:

   ```sh
   php artisan migrate:status
   php artisan migrate
   php artisan db:seed --class=Prod02FoundationSeeder
   php artisan bpm:assign-marketing email-user-anda@example.com
   php artisan route:list --path=production/briefs
   ```

6. Login memakai user yang sudah diberi role Marketing, lalu pilih modul Production pada Dashboard atau buka `http://127.0.0.1:8081/production/briefs`. Tombol Logout mengirim request POST dengan CSRF.
7. Buat Draft sebagian, simpan, edit, pastikan baris `client_brief_events` bertambah. Uji akses role non-Marketing (harus 403).

## Catatan teknis

- Kompatibilitas target: Laravel modern dengan route model binding, PHP 8.3+ dan PostgreSQL 17. Suite automated test feature telah dijalankan secara lokal terhadap database testing terisolasi.
- Pengujian feature menggunakan PostgreSQL 17 terisolasi `enterprise_bpm_test` dengan role khusus `enterprise_bpm_test_user`; jangan gunakan database aktif `enterprise_bpm` atau kredensial superuser. PHPUnit mengunci connection, host, port, database, username, dan mengosongkan `DB_URL`; cache/session memakai driver array dan queue memakai sync. `tests/TestCase.php` memeriksa driver, `current_database()`, `current_user`, `session_user`, atribut administratif role, keanggotaan role, ownership, serta hak tulis persisten pada database aktif melalui transaksi read-only sebelum `RefreshDatabase` menjalankan migration.
- `.env.testing` lokal (di-ignore Git) berisi `APP_KEY` testing dan `DB_PASSWORD` untuk role test serta dibatasi dengan permission `0600`. Test bootstrap berhenti jika file hilang, berupa symlink, atau dapat dibaca user lain; file `.env` aplikasi tidak digunakan sebagai fallback. Database dan role test lokal telah diprovisikan. Feature suite dan guard database dijalankan terhadap PostgreSQL testing; `RefreshDatabase` menjalankan migration pada database test saja.
- Hak akses awal `MARKETING` disimpan pada tabel `bpm_roles` agar tidak berkonflik dengan paket roles lain. Belum ada permission granular; akan ditambah sebagai tahap lanjutan.
- URL publik belum dibuat; tetap di localhost.
- Audit event dibuat pada transaksi DB yang sama dengan perubahan Draft. Larangan edit/hapus event melalui UI, namun hak DB superuser tetap dapat mengubahnya; desain immutable DB-level merupakan pekerjaan lanjutan.
- Pemeriksaan kandidat PROD-02 hanya mencakup brief `RECORDED`, menormalkan teks dan menghitung Dice coefficient atas token unik; target waktu cocok bila bulan dan tahunnya sama. Kandidat membutuhkan minimal dua field yang cocok (ambang teks 50%), skor diurutkan menurun dengan ID sebagai tie-breaker, dan hasil dibatasi 10. Ini hanya saran; keputusan Marketing tetap eksplisit. Perubahan pada field pemeriksaan setelah hasil dibuat membatalkan tautan lanjutan yang tersimpan dan mewajibkan pemeriksaan ulang sebelum keputusan berikutnya.
- Submit PROD-02 hanya berlaku bagi pemilik Marketing atas Draft lengkap dengan keputusan Permintaan Baru yang masih valid. Penerbitan nomor `REQ-YYYY-NNNN`, status `RECORDED`, `recorded_at`, dan audit `REQUEST_RECORDED` dilakukan dalam transaksi PostgreSQL; kandidat diperiksa ulang di bawah transaction advisory lock dan counter tahunan dialokasikan lewat upsert atomik.
- Migration `down` hanya untuk lingkungan dev, **jangan jalankan migrate:fresh atau rollback tanpa backup**.
- Hindari memanggil `php artisan migrate` jika terdapat migrasi lain yang belum ditinjau pada aplikasi Anda.
- Format resmi request number masih menunggu tahap submit; spesifikasi hanya memberi contoh `REQ-2026-0001`.
