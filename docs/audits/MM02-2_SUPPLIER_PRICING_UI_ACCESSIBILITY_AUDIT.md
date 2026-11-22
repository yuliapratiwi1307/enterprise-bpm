# MM-02.2 Supplier & Pricing UI Accessibility Audit

**Project:** Enterprise BPM Laravel
**Module:** MM-05 Master Material
**Audit type:** Read-only source inspection

## Ringkasan

Menu Supplier, Penawaran Harga, dan Histori Harga belum dapat dibuka sebagai halaman karena shortcut di dashboard bukan named routes. Ketiganya memakai `href="#supplier-harga"` dan hanya mengarah ke satu bagian ringkasan/placeholder di dashboard. Tidak ditemukan route MM khusus, controller, atau Blade view untuk daftar/detail supplier, quote, maupun material price.

Fondasi database, model, relasi, serta service untuk mencatat quote dan price sudah ada. Namun service yang tersedia adalah operasi pencatatan, bukan controller atau halaman UI, dan tidak menyediakan fungsi baca/list. Selain itu, audit harga supplier sebelumnya mencatat bahwa metadata approval/aktif quote dan pemilihan harga valid terbaru belum tersedia. Karena itu, pembuatan halaman nantinya harus dibedakan dari implementasi approval atau pemilihan harga.

## Temuan per menu

| Menu | Kondisi saat ini | Penyebab tidak bisa diakses | File terkait | Solusi yang direkomendasikan |
|---|---|---|---|---|
| Supplier | Dashboard menunjukkan jumlah supplier aktif dan shortcut “Supplier”, tetapi bukan halaman daftar atau detail yang dapat dibuka. | Shortcut di `mm05/index.blade.php` memakai `href="#supplier-harga"`; anchor itu menunjuk ke bagian ringkasan di dashboard. Tidak ditemukan route/controller/view MM khusus supplier. Model supplier dan relasi quote tersedia, tetapi tidak ditemukan `MmSupplierService` atau operasi CRUD supplier. | `resources/views/mm05/index.blade.php`; `routes/web.php`; `app/Services/MmAuthorization.php`; `app/Models/MmSupplier.php`; migration MM foundation | Langkah UI terkecil: ganti shortcut menjadi route bernama yang mengarah ke halaman daftar baca supplier. Terapkan `authorizeView`; jika Procurement membutuhkan create/update/deactivate, rancang layanan dan validasi supplier secara terpisah—jangan menganggap model saja sudah merupakan CRUD. |
| Penawaran Harga | Dashboard menampilkan jumlah penawaran dan shortcut “Penawaran Harga”. Belum ada halaman daftar/detail quote. | Shortcut memakai anchor placeholder yang sama. Tidak ditemukan route, controller, atau view quote. `MmSupplierQuoteService` dapat mencatat quote, tetapi tidak menyediakan operasi baca/list dan tidak terhubung ke HTTP. | `resources/views/mm05/index.blade.php`; `routes/web.php`; `app/Services/MmSupplierQuoteService.php`; `app/Models/MmSupplierQuote.php`; `app/Models/MmSupplier.php`; `app/Models/MmMaterialVariant.php`; migration MM foundation | Langkah terkecil untuk akses adalah route GET, controller index dengan eager loading variant/material dan supplier, serta view daftar quote. Otorisasi baca memakai `MmAuthorization::authorizeView`; aksi tulis hanya boleh Procurement dan perlu dipisah dari akses baca. Sebelum menampilkan quote sebagai eligible/approved, tutup gap status approval/aktif dan definisi tanggal efektif dari audit Supplier Price Reference. |
| Histori Harga | Dashboard menampilkan jumlah referensi harga dan shortcut “Histori Harga”, tetapi hanya ada ringkasan total; tidak ada tabel histori yang dapat dibuka. | Shortcut memakai anchor placeholder yang sama. Tidak ditemukan route, controller, atau view histori harga. `MmMaterialPriceService` hanya mencatat price, tidak menyediakan query histori atau pemilihan harga terbaru. | `resources/views/mm05/index.blade.php`; `routes/web.php`; `app/Services/MmMaterialPriceService.php`; `app/Models/MmMaterialPrice.php`; `app/Models/MmMaterialVariant.php`; `app/Models/MmSupplierQuote.php`; migration MM foundation | Langkah terkecil untuk akses adalah route GET dan halaman read-only yang menampilkan histori per variant/SKU dengan pagination dan eager loading variant, unit, source quote, serta supplier. Jangan menampilkan satu “harga terbaru supplier” sebelum aturan eligibility, approval, expiry, effective date, tie, unit, dan currency ditentukan serta diuji. |

## Pemeriksaan navigasi

Di `resources/views/mm05/index.blade.php`, shortcut aktif yang mengarah ke halaman bernama adalah:

- Data Material → `mm05.materials.index`
- Kategori & Satuan → halaman kategori yang tersedia
- Spesifikasi Material → `mm05.specifications.index`
- Varian Material → `mm05.variants.index`

Sebaliknya, Supplier, Penawaran Harga, dan Histori Harga masing-masing memakai `href="#supplier-harga"`. Anchor tersebut adalah section dashboard yang menampilkan ringkasan jumlah supplier/quote/price dan teks “Form pengelolaan akan diintegrasikan berikutnya”; bukan halaman pengelolaan.

## Route, controller, view, dan authorization

- `routes/web.php` memiliki route MM untuk workspace, materials, categories, units, specifications, dan variants di dalam middleware `auth`.
- Tidak ditemukan route MM untuk supplier, quote, maupun material price.
- Tidak ditemukan controller khusus untuk resource supplier/quote/price. `MmWorkspaceController::index()` hanya menyediakan angka ringkasan dashboard.
- Tidak ditemukan Blade view di `resources/views/mm05/` untuk daftar/detail supplier, quote, atau histori price.
- `MmAuthorization` sendiri bukan penyebab kegagalan navigasi. Ia mengizinkan pembacaan bagi `ADMIN_BPM`, `PROCUREMENT`, `ASST_MANAGER_PRODUKSI`, dan `ADMIN_PRODUKSI`; hanya `PROCUREMENT` yang boleh mengelola. Controller GET baru perlu memanggil `authorizeView`, dan endpoint mutasi—jika kelak disetujui—harus memanggil `authorizeManage`.
- Dengan demikian, penyebab langsung akses UI adalah route/controller/view belum tersedia; bukan bukti adanya penolakan role dari halaman resource yang sudah ada.

## Fondasi database dan relasi yang tersedia

Migration `2026_11_05_130000_create_mm_material_master_foundation.php` menyediakan:

- `mm_suppliers`, termasuk identitas/kontak dan `is_active`.
- `mm_supplier_quotes`, berelasi ke `mm_material_variants`, `mm_suppliers`, dan `mm_units`, dengan amount/currency, `quoted_on`, `valid_until`, reference, dokumen sumber, catatan, dan `recorded_by`.
- `mm_material_prices`, berelasi ke `mm_material_variants`, `mm_units`, serta optional `mm_supplier_quotes`, dengan `price_type`, amount/currency, `effective_from`, `effective_until`, sumber, alasan perubahan, dan `recorded_by`.
- Foreign keys yang membatasi penghapusan data terkait, check amount positif dan rentang tanggal, serta index lookup variant/date.

Relasi model juga tersedia: supplier has-many quotes; quote belongs-to variant/supplier/unit dan has-many material prices; price belongs-to variant/unit dan optional source quote; variant has-many quote dan price; material has-many variant.

Fondasi tersebut cukup untuk membuat halaman baca/list dasar tanpa migration baru. Namun, audit ini tidak mengklaim bahwa data harga sudah memenuhi aturan supplier price reference. Quote belum memiliki status aktif atau approval, dan belum ada selector harga eligible terbaru. Temuan terperinci untuk aturan bisnis tersebut ada pada [MM02-SUPPLIER-PRICE-REFERENCE-AUDIT.md](./MM02-SUPPLIER-PRICE-REFERENCE-AUDIT.md).

## Rekomendasi langkah implementasi terkecil

1. **Untuk akses baca UI saja:** ubah tiga shortcut dashboard dari anchor ke named routes; tambahkan GET route, controller index, dan view daftar untuk masing-masing resource. Reuse schema/model yang sudah ada dan pagination; eager-load relasi yang ditampilkan agar tidak terjadi N+1.
2. Tegakkan role pada controller: semua role yang sudah memiliki hak baca dapat melihat; hanya Procurement dapat mengakses operasi mutasi. Jangan mengubah matriks role.
3. Implementasikan halaman daftar quote dan histori price sebagai histori data, bukan sebagai klaim “harga supplier terbaru/approved”, sampai aturan eligibility dari audit harga tersedia.
4. Supplier CRUD memerlukan service/controller write path yang belum ditemukan; jangan mengasumsikan dapat dibuat hanya dengan menghubungkan model ke route.
5. Untuk menampilkan harga supplier terbaru yang sesuai bisnis, terlebih dahulu selesaikan metadata approval/aktif, definisi effective date, masa berlaku, tie antar supplier, unit/currency, serta empty state “Harga belum tersedia”. Itu adalah perubahan terpisah dari sekadar membuat menu dapat dibuka.

## Batas audit

- Tidak ada source, route, permission, migration, UI, atau data yang diubah.
- Tidak menjalankan test, migration, atau query database.
- `.env` tidak dibaca.
- Git worktree sudah memiliki perubahan sebelum audit; seluruh perubahan existing dipertahankan.
