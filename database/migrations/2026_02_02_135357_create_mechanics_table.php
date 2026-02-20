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
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('shop_name')->nullable();
            $table->string('address');
            $table->text('bio')->nullable();
            $table->string('specialization')->nullable(); // e.g. "Electrical, Engine, FI"
            $table->string('contact_number');
            $table->string('emergency_contact')->nullable(); // Optional extra contact
            $table->integer('years_experience')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_available')->default(true); // Status sa mekaniko karon
            // --- LOCATION DATA ---
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('service_fee_starts_at', 10, 2)->nullable();
            $table->string('image')->nullable(); // Pwede na ni nimo i-delete puhon kay polymorphic naman ta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanics');
    }
};
