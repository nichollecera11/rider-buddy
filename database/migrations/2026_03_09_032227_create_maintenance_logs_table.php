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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('user_motorcycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('mechanic_id')->nullable()->constrained()->onDelete('set null');

            // Service Details
            $table->string('service_type')->index(); // Added index para paspas ang search by type
            $table->text('description')->nullable();

            // 🚀 Odometer & Verification
            $table->unsignedInteger('odometer_reading'); // Gigamit nimo ang unsigned, saktong-sakto!
            $table->date('service_date')->index(); // Added index para sa filtering by date
            $table->decimal('cost', 10, 2)->default(0);

            // 🚀 Verification logic
            $table->boolean('is_verified_by_mechanic')->default(false)->index();

            $table->timestamps();
            $table->softDeletes(); // Para sa "Safety Net" (Undo delete)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
