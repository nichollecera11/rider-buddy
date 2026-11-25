
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_qr_challenges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consultation_id')
                ->constrained()
                ->cascadeOnDelete();

            // Store only the SHA-256 hash of the QR token.
            $table->char('token_hash', 64)->unique();

            // QR scanning expiration.
            $table->timestamp('expires_at');

            // Mechanic identity and scan information.
            $table->foreignId('scanned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('scanned_at')->nullable();

            // Rider confirmation window after scanning.
            $table->timestamp('confirmation_expires_at')->nullable();

            // Rider approval and challenge revocation.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index([
                'consultation_id',
                'revoked_at',
                'confirmed_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_qr_challenges');
    }
};
