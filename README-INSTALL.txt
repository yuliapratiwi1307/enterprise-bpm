PATCH MM05 — PENYEDERHANAAN FORM TAMBAH MATERIAL

Isi patch:
- app/Http/Controllers/MmMaterialWizardController.php
- resources/views/mm05/material-wizard.blade.php
- install-mm05-redesign.sh

Cara pemasangan:
1. Ekstrak isi ZIP ini langsung ke folder utama proyek Laravel (folder yang berisi file artisan).
2. Buka Terminal dan masuk ke folder utama proyek.
3. Jalankan:
   chmod +x install-mm05-redesign.sh
   ./install-mm05-redesign.sh

Script akan:
- Membuat backup kedua file yang akan diganti.
- Memasang controller dan tampilan baru.
- Memeriksa sintaks PHP controller.
- Membersihkan cache view Laravel.
- Tidak menjalankan migration dan tidak mengubah database.

Perubahan utama:
- Form dibuat menjadi empat bagian bernomor benar: Identitas, Spesifikasi & Varian, Supplier & Harga, Simpan.
- Spesifikasi tampil sesuai kategori dan atribut wajib divalidasi di server.
- Supplier lama dapat dipilih atau supplier baru ditambahkan.
- Harga langsung dicatat dari konfirmasi Procurement; tidak memerlukan alur approval quote atau Price Bank.
- Harga tetap opsional saat membuat material; jika harga diisi, supplier, satuan, mata uang, dan tanggal harus lengkap.

Catatan pengujian:
- Pemeriksaan sintaks PHP untuk controller, route, service harga, dan test berhasil.
- Test PHPUnit belum dapat dijalankan di lingkungan pemeriksaan ini karena ekstensi PHP DOM, mbstring, dan xmlwriter tidak tersedia.
