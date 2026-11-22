<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('database.default') !== 'pgsql' || (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test')) && ! (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: hanya database pengujian SJR');
        }
        Schema::table('sjr_attachments', function (Blueprint $t) {
            $t->string('attachment_note', 1000)->nullable();
        });
    }

    public function down(): void
    {
        if (! (config('database.connections.pgsql.database') === 'enterprise_bpm_browser' || (app()->environment('testing') && config('database.connections.pgsql.database') === 'enterprise_bpm_test'))) {
            throw new RuntimeException('STOP: bukan database browser');
        }
        Schema::table('sjr_attachments', function (Blueprint $t) {
            $t->dropColumn('attachment_note');
        });
    }
};
