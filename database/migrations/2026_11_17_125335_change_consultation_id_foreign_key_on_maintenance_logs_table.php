<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->foreign('consultation_id')
                ->references('id')
                ->on('consultations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->foreign('consultation_id')
                ->references('id')
                ->on('consultations')
                ->nullOnDelete();
        });
    }
};