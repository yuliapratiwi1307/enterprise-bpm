<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Prod02FoundationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->updateOrInsert(['code'=>'MARKETING'], ['name'=>'Marketing','created_at'=>now(),'updated_at'=>now()]);
        DB::table('bpm_roles')->updateOrInsert(['code'=>'MARKETING'], ['name'=>'Marketing','created_at'=>now(),'updated_at'=>now()]);
    }
}
