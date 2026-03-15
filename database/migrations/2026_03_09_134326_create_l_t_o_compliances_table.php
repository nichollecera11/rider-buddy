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
        Schema::create('l_t_o_compliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_motorcycle_id')->constrained()->onDelete('cascade');
            //ORCR Details
            $table->string('plate_number')->unique();
            $table->string('engine_number')->unique();
            $table->string('chassis_number')->unique();
            $table->date('registration_expiry');
            //Status Compliance
            $table->enum('status',['pending' , 'approved' , 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('remarks')->nullable();
            //Audit Trail 
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('l_t_o_compliances');
    }
};
