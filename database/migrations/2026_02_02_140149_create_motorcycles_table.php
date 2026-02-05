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
        Schema::create('motorcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('model');
            $table->year('year_model');
            $table->string('plate_number')->nullable(); //optional kung second hand
            $table->integer('mileage')->default(0);
            $table->decimal('price', 12, 2);
            $table->enum('condition', ['brand_new', 'second_hand']);
            $table->enum('document_status', ['complete_original', 'orig_cr_xerox_or', 'xerox_only','no_papers'])->default('complete_original');
            $table->boolean('is_registered')->default(true);
            $table->text('description');
            $table->text('issues')->nullable(); //if mint condition leave it blank
            $table->boolean('is_sold')->default(false); //mawala sa listing pag sold na
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motorcycles');
    }
};
