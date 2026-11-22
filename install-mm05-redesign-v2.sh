#!/usr/bin/env bash
set -euo pipefail

ROOT="$(pwd)"
PATCH_DIR="$ROOT/mm05-redesign-v2-patch"
CONTROLLER="app/Http/Controllers/MmMaterialWizardController.php"
VIEW="resources/views/mm05/material-wizard.blade.php"
MIGRATION="database/migrations/2026_11_12_092200_create_mm_supplier_contacts_table.php"

if [[ ! -f artisan ]]; then
  echo "ERROR: Jalankan script dari root proyek Laravel (folder yang berisi artisan)."
  exit 1
fi
if [[ ! -f "$PATCH_DIR/$CONTROLLER" || ! -f "$PATCH_DIR/$VIEW" || ! -f "$PATCH_DIR/$MIGRATION" ]]; then
  echo "ERROR: Folder patch tidak lengkap: $PATCH_DIR"
  echo "Ekstrak seluruh ZIP ke root proyek, lalu jalankan ulang."
  exit 1
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP="$ROOT/.mm05-redesign-v2-backup-$STAMP"
mkdir -p "$BACKUP/app/Http/Controllers" "$BACKUP/resources/views/mm05" "$BACKUP/database/migrations"

cp "$CONTROLLER" "$BACKUP/app/Http/Controllers/"
cp "$VIEW" "$BACKUP/resources/views/mm05/"
if [[ -f "$MIGRATION" ]]; then
  cp "$MIGRATION" "$BACKUP/database/migrations/"
  echo "Migration sudah ada; file akan diperbarui dari patch."
fi

cp "$PATCH_DIR/$CONTROLLER" "$CONTROLLER"
cp "$PATCH_DIR/$VIEW" "$VIEW"
cp "$PATCH_DIR/$MIGRATION" "$MIGRATION"

echo "Backup: $BACKUP"
echo "Memeriksa sintaks PHP..."
php -l "$CONTROLLER"
php -l "$MIGRATION"

echo "Menjalankan migration kontak supplier..."
if ! php artisan migrate --force; then
  echo "Migration gagal. File aplikasi telah diperbarui; backup tersedia di $BACKUP."
  echo "Jangan lanjut uji simpan. Kirim output error migration untuk diperbaiki."
  exit 1
fi

php artisan view:clear
php artisan optimize:clear

echo
echo "MM-05 redesign v2 berhasil dipasang."
echo "Fitur: kode otomatis tersembunyi, varian tambahan, mode supplier eksklusif, dan beberapa nomor kontak."
echo "Backup tersimpan di: $BACKUP"
