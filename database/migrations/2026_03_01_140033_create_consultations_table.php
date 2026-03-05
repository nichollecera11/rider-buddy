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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            // Sa imong Consultations Migration
            $table->foreignId('user_motorcycle_id')
                ->constrained('user_motorcycles')
                ->onDelete('restrict'); // Ayaw i-delete ang consultation record bisan i-delete ang motor
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mechanic_id')->constrained()->onDelete('cascade');
            $table->foreignId('motorcycle_id')->constrained()->onDelete('cascade');
            $table->string('consultation_type')->default('standard');
            $table->text('issue_description');
            $table->decimal('agreed_diagnostic_fee', 10, 2)->default(0);
            $table->decimal('estimated_repair_costs', 10, 2)->nullable();
            $table->string('payment_status')->default('pending')->index();
            $table->string('status')->default('pending')->index();
            $table->json('suggested_parts')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->text('mechanic_notes')->nullable();
            $table->string('verification_otp', 6)->nullable();
            $table->timestamp('arrived_at')->nullable()->after('verification_otp');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
