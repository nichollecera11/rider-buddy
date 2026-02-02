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
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop_name');
            $table->string('address');
            $table->string('contact_number');
            $table->integer('years experience')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->decimal('service_fee_starts_at', 10, 2)->nullable();
            $table->string('image')->nullable(); // Para sa profile/logo ra
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
