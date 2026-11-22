# MM-02.2 Master Material Core Specification

Tanggal: 9 November 2026

Status: Scope dan keputusan lifecycle parent disetujui project owner; rincian yang ditandai sebagai usulan atau terbuka bukan keputusan bisnis final.

## 1. Tujuan

Menetapkan batas scope dan acceptance criteria inti MM-02.2 untuk master material, definisi atribut, varian/SKU, dan nilai spesifikasi. Dokumen ini memformalkan keputusan owner yang tersedia dan membedakannya dari capability yang telah ada serta rekomendasi yang masih memerlukan persetujuan.

## 2. Keputusan Scope yang Disetujui

1. MM-02.2 membatasi pekerjaan pada fungsi inti Master Material.
2. Supplier CRUD, supplier quote CRUD, material pricing CRUD, dan perubahan HPP tidak termasuk.
3. Parent varian tidak boleh diubah apabila memiliki sekurang-kurangnya satu:
   - nilai spesifikasi;
   - histori supplier quote; atau
   - histori material price.
4. Jalur yang dapat menambah histori/atribut dan jalur re-parent harus memakai serialisasi terhadap row varian yang sama di dalam transaksi.
5. Hak akses mengikuti matriks Procurement yang sudah ada; role matrix tidak diubah sebagai bagian dari keputusan ini.

## 3. Capability Saat Ini yang Diverifikasi

Bagian ini adalah deskripsi source dan regression saat ini, bukan perluasan scope yang baru disetujui.

### Material, kategori, dan satuan

- Material terkait ke kategori dan unit terkait pada varian/definisi atribut melalui foreign key.
- Service memvalidasi kode unik, kategori dan satuan yang ada/aktif, serta dimensi satuan menurut enum aplikasi.
- Material, kategori, dan satuan yang digunakan dapat dinonaktifkan tanpa menghapus referensi historis.

### Definisi atribut

- Definisi terhubung ke satu kategori, memiliki kode unik dalam kategori, label, status aktif, flag required, urutan, dan tipe `text`, `number`, atau `boolean`.
- Tipe number dapat memiliki unit; text dan boolean tidak dapat memakai unit.
- Service mencegah perubahan kategori atau tipe tertentu ketika definisi telah digunakan, menurut guard aplikasi yang ada.

### Varian dan nilai

- Varian terhubung ke material induk dan unit dasar; SKU unik.
- Nilai spesifikasi terhubung ke varian dan definisi atribut.
- Nilai wajib mengikuti tipe definisi, definisi harus aktif dan sesuai kategori material, dan pasangan varian/definisi tidak boleh diduplikasi.
- Baris varian dikunci di dalam transaksi untuk re-parent dan penulisan nilai spesifikasi.

## 4. Aturan Lifecycle Parent yang Ditetapkan

Pada perubahan `material_id` yang benar-benar berbeda:

- Tujuan harus material yang ada dan aktif.
- Request ditolak dengan validation error pada `material_id` bila varian sudah memiliki spesifikasi, quote, atau price.
- Penolakan tidak mengubah parent, SKU, nama varian, atau histori yang sudah ada.
- Perubahan SKU, nama, dan unit tetap mengikuti validasi dan hak akses yang berlaku bila tidak ada perubahan parent yang ditolak.
- Re-parent hanya dapat dilakukan bila ketiga jenis histori tersebut belum ada.
- Semua writer spesifikasi, quote, dan price untuk varian yang sama mengambil row lock yang sama agar interleaving diserialisasi.

Aturan ini tidak mengizinkan penghapusan histori atau relasi untuk membuka re-parent.

## 5. Batas Integrasi

Termasuk sebagai boundary: mempertahankan relasi MM yang telah ada dan tidak merusak referensi supplier/quote/price atau HPP.

Tidak termasuk:

- halaman/form/controller/route CRUD supplier;
- pencatatan dan pengelolaan quote;
- pencatatan dan pengelolaan harga;
- pemilihan harga efektif atau kalkulasi harga;
- integrasi, perhitungan, atau perubahan HPP;
- event log append-only baru;
- perubahan permission, migration, atau schema tanpa persetujuan khusus.

Service quote dan price dapat tetap menjadi write path internal. Lock row varian di kedua service diperlukan untuk menegakkan aturan lifecycle parent; ini bukan persetujuan untuk membangun UI atau workflow quote/pricing.

## 6. Kriteria Penerimaan Inti yang Diusulkan

Kriteria berikut menjadi proposal implementasi/QA; detail yang mengubah kebijakan bisnis memerlukan persetujuan owner sebelum dikerjakan.

1. Procurement dapat membuat dan memperbarui material, kategori, satuan, definisi, varian, serta nilai sesuai validasi server dan authorization yang berlaku.
2. SKU, kode, pasangan nilai, tipe nilai, unit, required values, active references, serta category matching menolak duplikasi dan referensi/nilai tidak valid.
3. Re-parent ditolak ketika ada nilai spesifikasi, quote, atau price; data existing tetap utuh.
4. Re-parent tanpa ketiga histori diizinkan hanya ke material aktif.
5. Dua koneksi PostgreSQL independen yang bersamaan menyimpan spesifikasi dan re-parent pada varian yang sama harus menghasilkan serialisasi deterministik; tidak boleh meninggalkan kategori definisi yang tidak cocok.
6. Percobaan re-parent yang menunggu spec-write yang commit harus ditolak; spec-write yang menunggu re-parent harus divalidasi terhadap parent final.
7. ADMIN_BPM dan role read-only tidak dapat melakukan mutasi melalui request langsung; Procurement tetap mengikuti matriks permission yang sudah ada.
8. Test hanya memakai `enterprise_bpm_test`; tidak menjalankan migration atau menulis database browser/production.
9. Tidak ada perubahan supplier/pricing CRUD, HPP, HRMS, PROD, atau modul BPM lain.
10. Sebelum rilis, jalankan regression suite dan browser coverage sesuai test plan yang disetujui; focused MM tests saja bukan bukti full application regression.

## 7. Risiko Terbuka yang Tidak Diselesaikan Spesifikasi Ini

- `updateMaterial()` dan `updateDefinition()` memiliki guard sequential terhadap penggunaan kategori/definisi, namun saat ini belum diserialisasi dengan lock variant yang digunakan writer nilai. Concurrent edit kategori dapat menjadi sumber inkonsistensi dan perlu ditangani atau dibatasi sebelum scope MM-02.2 mengubah kategori.
- Schema tidak memiliki constraint yang secara langsung menegakkan category match lintas tabel.
- Kebijakan append-only event history belum disetujui.
- Detail pemeliharaan/perubahan definisi yang sudah dipakai, kebijakan soft delete, dan perilaku UI di luar alur yang tersedia harus dikonfirmasi jika menjadi kebutuhan baru.

## 8. Perubahan Scope Memerlukan Persetujuan

Persetujuan tambahan diperlukan sebelum:

- menambahkan atau mengubah UI/workflow supplier, quote, price, atau HPP;
- mengubah role matrix atau route authorization;
- mengubah migration/database schema;
- mengubah aturan re-parent yang ditetapkan pada bagian 4;
- memperluas audit trail menjadi event log append-only;
- mengubah kategori material/definisi dengan kebijakan concurrency yang berbeda.

