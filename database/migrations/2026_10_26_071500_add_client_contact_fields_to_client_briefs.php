<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->string('client_type', 100)->nullable();
            $table->text('client_address')->nullable();
            $table->string('client_position', 255)->nullable();
            $table->string('client_email', 255)->nullable();
            $table->string('client_phone', 50)->nullable();
            $table->text('client_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->dropColumn([
                'client_type',
                'client_address',
                'client_position',
                'client_email',
                'client_phone',
                'client_notes',
            ]);
        });
    }
};
