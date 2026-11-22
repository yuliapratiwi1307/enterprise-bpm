<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || (! (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')) && ! (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test'))) {
            throw new RuntimeException('V5.8.2 hanya untuk PostgreSQL enterprise_bpm_browser.');
        }
        Schema::create('sjr_multi_item_technical_finalizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('handoff_id')->unique();
            $table->string('state', 30)->default('READY_FOR_HPP');
            $table->unsignedInteger('item_count');
            $table->jsonb('technical_snapshot_json');
            $table->unsignedBigInteger('finalized_by');
            $table->timestamp('finalized_at');
            $table->timestamps();
            $table->foreign('handoff_id')->references('id')->on('sjr_multi_item_handoffs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql' || (! (DB::connection()->getDatabaseName() === 'enterprise_bpm_browser' || (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test')) && ! (app()->environment('testing') && DB::connection()->getDatabaseName() === 'enterprise_bpm_test'))) {
            throw new RuntimeException('Rollback V5.8.2 hanya untuk database browser.');
        }
        Schema::dropIfExists('sjr_multi_item_technical_finalizations');
    }
};
