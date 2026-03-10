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
        Schema::create('seller_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')
                ->constrained('sellers')
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
        Schema::dropIfExists('seller_media');
    }
};
