#!/bin/bash
set -euo pipefail

PROJECT_ROOT="$(pwd)"
PATCH_ROOT="$(cd "$(dirname "$0")" && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$PROJECT_ROOT/.mm05-redesign-backup-$STAMP"

if [[ ! -f "$PROJECT_ROOT/artisan" || ! -d "$PROJECT_ROOT/app/Http/Controllers" ]]; then
    echo "ERROR: Jalankan script dari direktori utama proyek Laravel (folder yang berisi artisan)."
    exit 1
fi

for rel in \
    "app/Http/Controllers/MmMaterialWizardController.php" \
    "resources/views/mm05/material-wizard.blade.php"
do
    if [[ ! -f "$PATCH_ROOT/$rel" ]]; then
        echo "ERROR: File patch tidak ditemukan: $PATCH_ROOT/$rel"
        exit 1
    fi
done

mkdir -p "$BACKUP_DIR/app/Http/Controllers" "$BACKUP_DIR/resources/views/mm05"
cp "$PROJECT_ROOT/app/Http/Controllers/MmMaterialWizardController.php" "$BACKUP_DIR/app/Http/Controllers/" 2>/dev/null || true
cp "$PROJECT_ROOT/resources/views/mm05/material-wizard.blade.php" "$BACKUP_DIR/resources/views/mm05/" 2>/dev/null || true

mkdir -p "$PROJECT_ROOT/app/Http/Controllers" "$PROJECT_ROOT/resources/views/mm05"
cp "$PATCH_ROOT/app/Http/Controllers/MmMaterialWizardController.php" "$PROJECT_ROOT/app/Http/Controllers/"
cp "$PATCH_ROOT/resources/views/mm05/material-wizard.blade.php" "$PROJECT_ROOT/resources/views/mm05/"

php -l "$PROJECT_ROOT/app/Http/Controllers/MmMaterialWizardController.php"
php artisan view:clear

echo ""
echo "Patch MM05 berhasil dipasang."
echo "Backup file lama: $BACKUP_DIR"
echo "Tidak ada migration atau perubahan database yang dijalankan."
