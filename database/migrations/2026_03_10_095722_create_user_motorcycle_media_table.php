<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_motorcycle_media', function (Blueprint $table) {
        $table->id();
        // Direct link sa motor record
        $table->foreignId('user_motorcycle_id')
              ->constrained('user_motorcycles')
              ->onDelete('cascade'); // Kung ma-delete ang motor, apil media

        $table->string('file_path');
        $table->string('file_type')->default('image'); // image, video, pdf
        $table->string('collection')->default('verification'); // verification, repair, etc.
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_motorcycle_media');
    }
};
