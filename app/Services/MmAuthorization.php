<?php

namespace App\Services;

use Illuminate\Http\Request;

final class MmAuthorization
{
    /**
     * Roles allowed to read the Material Master catalog and procurement workspace.
     * ADMIN_BPM retains oversight; production roles are read-only consumers.
     */
    private const VIEW_ROLES = [
        'ADMIN_BPM',
        'PROCUREMENT',
        'ASST_MANAGER_PRODUKSI',
        'ADMIN_PRODUKSI',
    ];

    /** ADMIN_BPM and Procurement can manage master data and price-bank records. */
    private const MANAGE_ROLES = [
        'ADMIN_BPM',
        'PROCUREMENT',
    ];

    private const QUOTE_APPROVAL_ROLES = [
        'ADMIN_BPM',
        'PROCUREMENT',
    ];

    public static function canView(?object $user): bool
    {
        return $user !== null
            && $user->roles()->whereIn('code', self::VIEW_ROLES)->exists();
    }

    public static function canManage(?object $user): bool
    {
        return $user !== null
            && $user->roles()->whereIn('code', self::MANAGE_ROLES)->exists();
    }

    public static function canApproveQuotes(?object $user): bool
    {
        return $user !== null
            && $user->roles()->whereIn('code', self::QUOTE_APPROVAL_ROLES)->exists();
    }

    public static function authorizeView(Request $request): void
    {
        abort_unless(self::canView($request->user()), 403,
            'Anda tidak memiliki akses untuk melihat Master Material.');
    }

    public static function authorizeManage(Request $request): void
    {
        abort_unless(self::canManage($request->user()), 403,
            'Hanya ADMIN_BPM dan Procurement yang dapat mengelola Master Material dan Price Bank.');
    }

    public static function authorizeQuoteApproval(Request $request): void
    {
        abort_unless(self::canApproveQuotes($request->user()), 403,
            'Hanya ADMIN_BPM dan Procurement yang dapat menyetujui atau menolak penawaran.');
    }
}
