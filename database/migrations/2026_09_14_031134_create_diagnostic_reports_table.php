<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnostic_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');

            // Nullable + nullOnDelete, not cascade — see note below on why
            $table->foreignId('mechanic_id')->nullable()->constrained()->nullOnDelete();

            // The actual clinical verdict — this is what's missing, not the fee
            $table->text('findings');
            $table->text('recommended_repairs')->nullable();
            $table->enum('severity', ['minor', 'moderate', 'urgent'])->default('minor');

            // Trust / audit trail, matching your l_t_o_compliances pattern
            $table->enum('status', ['draft', 'issued', 'disputed'])->default('draft')->index();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('disputed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('dispute_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_reports');
    }
};
