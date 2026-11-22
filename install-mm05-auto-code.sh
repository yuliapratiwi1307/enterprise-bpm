#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(pwd)"
EXPECTED_CONTROLLER="app/Http/Controllers/MmMaterialWizardController.php"
EXPECTED_VIEW="resources/views/mm05/material-wizard.blade.php"

if [[ ! -f "artisan" || ! -f "$EXPECTED_CONTROLLER" || ! -f "$EXPECTED_VIEW" ]]; then
  echo "ERROR: Jalankan installer dari root proyek Laravel yang berisi artisan."
  exit 1
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP=".mm05-auto-code-backup-$STAMP"
mkdir -p "$BACKUP/app/Http/Controllers" "$BACKUP/resources/views/mm05"

cp "$EXPECTED_CONTROLLER" "$BACKUP/app/Http/Controllers/"
cp "$EXPECTED_VIEW" "$BACKUP/resources/views/mm05/"

cp -f "app/Http/Controllers/MmMaterialWizardController.php" "$EXPECTED_CONTROLLER"
cp -f "resources/views/mm05/material-wizard.blade.php" "$EXPECTED_VIEW"

echo "Backup tersimpan di: $APP_ROOT/$BACKUP"
echo "Memeriksa sintaks PHP..."
php -l "$EXPECTED_CONTROLLER"

echo "Membersihkan cache view..."
php artisan view:clear

echo
echo "Patch kode material otomatis berhasil dipasang."
echo "Kode baru akan dibuat saat simpan: MAT-000001, MAT-000002, dan seterusnya."
echo "Tidak ada migration yang dijalankan dan tidak ada data yang dihapus."
