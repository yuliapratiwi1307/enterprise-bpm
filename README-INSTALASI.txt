MM-05 REDESIGN V2 — INSTALLATION

Isi:
- Form pendaftaran material disederhanakan.
- Kolom kode material dihilangkan dari UI; kode tetap dibuat otomatis oleh backend MAT-000001 dst.
- Spesifikasi muncul sesuai kategori.
- Varian utama wajib satu; varian tambahan opsional dan bisa ditambah/hapus pada form.
- Pilihan supplier eksklusif: existing, new, atau none.
- Supplier baru dibuat ke mm_suppliers dalam transaksi yang sama dengan material.
- Nomor telepon tambahan supplier disimpan pada tabel mm_supplier_contacts; nomor pertama tetap pada mm_suppliers.phone.
- Harga langsung dicatat Procurement; harga awal melekat ke varian utama.
- Tidak menambahkan approval, quote, atau publikasi Price Bank sebagai langkah wajib.

PENTING:
- Spesifikasi yang diisi pada bagian 2 diterapkan ke semua varian yang dibuat pada form ini.
- Harga awal yang diisi pada form berlaku untuk varian utama. Harga varian lain dapat dikelola kemudian dari detail material.
- Jika memilih supplier terdaftar atau supplier baru, harga wajib diisi agar hubungan supplier-harga tercatat. Jika belum ada supplier/harga, pilih "Belum menentukan supplier".
- Patch ini ditujukan untuk proyek Laravel dengan PostgreSQL.

CARA PASANG:
1. Letakkan ZIP di ~/Downloads.
2. Dari Terminal, masuk ke root proyek:
   cd ~/Documents/Codex/LaravelApps/webapp-saya
3. Ekstrak ZIP:
   unzip -o ~/Downloads/mm05-redesign-v2-patch.zip -d .
4. Jalankan:
   chmod +x install-mm05-redesign-v2.sh
   ./install-mm05-redesign-v2.sh

Installer:
- Membuat backup file Controller, Blade, dan migration yang sudah ada.
- Menyalin patch dari folder mm05-redesign-v2-patch.
- Memeriksa sintaks PHP.
- Menjalankan migration untuk nomor kontak tambahan.
- Membersihkan cache Laravel.

Setelah instalasi:
- Buka /material-master/materials/create
- Uji pergantian kategori.
- Uji tambah/hapus varian.
- Uji mode supplier existing/new/none.
- Jangan langsung gunakan data produksi; uji dahulu dengan data percobaan.
