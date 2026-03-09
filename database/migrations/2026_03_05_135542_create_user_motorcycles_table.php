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
        Schema::create('user_motorcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('restrict');
            $table->string('model');
            $table->string('year_model')->nullable();
            $table->integer('engine_capacity')->nullable();
            $table->enum('transmission', ['manual', 'automatic', 'semi-automatic', 'none-electric'])->nullable();
            $table->enum('fuel_type', ['gasoline', 'electric'])->default('gasoline');
            $table->string('color')->nullable();
            $table->string('plate_number')->unique();
            $table->string('engine_number')->unique()->nullable()->comment('Unique LTO Engine Number');
            $table->string('chassis_number')->unique()->nullable()->comment('Unique LTO Chassis Number');
            $table->date('last_registration_date')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->integer('current_odometer')->default(0);
            $table->boolean('is_main')->default(false); // Para mahibal-an kinsay primary bike
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_motorcycles');
    }
};
