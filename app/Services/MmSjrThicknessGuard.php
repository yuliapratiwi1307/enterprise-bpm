<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class MmSjrThicknessGuard
{
    public function assertCompatible(array $row): void
    {
        $thickness = $row['attributes']['thickness_mm'] ?? null;
        if ($thickness === null || trim((string) $thickness) === '') return;
        $snapshot = $row['mm05_snapshot'] ?? [];
        $text = trim((string) ($snapshot['label'] ?? '').' '.(string) ($snapshot['spec'] ?? ''));
        if (!preg_match('/(?:^|[^0-9])([0-9]+(?:[.,][0-9]+)?)\s*mm\b/iu', $text, $match)) return;
        $master = (float) str_replace(',', '.', $match[1]);
        $requested = str_replace(',', '.', trim((string) $thickness));
        if (!is_numeric($requested) || abs((float) $requested - $master) > 0.00001) {
            throw ValidationException::withMessages(['materials' => 'Ketebalan kebutuhan '.(string) $thickness.' mm berbeda dari referensi MM-05 '.$master.' mm. Sesuaikan material atau kebutuhan sebelum menyimpan/finalisasi.']);
        }
    }
}
