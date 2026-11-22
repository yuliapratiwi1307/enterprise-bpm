# MM-02.2 Readiness Reassessment

Tanggal: 9 November 2026

Modul: MM-05 Master Material

## 1. Ringkasan

Fitur inti definisi spesifikasi, varian/SKU, nilai atribut, Procurement authorization, dan UI dasar telah ada dan focused regression terkini lulus 18 tests/222 assertions. Defect R1 untuk request berurutan telah ditutup dengan Parent Lock.

Namun, Parent Lock melakukan pemeriksaan `attributes()->exists()` dan update parent tanpa transaction/lock bersama dengan penulisan nilai spesifikasi. `setAttributes()` mempunyai transaction tersendiri, tetapi tidak mengunci row varian. Foreign key menjaga keberadaan relasi dan unique constraint mencegah pasangan varian/definisi ganda; keduanya tidak menjaga kecocokan kategori lintas tabel. Karena itu masih ada race condition simultan yang belum diuji atau dicegah.

Selain itu, tidak ditemukan spesifikasi/roadmap MM-02.2 formal yang disahkan pada dokumen yang ditinjau. Keputusan scope untuk histori audit dan perubahan parent varian yang telah mempunyai quote/harga juga belum terdokumentasi. MM-02.2 **belum mendapat readiness gate untuk mulai implementasi**.

## 2. Disposisi Temuan Readiness Sebelumnya

| Temuan sebelumnya | Status | Bukti/disposisi terkini |
|---|---|---|
| R1: re-parent varian menyisakan nilai spesifikasi kategori lama | **RESOLVED** untuk operasi berurutan | Parent Lock menolak perubahan `material_id` jika relasi nilai sudah ada; current HTTP regression lulus. |
| R2: invariant kategori tidak ditegakkan FK/schema dan bergantung pada aplikasi | **OPEN** | Guard menutup jalur sequential yang diaudit. Race simultan masih dimungkinkan; tidak ada constraint lintas tabel atau mutual locking yang diamati. |
| R3: audit actor/timestamp bukan event log append-only | **OPEN** | Masih benar pada schema yang ditinjau. Perlu dipastikan apakah event history merupakan acceptance MM-02.2; jangan ditambahkan tanpa scope approval. |
| R4: supplier, quote, harga dan HPP belum menjadi alur UI terintegrasi | **NOT APPLICABLE** untuk fungsi inti spesifikasi/varian MM-02.2 yang saat ini ditinjau; **OPEN** sebagai dependency milestone supplier/pricing | Schema/model/service ada; CRUD controller/route/UI belum ada. HPP tidak diubah dan integrasi memerlukan scope tersendiri. |
| Spesifikasi MM-02.2 formal belum ditemukan | **BLOCKED** untuk finalisasi acceptance bisnis | Source dan audit lama tidak menggantikan persetujuan aturan bisnis formal. |
| Full suite dan browser E2E tidak diverifikasi pada audit sebelumnya | **OPEN** | Dua suite focused MM lulus ulang; full suite/browser tidak dijalankan pada reassessment ini. Jangan klaim full regression. |

## 3. Readiness Teknis Terkini

### Database dan relasi

Migration fondasi mendefinisikan FK `material -> category`, `attribute definition -> category/unit`, `variant -> material/base unit`, dan `variant attribute -> variant/definition`, serta unique SKU dan unique pasangan variant/definition. Supplier quote dan material price juga terhubung ke varian, unit, supplier, dan source quote.

Constraint ini memastikan referensi terhubung tidak hilang dan pasangan atribut-varian tidak diduplikasi. Constraint tidak menghubungkan category dari material dengan category dari attribute definition dalam satu constraint. Kolom `data_type` dan unit dimension juga berupa string, sementara enum diterapkan di service. Tidak ada migration dijalankan pada reassessment ini.

### Attribute definitions dan variants

CRUD definisi atribut dan varian, nilai text/number/boolean, validasi kategori, satuan number, required fields, status aktif, SKU unik, serta proteksi Parent Lock telah terhubung pada service/controller dan diuji di focused tests. Re-parent ke material aktif hanya diizinkan jika belum ada nilai atribut. Perilaku re-parent terhadap quote/harga yang sudah melekat pada varian belum mempunyai keputusan bisnis/test khusus.

### Authorization dan UI

`MmAuthorization` memberi baca kepada `ADMIN_BPM`, `PROCUREMENT`, `ADMIN_PRODUKSI`, `ASST_MANAGER_PRODUKSI`; hanya `PROCUREMENT` yang dapat mutasi. Route terkini berjumlah 22 dengan middleware `web` dan `auth`, dan feature tests menguji direct mutasi untuk role read-only serta role tanpa izin.

UI material, kategori/satuan, spesifikasi, dan varian tersedia. Bagian supplier/quote/harga pada workspace hanya ringkasan/anchor; tidak ada form CRUD route terverifikasi. Source/UI diperiksa, tetapi browser tidak dijalankan pada reassessment ini.

### Supplier, harga, dan cross-module boundary

`MmSupplierQuoteService` serta `MmMaterialPriceService` berisi validasi relasi aktif, nilai/tanggal, dan kecocokan quote saat pencatatan harga. Tidak ada endpoint/UI CRUD yang terverifikasi. Quote dan price melekat pada variant ID sehingga data tetap mengikuti record varian jika material parent berubah. Belum ada aturan bisnis yang menetapkan boleh/tidaknya re-parent varian tanpa spesifikasi tetapi dengan histori quote/harga.

Tidak ada bukti pada audit ini untuk kontrak integrasi HPP, pemilihan harga efektif, atau alur pengiriman data ke HPP. Perluasannya bukan bagian dari pekerjaan ini dan tidak boleh diasumsikan sebagai scope MM-02.2.

## 4. R1 Concurrency Risk Assessment

**Klasifikasi: MEDIUM — OPEN**

### Dasar teknis

1. `MmVariantService::setAttributes()` memulai `DB::transaction()` untuk membaca definisi/kategori lalu menyimpan nilai atribut.
2. Method tersebut tidak mengambil lock eksplisit pada row `mm_material_variants`.
3. `updateVariant()` memeriksa keberadaan atribut melalui query terpisah, kemudian mengubah model; pengecekan dan update tidak berada di transaksi/lock yang sama dengan penulisan atribut.
4. Dalam interleaving tertentu, request update dapat melihat belum ada atribut lalu menerapkan parent baru, sementara request penulisan atribut telah memvalidasi kategori parent lama dan menyimpan nilai sesudah pemeriksaan tersebut.
5. FK dan unique pair membatasi keberadaan dan duplikasi, bukan validitas kategori parent/definisi. Transaksi `setAttributes()` hanya mengatomikkan penulisan dalam request itu sendiri; tidak menyerialisasi keputusan parent dari request lain.

Risiko diberi MEDIUM karena memerlukan operasi simultan pada varian yang sama dalam jendela waktu sempit, tetapi dampaknya adalah kemungkinan inkonsistensi data dan test sequential tidak mendeteksinya. Tidak ada race test yang dijalankan.

### Rekomendasi terpisah, tanpa implementasi pada audit ini

Sebelum menyatakan invariant kategori race-safe, gunakan mekanisme serialisasi konsisten untuk kedua jalur perubahan (nilai dan parent), atau pertimbangkan invariant database yang tepat. Jika pendekatan locking dipilih, kedua operasi harus mengambil lock row parent yang sama di dalam transaksi sebelum membaca kategori/relasi; perubahan harus menolak atau memvalidasi ulang terhadap keadaan terkini. Pastikan penanganan exception/rollback tidak meninggalkan sebagian nilai.

Acceptance criteria untuk proteksi concurrency:

- Integration/feature test memakai dua koneksi transaksi independen dan koordinasi interleaving yang deterministik.
- Saat operasi add-spec dan re-parent bertabrakan, hasil akhirnya terserialisasi: parent change ditolak atau add-spec divalidasi terhadap parent final; tidak ada definisi atribut beda kategori.
- Kasus request sequential yang sudah lulus tetap lulus.
- Kegagalan/penolakan tidak menghapus atau mengubah nilai yang telah committed.
- Database test tetap `enterprise_bpm_test`; tidak ada migration atau uji ke browser/production DB.

Project owner perlu menyetujui implementasi proteksi tersebut atau secara eksplisit menerima risiko MEDIUM sebelum menggunakan invariant ini sebagai jaminan race-free. Tidak ada locking, refactor, atau source change dilakukan pada task ini.

## 5. Dependency dan Disposisi Scope

| Area | Status | Catatan |
|---|---|---|
| Material specifications dan attribute definitions | **RESOLVED** sebagai fondasi yang ada; acceptance scope **BLOCKED** pada spesifikasi formal | CRUD dan validasi tersedia; formal business rules MM-02.2 belum ditemukan. |
| Material variant dan SKU | **OPEN** | CRUD/SKU tersedia; sequential Parent Lock lulus, residual concurrency race dan aturan perpindahan dengan quote/harga belum diputuskan. |
| Variant attribute values | **OPEN** | Typed values, relasi, validasi, dan transaksi request tersedia; mutual serialization dengan re-parent belum ada. |
| Duplicate/invalid reference prevention | **RESOLVED** untuk jalur yang diuji; **OPEN** untuk invariant lintas kategori saat concurrency | Unique constraints dan validation tests ada; DB belum menegakkan kategori match lintas tabel. |
| Procurement authorization/read-only roles | **RESOLVED** untuk route HTTP yang diuji | Matriks role tidak berubah; current focused tests lulus. |
| Supplier model/service | **NOT APPLICABLE** untuk scope spesifikasi/varian; **OPEN** untuk milestone supplier | Fondasi ada, UI/controller CRUD tidak ada. |
| Supplier quote dependency | **OPEN** untuk kebijakan lifecycle parent variant | FK menyimpan quote di variant; re-parent tanpa attribute namun dengan quote belum ditentukan. |
| Material price dependency | **OPEN** untuk kebijakan lifecycle parent variant | FK menyimpan price di variant; treatment historical price pada re-parent belum ditentukan. |
| Authorization surface | **RESOLVED** pada 22 route terdaftar | `web` + `auth`, server-side role check, direct mutation tests. |
| Existing UI | **RESOLVED** untuk alur dasar yang ada; browser behavior **OPEN/UNVERIFIED** | Source/form dan HTTP behavior diperiksa; E2E visual tidak diulang. |
| Testing environment isolation | **RESOLVED** untuk focused tests yang dijalankan | Test guards lulus pada `enterprise_bpm_test`; browser/production DB tidak digunakan. |
| Audit event/history | **OPEN** sebagai keputusan scope | Provenance tersedia; append-only history tidak ditemukan dan belum disahkan sebagai requirement. |
| HPP/cross-module integration | **NOT APPLICABLE** pada audit ini | Tidak ada kontrak HPP yang diverifikasi; jangan mengubah HPP/PROD atau modul lain. |

## 6. Reconciled Verification

- Current focused tests: **18 passed, 222 assertions** (`MmSpecificationTest` + `MmAuthorizationTest`).
- Current MM route check: **22 routes**, seluruhnya menampilkan middleware `web` dan `auth`.
- Current syntax check: service dan specification test tanpa syntax error.
- Database isolation prerequisites: `.env.testing` hanya diperiksa metadata-nya (regular file, mode `600`, 447 bytes); config cache absent; test guard memvalidasi config/identitas efektif sebelum test setup.
- Backup integrity: 31-file baseline backup masih ada; manifest 38 entries dan checksum diverifikasi. Dua file backup sebelum edit R1 juga lolos verifikasi checksum.
- Tidak ada migration, full suite, browser E2E, atau test konkurensi yang dijalankan pada reassessment ini.
- Tidak ada source, test, route, migration, role, atau konfigurasi yang diubah oleh task reassessment ini.

## 7. Usulan Urutan Sebelum MM-02.2

1. Project owner mengesahkan spesifikasi dan acceptance MM-02.2, termasuk cakupan histori audit, aturan nilai/definisi yang sudah dipakai, dan apakah UI supplier/price termasuk atau tetap milestone terpisah.
2. Project owner memutuskan residual concurrency risk MEDIUM: menyetujui mitigasi race dan test concurrency, atau secara eksplisit menerima risiko dengan scope/batasan yang dicatat.
3. Tetapkan aturan re-parent varian yang sudah mempunyai quote/harga, terpisah dari Parent Lock atas specification values.
4. Setelah approval, susun implementasi kecil per area; jangan mengubah HPP/PROD atau permission tanpa spesifikasi dan persetujuan tersendiri.
5. Jalankan focused tests di database test terisolasi, termasuk role, invalid references, persistence, dan concurrency bila disetujui. Jangan menonaktifkan migration guard.

## 8. Proposed Acceptance Criteria

- Spesifikasi MM-02.2 resmi disetujui dan mendefinisikan data, validasi, status aktif, serta behavior update untuk definisi yang sudah dipakai.
- Parent Lock sequential dan seluruh regression 18/222 yang saat ini pass tetap pass.
- Kebijakan concurrency disetujui; bila mitigasi dipilih, concurrent spec-write/re-parent tidak dapat menghasilkan data lintas kategori.
- Re-parent saat quote/harga ada mempunyai kebijakan eksplisit dan test sesuai kebijakan.
- Procurement dapat melakukan mutasi yang disetujui; role baca dan user tanpa izin tidak dapat melakukan mutasi melalui endpoint langsung.
- Nilai spesifikasi tetap dapat dibuat/dibaca; duplikasi dan referensi invalid ditolak; data historis tidak hilang saat deactivate/update yang diizinkan.
- Test hanya menulis ke database test yang terisolasi; tidak ada migration ke browser/production tanpa izin khusus.
- Scope supplier/price/HPP dinyatakan eksplisit sebagai termasuk atau di luar milestone sebelum integrasi lintas modul dimulai.

## 9. Final Readiness Decision

# BLOCKED — REQUIRES REMEDIATION

MM-02.2 belum mendapat gate implementasi karena tidak ditemukan spesifikasi MM-02.2 formal yang disahkan, dan Parent Lock belum menjamin invariant kategori pada request yang berjalan bersamaan. Project owner juga perlu menetapkan kebijakan quote/harga ketika parent varian dipindah. Ini bukan klaim bahwa fitur sequential R1 gagal: Parent Lock dan regression terkait lulus. Keputusan ini memisahkan hasil tersebut dari risiko konkurensi dan kebutuhan persetujuan scope yang belum selesai.

Rekomendasi berikutnya: sahkan scope MM-02.2 dan putuskan mitigasi atau penerimaan eksplisit untuk risiko concurrency sebelum memberi approval implementasi. Tidak ada MM-02.2 yang dimulai pada reassessment ini.

## Addendum: Reassessment Setelah Approval Owner

Tanggal addendum: 9 November 2026

Bagian di atas adalah rekaman readiness sebelum owner menyetujui mitigasi concurrency dan lifecycle rule parent varian. Bagian tersebut dipertahankan sebagai riwayat; status paling baru berada di [MM-02.2 Final Readiness Gate](./MM02-2_FINAL_READINESS_GATE.md). Keputusan scope yang disetujui dan kriteria inti diformalkan pada [MM-02.2 Master Material Core Specification](../specifications/MM02-2_MASTER_MATERIAL_CORE_SPEC.md).

### Disposisi terbaru atas temuan

| Temuan terdahulu | Disposisi setelah remediasi | Bukti atau batas |
|---|---|---|
| Race spec-write melawan re-parent | **RESOLVED untuk kedua interleaving yang diuji** | Kedua jalur mengambil `FOR UPDATE` pada row varian dalam transaksi. Dua test memakai proses/koneksi PostgreSQL terpisah dan mengamati wait event `Lock` sebelum melepas transaksi pertama. |
| Parent lifecycle saat ada spec/quote/price history | **RESOLVED pada service write paths yang diaudit** | Re-parent memeriksa ketiga relasi setelah row varian dikunci; record quote/price menggunakan lock yang sama. Focused MM suite lulus. |
| Kategori material/definisi berubah bersamaan dengan spec write | **OPEN — finding** | Update kategori masih memakai existence guard tanpa lock bersama dengan row variant. Tidak diubah atau dicakup oleh concurrency tests terbaru. |
| Constraint kategori lintas tabel | **OPEN** | FK/unique tetap menjaga referential integrity, bukan kesamaan kategori material dan definisi; tidak ada migration/schema change. |
| Audit event append-only | **OPEN / NOT IN APPROVED CORE SCOPE** | Tidak ada event log append-only yang ditambahkan; actor/timestamp provenance yang ada tetap dipertahankan. |
| Supplier/quote/price CRUD UI dan HPP | **NOT IN SCOPE** | Hanya write service quote/price yang ikut diserialisasi untuk lifecycle parent. CRUD/UI dan HPP tidak diimplementasikan. |
| Full suite dan browser E2E | **OPEN / UNVERIFIED** | Hanya focused specification, authorization, dan concurrency tests dijalankan. |
| Spesifikasi bisnis formal | **RESOLVED untuk keputusan yang disetujui; detail lain tetap perlu approval** | Scope inti dan lifecycle parent dicatat dalam dokumen spesifikasi baru. Detail yang belum disetujui ditandai sebagai usulan, bukan aturan final. |

Focused regression terkini lulus **25 tests / 257 assertions** pada `enterprise_bpm_test`. Tidak ada migration dijalankan. Keputusan terbaru adalah **READY WITH FINDINGS**, bukan pernyataan bebas risiko atau bukti full application regression. Rincian temuan dan urutan tindakan ada pada final readiness gate.
