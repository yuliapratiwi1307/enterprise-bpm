<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->string('project_name', 255)->nullable();
            $table->string('project_type', 255)->nullable();
            $table->string('brand', 255)->nullable();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->string('priority', 30)->nullable();
            $table->text('project_objectives')->nullable();
            $table->text('project_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_briefs', function (Blueprint $table) {
            $table->dropColumn([
                'project_name',
                'project_type',
                'brand',
                'estimated_value',
                'priority',
                'project_objectives',
                'project_description',
            ]);
        });
    }
};
