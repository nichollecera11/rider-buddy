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
        Schema::create('l_t_o_compliance_media', function (Blueprint $table) {
        $table->id();
        // Foreign Key link sa main LTO record
        $table->foreignId('l_t_o_compliance_id')->constrained()->onDelete('cascade');
        
        $table->string('file_path'); // Ang 'private' path
        $table->string('document_type')->default('OR_CR'); // e.g. OR, CR, Plate, ID
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('l_t_o_compliance_media');
    }
};
