<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            // Nullable because routine maintenance (like oil changes) won't have a diagnostic report
            // nullOnDelete so we don't lose the maintenance history if a report is ever deleted
            $table->foreignId('diagnostic_report_id')
                  ->nullable()
                  ->after('id') // Puts it right at the top of the table for clean organization
                  ->constrained('diagnostic_reports')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['diagnostic_report_id']);
            $table->dropColumn('diagnostic_report_id');
        });
    }
};