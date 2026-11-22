<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Prod03FoundationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('bpm_roles')->updateOrInsert(
            ['code' => 'DESIGNER'],
            [
                'name' => 'Designer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
