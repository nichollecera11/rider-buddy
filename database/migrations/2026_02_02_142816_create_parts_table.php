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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->constrained();
            $table->string('part_name');
            $table->string('part_number')->nullable(); // Pain Point #1: Para sa saktong fitment
            // Genuine (OEM) vs Replacement
            $table->enum('type', ['original', 'replacement', 'aftermarket'])->default('aftermarket');
            $table->enum('condition', ['new', 'used'])->default('new');
            $table->decimal('price', 10, 2);
            $table->boolean('is_negotiable')->default(true)->after('price');
            $table->integer('stock_quantity')->default(1);
            // --OEM -- //
            $table->text('oem_compatibility')->nullable(); // List of specific bikes
            $table->boolean('is_universal')->default(false); // Toggle for universal parts
            $table->string('dimensions')->nullable(); // Exact sizes (e.g., 14mm, 17 inches)
            // --- KANI IMONG GUSTO (SWAP LOGIC) ---
            $table->boolean('is_open_for_swap')->default(false);
            $table->text('swap_preferences')->nullable(); // e.g., "Swap to Stock + Cash"
            $table->text('description')->nullable();
            // Para sa Location (usahay lahi ang location sa shop kaysa seller)
            $table->string('location')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
