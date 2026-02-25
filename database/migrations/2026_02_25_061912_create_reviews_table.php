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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('reviewable');

            // 1. Rating (Standard)
            $table->unsignedTinyInteger('rating')->default(5); // Mas tipid sa memory (0-255 range)

            // 2. Headline/Title (User Friendly)
            // Usahay ang rider gusto lang mo-ingon og "Solid kaayo!" bago ang taas nga comment.
            $table->string('headline')->nullable();

            $table->text('comment')->nullable();

            // 3. Helpful Counter (Professional Standard)
            // Sama sa Shopee o Amazon, ang ubang user puyde mo-click og "Helpful"
            $table->integer('helpful_count')->default(0);

            // 4. Photos (Optional field, or use Polymorphic Images later)
            // Mas mutuo ang rider kon naay picture sa pisa nga na-install na.
            // Pero since naa na tay Image model, puyde ra ni nato i-polymorphic sab.

            // 5. Seller/Mechanic Reply (Professional)
            // Importante ni para maka-respond ang seller sa reklamo o pasalamat.
            $table->text('reply_comment')->nullable();
            $table->timestamp('replied_at')->nullable();

            // 6. Status (Moderation)
            // Para ma-hide nato ang mga bastos o spam nga reviews.
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
