Patch F12-E: calendario PROD-04 libur nasional Indonesia 2026–2027
Dokumen acuan (lampiran A hari libur nasional, bukan lampiran B cuti bersama):
https://www.kemenkopmk.go.id/sites/default/files/pengumuman/2025-09/SKB%20Libur%20Nasional%20dan%20Cuti%20Bersama%20Tahun%202026.pdf
https://kemenkopmk.go.id/sites/default/files/pengumuman/2026-09/SKB%20Libur%20Nasional%20dan%20Cuti%20Bersama%20Tahun%202027.pdf

Verifikasi berdasarkan dokumen resmi tertanggal 22 Oktober 2026.
17 tanggal nasional untuk 2026, 18 tanggal nasional untuk 2027 (berdasarkan SKB).
Perubahan SKB / penetapan baru harus diperiksa dan dataset+checksum direvisi secara sadar.

Ekstrak dengan cara aman ke repo setelah menyimpan backup file yang ada.
Tidak ada migration, provisioning, update DB, server atau commit yang dijalankan.
Jalankan validasi di Mac pada repo: php -l tiga file, vendor/bin/pint --test path..., kemudian unit-test dengan memastikan guard DB aman.
