<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('sjr_jobs', 'brief_details_html')) {
            Schema::table('sjr_jobs', function (Blueprint $table) {
                $table->text('brief_details_html')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Additive safety: never remove stored formatted briefs automatically.
    }
};
