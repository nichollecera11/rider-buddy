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
            $table->string('plate_number')->unique();
            $table->string('engine_number')->nullable(); // Optional pero maayo para sa LTO tracking
            $table->string('chassis_number')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_main')->default(false); // Para mahibal-an kinsay primary bike
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
