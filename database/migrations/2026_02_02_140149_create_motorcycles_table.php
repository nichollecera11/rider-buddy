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
            $table->string('plate_number')->nullable();
            $table->integer('mileage')->default(0);
            $table->integer('engine_capacity')->nullable();
            $table->enum('transmission', ['manual', 'automatic', 'semi_automatic'])->nullable();
            $table->enum('fuel_type', ['gasoline', 'electric'])->default('gasoline');
            $table->string('current_location')->nullable();

            // Sales Details
            $table->decimal('price', 12, 2);
            $table->boolean('is_negotiable')->default(true); // Tangtanga ang ->after()
            $table->boolean('is_open_for_swap')->default(false); // Tangtanga ang ->after()
            $table->text('swap_preferences')->nullable(); // Tangtanga ang ->after()

            $table->enum('condition', ['brand_new', 'second_hand']);
            $table->enum('document_status', ['complete_original', 'orig_cr_xerox_or', 'xerox_only', 'no_papers'])->default('complete_original');
            $table->boolean('is_registered')->default(true);
            $table->text('description');
            $table->text('issues')->nullable();
            $table->boolean('is_sold')->default(false);
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
