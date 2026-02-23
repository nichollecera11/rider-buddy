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
        Schema::create('motorcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->string('model');
            $table->year('year_model');
            $table->string('color')->nullable();
            $table->string('plate_number')->nullable(); //optional kung second hand
            $table->integer('mileage')->default(0);
            $table->integer('engine_capacity')->nullable();
            $table->enum('transmission', ['manual', 'automatic', 'semi_automatic'])->nullable();
            $table->enum('fuel_type', ['gasoline', 'electric'])->default('gasoline');
            $table->string('current_location')->nullable();
            $table->decimal('price', 12, 2);
            $table->boolean('is_negotiable')->default(true)->after('price');
            $table->enum('condition', ['brand_new', 'second_hand']);
            $table->enum('document_status', ['complete_original', 'orig_cr_xerox_or', 'xerox_only', 'no_papers'])->default('complete_original');
            $table->boolean('is_registered')->default(true);
            $table->boolean('is_open_for_swap')->default(false)->after('is_negotiable');
            $table->text('swap_preferences')->nullable()->after('is_open_for_swap'); // Para sa detalye sa gusto i-swap
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
