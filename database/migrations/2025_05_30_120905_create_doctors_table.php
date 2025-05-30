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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('doctor_id')->unique();
            $table->string('specialty');
            $table->string('license_number')->unique();
            $table->integer('years_of_experience')->default(0);
            $table->text('education')->nullable();
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->json('available_days')->nullable();
            $table->time('available_hours_start')->nullable();
            $table->time('available_hours_end')->nullable();
            $table->string('room_number')->nullable();
            $table->string('phone_extension')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('specialty');
            $table->index('license_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
