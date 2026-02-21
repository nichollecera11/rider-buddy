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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('image')->nullable(); // Para sa profile/logo ra
            $table->string('shop_name')->nullable(); // Himoon natong required para naay brand ang shop
            $table->text('address');
            $table->string('contact_number');
            $table->string('business_permit_no')->nullable(); // Para sa validation
            $table->boolean('has_delivery')->default(false); // Kung mo-deliver ba silag parts
            $table->string('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_official_store')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_24_7')->default(false);
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
