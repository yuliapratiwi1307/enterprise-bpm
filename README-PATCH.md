# SJR V6.3.1 – Lead Time Produksi & Simplifikasi Form

Patch ini berisi dua file:
- `app/Http/Controllers/SjrMultiItemTechnicalDraftController.php`
- `resources/views/sjr/multi-item-production-detail.blade.php`

## Perubahan
1. Menambahkan field Estimasi Lead Time Produksi (hari kerja) per item.
2. Memperbaiki penyimpanan `leadtime_days`; sebelumnya request divalidasi tetapi nilai baru tidak disimpan.
3. Menghapus UI Atribut Tambahan dari form material.
4. Menghapus validasi dan normalisasi atribut tambahan dari penyimpanan draft.
5. Menampilkan Lead Time pada tampilan baca-saja.

## Penerapan
1. Backup dua file asli.
2. Ekstrak ZIP patch ke root project Laravel (`webapp-saya`) dan izinkan file ditimpa.
3. Jalankan `php artisan view:clear`.
4. Buka ulang halaman detail Multi-Item dan uji simpan draft pada satu item.

Tidak ada migration baru. Kolom `leadtime_days` dan `leadtime_note` sudah ada pada tabel `sjr_multi_item_technical_drafts`.
