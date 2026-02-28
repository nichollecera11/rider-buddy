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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('mechanic_id')->constrained()->onDelete('cascade');
            $table->string('service_type');
            $table->string('notes')->nullable();
            //SOS Emergency Table
            $table->boolean('is_emergency')->default(false);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            //Security Tracking
            $table->string('status')->default('pending')->index();
            $table->decimal('service_fee', 10, 2)->default(0); // Ang base fee
            $table->decimal('additional_charges', 10, 2)->default(0); // Para sa mga pyesa o towing
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('payment_method')->default('cash'); // Future-proof kon mag-Gcash ta puhon
            $table->string('payment_status')->default('unpaid'); // unpaid, paid
            // 6. CANCELLATION REASON
            // Importante ni sa analytics kon nganong sige og cancel ang user
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Para sa "Trash" functionality (Security audit)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
