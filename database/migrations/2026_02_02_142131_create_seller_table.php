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
        Schema::create('seller', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable(); // Para sa profile/logo ra
            $table->string('shop_name')->nullable();
            $table->string('seller_name');
            $table->text('address');
            $table->string('contact_number');
            $table->string('business_permit_no')->nullable(); // Para sa validation
            $table->boolean('has_delivery')->default(false); // Kung mo-deliver ba silag parts
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
