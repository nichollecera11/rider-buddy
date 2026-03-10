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
        Schema::create('mechanic_media', function (Blueprint $table) {
        $table->id();
        // Direct Foreign Key para sa Mechanic
        $table->foreignId('mechanic_id')
              ->constrained('mechanics')
              ->onDelete('cascade');

        $table->string('file_path');
        $table->string('file_type')->default('image'); // image, video
        $table->string('collection')->default('profile'); // profile, shop_photo, certification
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanic_media');
    }
};
