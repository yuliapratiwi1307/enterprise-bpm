# MM-02.2 Readiness Audit

Tanggal: 9 November 2026

## 1. Executive Summary

Fondasi Master Material dan sebagian besar perilaku yang relevan untuk spesifikasi serta varian sudah tersedia: definisi atribut, tipe nilai, SKU/varian, nilai spesifikasi, validasi aktif/nonaktif, pembatasan role, dan feature tests. MM route saat ini berjumlah 22 dan seluruhnya berada di middleware `web` serta `auth`.

Namun, terdapat cacat integritas yang dapat dicapai melalui endpoint Procurement yang sah: varian yang telah mempunyai nilai spesifikasi dapat dipindahkan ke material lain tanpa memeriksa bahwa definisi atribut yang tersimpan masih sesuai dengan kategori material tujuan. Foreign key tetap valid, tetapi hubungan domain menjadi tidak konsisten. Perbaikan dan regression test untuk jalur ini merupakan prasyarat sebelum implementasi MM-02.2 dilanjutkan.

Tidak ditemukan dokumen spesifikasi MM-02.2 formal yang dapat dijadikan sumber aturan bisnis yang disahkan pada bahan dokumentasi yang ditinjau. Karena itu, klasifikasi dan rekomendasi di bawah membedakan perilaku yang sudah terverifikasi dari hal-hal yang membutuhkan keputusan pemilik produk.

## 2. Dasar dan Batas Audit

- Audit ini hanya menilai kesiapan dan mempertahankan pekerjaan yang ada; tidak mengimplementasikan atau memperbaiki source.
- Pemeriksaan kode mencakup controller, service, authorization, safety guard, migration, route, dan view MM-05.
- Pengujian ulang dibatasi pada dua focused feature test MM-05 setelah pengaman database memvalidasi target test.
- Tidak ada migration atau operasi skema yang dijalankan.
- Browser E2E tidak diulang. Hasil browser sebelumnya yang disampaikan pengguna bukan bukti pengujian ulang pada audit ini.
- Tidak ada akses ke database `enterprise_bpm_browser` atau `enterprise_bpm` dalam audit ini.

## 3. Hasil Audit Arsitektur

### Database dan model

Migration fondasi MM mendefinisikan tabel untuk unit, kategori, material, definisi atribut, varian, nilai atribut varian, supplier, penawaran supplier, dan harga material. Relasi material-kategori, atribut-kategori, varian-material, varian-unit, nilai-varian, nilai-definisi, penawaran-varian/supplier/unit, dan harga-varian/unit/sumber penawaran memakai foreign key dengan `RESTRICT` saat penghapusan.

Unique constraints yang teramati melindungi kode kategori, unit, material, SKU, kode atribut dalam kategori, dan pasangan varian-definisi atribut. Nilai spesifikasi memiliki kolom terpisah untuk text, number, dan boolean, serta unique constraint untuk satu definisi atribut per varian. Harga dan penawaran memiliki check constraint positif untuk jumlah dan validasi rentang tanggal.

Tipe definisi atribut (`text`, `number`, `boolean`), penggunaan satuan hanya untuk tipe `number`, satuan aktif, dan dimensi satuan diperiksa di service. Schema menyimpan `data_type` dan `dimension` sebagai string; aturan enum tersebut tidak sepenuhnya ditegakkan oleh database. Relasi foreign key sendiri juga tidak menjamin bahwa definisi atribut milik kategori material yang sama.

Material, kategori, unit, definisi, varian, dan nilai yang dinonaktifkan tetap tersimpan; pendekatan soft deactivate ini mempertahankan referensi historis. Kolom `created_by`, `updated_by`, dan `recorded_by` memberi provenance dasar, tetapi bukan event log append-only.

### Controller dan service

- `MmWorkspaceController`, `MmMasterDataController`, dan `MmSpecificationController` menangani endpoint dan view untuk workspace, material, kategori, unit, definisi, varian, serta nilai atribut.
- `MmMasterDataService`, `MmAttributeDefinitionService`, dan `MmVariantService` melakukan validasi server-side, pemeriksaan referensi aktif, dan pembatasan perubahan definisi yang sudah dipakai.
- Penggantian nilai atribut varian dilakukan dalam transaksi database; nilai diuji terhadap tipe definisi, kategori material, status aktif, duplikasi, dan atribut wajib.
- `MmSupplierQuoteService` dan `MmMaterialPriceService` menyediakan fondasi validasi dan pencatatan layanan, termasuk kecocokan quote dengan varian, satuan, dan mata uang pada pembentukan harga.
- Tidak ditemukan CRUD controller/route untuk supplier dan price-reference. Workspace secara eksplisit menampilkan jumlah data dan menyebut form pengelolaan akan diintegrasikan berikutnya.

### Routes dan authorization

`php artisan route:list --path=material-master -v` menampilkan 22 route MM-05; setiap route memiliki middleware `web` dan `auth`. `MmAuthorization` memberi akses baca kepada `ADMIN_BPM`, `PROCUREMENT`, `ADMIN_PRODUKSI`, dan `ASST_MANAGER_PRODUKSI`; hanya `PROCUREMENT` yang dapat mengelola. Role lain, termasuk MARKETING dan DESIGNER, tidak mendapat akses.

Controller memanggil authorization server-side untuk operasi baca maupun mutasi. Form memakai data yang dipilih secara eksplisit dari request, dan service menjalankan validasi sebelum persistensi. CSRF disediakan melalui middleware `web`; fitur ini tidak diuji ulang sebagai browser scenario. Model binding dan pemeriksaan authorization melindungi direct URL sesuai matriks role. Katalog bersifat shared, sehingga tidak ada pembatasan per-record/tenant dalam implementasi yang ditinjau.

### Antarmuka

Halaman spesifikasi dan varian mempunyai endpoint dan view sendiri. Kontrol mutasi pada view mengikuti flag `canManage`, namun otorisasi server tetap dilakukan di controller. Form definisi dan varian terhubung ke service; nilai spesifikasi ditampilkan melalui relasi varian-definisi. Workspace menampilkan supplier, quote, dan harga yang tersimpan sebagai ringkasan/anchor, bukan sebagai form CRUD. Audit ini tidak menjalankan browser, sehingga tidak menyatakan ulang validasi visual atau JavaScript.

## 4. Cacat dan Risiko yang Ditemukan

### R1 - Re-parent varian dapat meninggalkan nilai spesifikasi lintas kategori

`MmVariantService::updateVariant()` memvalidasi bahwa `material_id` yang dikirim valid dan aktif, lalu mengubah varian. Jalur ini tidak menolak perubahan material ketika varian sudah mempunyai nilai atribut dan tidak membandingkan kategori definisi atribut tersimpan dengan kategori material tujuan. Berbeda dengan `MmMasterDataService::updateMaterial()`, yang menolak perubahan kategori material jika ada spesifikasi varian, guard tersebut tidak mencegah pemindahan varian ke material kategori lain.

Foreign key `mm_variant_attributes.variant_id` dan `attribute_definition_id` tetap terpenuhi setelah pemindahan, sehingga constraint yang ada tidak mencegah keadaan tersebut. `setAttributes()` memeriksa kategori ketika nilai baru dikirim, tetapi pemeriksaan itu tidak memperbaiki nilai lama yang ikut terbawa saat parent varian berubah.

**Dampak:** katalog dapat menampilkan material dengan nilai spesifikasi dari kategori lain; referensi downstream yang melekat pada SKU/varian juga dapat menjadi ambigu jika parent material berubah.

**Uji yang ada:** terdapat test HTTP untuk melarang perubahan kategori material setelah nilai spesifikasi disimpan, dan test untuk menolak atribut lintas kategori pada penyimpanan nilai. Belum ada test untuk memindahkan varian yang sudah memiliki nilai ke material kategori berbeda.

**Rekomendasi remediasi sebelum MM-02.2:** tentukan kebijakan bisnis perpindahan varian. Pilihan paling konservatif adalah menolak perubahan parent bila sudah ada nilai spesifikasi, kecuali aturan pemetaan/migrasi nilai disetujui. Tambahkan feature test melalui endpoint yang membuktikan penolakan dan memastikan parent serta seluruh nilai tetap tidak berubah. Pertimbangkan juga semantik quote/harga yang sudah terkait dengan varian. Tidak ada perbaikan dilakukan pada audit ini.

### R2 - Invariant kategori sebagian besar ditegakkan pada aplikasi

Foreign key dan unique index menjaga keberadaan referensi serta keunikan, tetapi tidak menegakkan kesamaan kategori material dengan definisi spesifikasi pada level database. Perilaku jalur HTTP normal memiliki validasi service, tetapi semua jalur perubahan parent harus mempertahankan invariant yang sama. Ini memperkuat kebutuhan perbaikan R1 dan pengujian regresi.

### R3 - Audit trail adalah provenance, bukan histori perubahan

Schema mencatat sebagian ID pembuat/pengubah/perekam dan timestamp. Tidak teramati event log append-only untuk perubahan master data, spesifikasi, atau nilai varian. Apakah histori perubahan merupakan acceptance requirement MM-02.2 perlu diputuskan; jangan menambahkannya sebagai scope tanpa persetujuan.

### R4 - Supplier, harga, dan HPP belum menjadi alur UI terintegrasi

Model, schema, dan service harga/quote sudah ada, tetapi workspace menyatakan form akan diintegrasikan berikutnya dan tidak ada route CRUD yang teramati. Integrasi HPP, pemilihan sumber harga, dan lifecycle quote/harga perlu spesifikasi serta batas integrasi tersendiri sebelum pekerjaan lintas modul.

## 5. Security dan Role Assessment

| Role | Baca MM-05 | Mutasi MM-05 | Hasil inspeksi |
|---|---|---|---|
| PROCUREMENT | Ya | Ya | Diizinkan oleh authorization server-side |
| ADMIN_BPM | Ya | Tidak | Mutasi langsung ditolak |
| ADMIN_PRODUKSI | Ya | Tidak | Read-only |
| ASST_MANAGER_PRODUKSI | Ya | Tidak | Read-only |
| MARKETING | Tidak | Tidak | Tidak ada izin eksplisit |
| DESIGNER | Tidak | Tidak | Tidak ada izin eksplisit |
| Tanpa autentikasi | Tidak | Tidak | Route memakai `auth` |

Validasi server-side dan unique constraints menangani input kode, SKU, tipe nilai, referensi, dan duplikasi. Eloquent/query builder memakai parameter binding pada query yang ditinjau; tidak ditemukan interpolasi input request ke SQL. Mass assignment dikurangi dengan pemilihan field request dan data hasil validasi sebelum persistensi. CSRF termasuk pada web middleware group. Tidak ditemukan bukti pengujian browser baru dalam audit ini.

R1 adalah cacat integritas dan otorisasi Procurement yang sah, bukan jalur peningkatan hak akses; namun perlu ditutup agar endpoint mutasi tidak dapat menghasilkan referensi lintas kategori yang salah.

## 6. Dependency dan Scope MM-02.2

| Area fungsional | Status | Bukti dan batasan |
|---|---|---|
| 1. Spesifikasi material dan definisi atribut | READY WITH FINDINGS | CRUD definisi, tipe text/number/boolean, kategori, satuan number, status aktif, validasi unik tersedia. Aturan bisnis formal MM-02.2 belum ditemukan/dikonfirmasi. |
| 2. SKU dan pengelolaan varian | BLOCKED | CRUD varian dan validasi SKU tersedia, tetapi perubahan parent dapat membawa nilai kategori lama (R1). |
| 3. Nilai atribut varian | READY WITH FINDINGS | Typed values, relasi, validasi kategori, duplikasi, kewajiban, dan transaksi tersedia; R1 membiarkan nilai tersimpan tidak konsisten setelah parent diganti. |
| 4. Pencegahan duplikasi dan referensi invalid | READY WITH FINDINGS | Unique indexes dan validasi server ada. Invariant silang kategori belum ditegakkan oleh FK/schema dan perlu semua jalur mutasi dijaga. |
| 5. Procurement authorization dan batas role | READY | HTTP routes menggunakan `auth`; pemeriksaan baca/mutasi server-side mengikuti role matrix yang diperiksa. |
| 6. Automated regression/security testing | READY WITH FINDINGS | Dua focused suite saat ini lulus 14 test/201 assertion. Tambahkan regression untuk re-parent varian dan verifikasi parent/nilai tidak berubah. |
| 7. Supplier dan price-reference dependencies | NOT IN SCOPE | Model, migration, dan service fondasi tersedia; CRUD UI/route belum ada. HPP integration memerlukan spesifikasi dan persetujuan terpisah. |

Status di atas menilai fondasi yang ada, bukan persetujuan atas spesifikasi produk. Jangan menambah enum, aturan bisnis, event log, atau alur HPP berdasarkan asumsi.

## 7. Test Coverage dan Hasil Pemeriksaan

| Pemeriksaan | Hasil | Keterangan |
|---|---|---|
| MM authorization dan specification focused tests | PASS | 14 tests, 201 assertions, selesai dalam 758 ms |
| PHP syntax MM | PASS | 22 file PHP: controller, model, service, feature test, dan migration |
| Route check | PASS | 22 route; `web` dan `auth` |
| Scoped Pint | PASS | `--test` pada tiga file PHP hasil kerja audit sebelumnya; tidak menulis file |
| Scoped whitespace | PASS WITH FINDING | Tidak ada trailing whitespace pada file MM yang diperiksa |
| `git diff --check` | FAIL (pre-existing) | Menemukan trailing whitespace baseline di `resources/views/prod03/briefs/index.blade.php:90`; tidak diperbaiki karena di luar scope |
| Full test suite | NOT RUN | Audit dibatasi pada focused MM test; tidak ada migration dijalankan |
| Browser E2E | NOT RUN | Hasil browser terdahulu tidak diulang pada audit ini |

Test MM menggunakan `DatabaseTransactions`, bukan `RefreshDatabase`; test yang dijalankan tidak meminta migration. `tests/bootstrap.php` memaksa environment test dan memeriksa permission file `.env.testing` tanpa mencetak isinya. File tersebut hanya diperiksa metadata-nya: regular file, mode `600`, 447 byte. Config cache tidak ada. `Tests\TestCase` memeriksa konfigurasi efektif dan identitas/hak database PostgreSQL terhadap database serta role test yang diharapkan sebelum test dimulai. Semua focused test lulus tanpa guard gagal.

## 8. Database Safety

- Database automated test: `enterprise_bpm_test`, PostgreSQL lokal, role `enterprise_bpm_test_user`, sesuai guard test.
- Test berjalan dalam transaksi; migration tidak dijalankan.
- Tidak ada query atau operasi yang diarahkan ke `enterprise_bpm_browser` atau `enterprise_bpm`.
- Tidak ada perubahan `.env`, `.env.testing`, atau konfigurasi database.
- Tidak ada data aplikasi dihapus atau dimutasi di database browser/produksi.

## 9. Rekomendasi Urutan Implementasi

1. Dapatkan persetujuan aturan resmi untuk mengganti material parent varian yang telah memiliki nilai spesifikasi atau data quote/harga.
2. Dalam perubahan terpisah yang disetujui, tutup R1 dengan guard server-side yang konsisten dan feature test HTTP yang membuktikan seluruh data tetap utuh setelah penolakan.
3. Sepakati dokumen/scope MM-02.2, khususnya tipe atribut, satuan, perilaku nonaktif, edit definisi yang sudah dipakai, dan apakah audit event append-only termasuk.
4. Verifikasi setiap endpoint Procurement, read-only roles, akses tanpa autentikasi, validasi duplikasi/referensi, dan persistence dengan focused tests pada database test terisolasi.
5. Bahas CRUD supplier/harga dan kontrak HPP sebagai milestone terpisah; jangan mengubah modul HPP/PROD dalam pekerjaan MM tanpa persetujuan.

### Acceptance criteria proposal

- Parent material tidak dapat diubah dengan cara yang membuat nilai spesifikasi berbeda kategori; kebijakan yang disetujui juga berlaku pada reference quote/harga.
- Request yang ditolak tidak mengubah parent varian maupun nilai spesifikasi.
- PROCUREMENT dapat melakukan tindakan yang disetujui; role baca tidak dapat mutasi melalui direct endpoint; role lain dan guest ditolak.
- Duplikasi dan referensi tidak valid tetap ditolak pada server-side; status nonaktif tetap mempertahankan data referensi.
- Test mencakup regression R1, sukses create/update yang disetujui, authorization, invalid input, serta persistensi.
- Database browser dan produksi tidak digunakan oleh automated test; tidak ada migration dijalankan tanpa persetujuan khusus.

## 10. Keputusan Akhir

**BLOCKED — REQUIRES REMEDIATION**

MM-02.2 belum aman dilanjutkan karena R1 memungkinkan varian yang memiliki nilai spesifikasi dipindahkan ke material kategori lain dan meninggalkan nilai lama tetap terhubung. Tindakan berikutnya yang direkomendasikan adalah menyetujui kebijakan perpindahan parent, kemudian melakukan perbaikan dan regression test terisolasi untuk R1. Implementasi MM-02.2 tidak dimulai dalam audit ini dan menunggu persetujuan eksplisit pengguna.
