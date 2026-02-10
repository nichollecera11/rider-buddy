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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('path'); // Ang lokasyon sa file sa imong storage

            // Kini ang "Magic" part: Polymorphic Relationship
            // Pasabot: Kini nga picture pwede para sa Motor, pwede para sa Part, pwede sa Mechanic.
            $table->unsignedBigInteger('imageable_id');
            $table->string('imageable_type');

            $table->boolean('is_primary')->default(false); // Para mahibal-an kinsa ang "Main" photo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
