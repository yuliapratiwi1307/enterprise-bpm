<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignMarketingRole extends Command
{
    protected $signature = 'bpm:assign-marketing {email}';
    protected $description = 'Grant MARKETING role to an existing local user';

    public function handle(): int
    {
        $user = DB::table('users')->where('email', $this->argument('email'))->first();
        if (!$user) { $this->error('User tidak ditemukan. Buat akun dahulu.'); return self::FAILURE; }
        $roleId = DB::table('bpm_roles')->where('code','MARKETING')->value('id');
        if (!$roleId) { $this->error('Jalankan migration dan seeder dahulu.'); return self::FAILURE; }
        DB::table('bpm_role_user')->updateOrInsert(['user_id'=>$user->id,'bpm_role_id'=>$roleId], []);
        $this->info('Role Marketing ditetapkan untuk ' . $user->email);
        return self::SUCCESS;
    }
}
