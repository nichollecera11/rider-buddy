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
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            // Relasyon: Kinsa ang namaligya? (Individual o Shop)
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            
            // Relasyon: Unsa ni nga klase nga part? (Engine, Body, etc.)
            $table->foreignId('category_id')->constrained();
            
            $table->string('part_name');
            $table->string('brand')->nullable(); // e.g., TDR, RCB, o "No Brand"
            
            // KANI ANG IMONG GUSTO:
            $table->enum('condition', ['new', 'used'])->default('new');
            
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(1);
            
            // Para mahibal-an kung unsa nga motor compatible kini nga part
            $table->string('compatibility')->nullable(); // e.g., "Mio Sporty, Mio Soul"
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
