<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BpmRoleAuthorization
{
    public function has(User $user, string $role): bool
    {
        return DB::table('bpm_role_user as role_user')
            ->join('bpm_roles as role', 'role.id', '=', 'role_user.bpm_role_id')
            ->where('role_user.user_id', $user->id)
            ->where('role.code', $role)
            ->exists();
    }

    public function require(User $user, string $role): void
    {
        abort_unless($this->has($user, $role), 403, "Akses ini hanya untuk {$role}.");
    }
}
