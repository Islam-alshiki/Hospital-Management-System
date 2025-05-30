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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->foreignId('ward_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->enum('room_type', ['single', 'double', 'triple', 'ward', 'icu', 'operation_theater', 'consultation'])->default('single');
            $table->integer('bed_count')->default(1);
            $table->integer('available_beds')->default(1);
            $table->decimal('daily_rate', 8, 2)->default(0);
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_bathroom')->default(true);
            $table->json('equipment')->nullable();
            $table->enum('status', ['available', 'occupied', 'maintenance', 'cleaning', 'reserved'])->default('available');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('room_number');
            $table->index('status');
            $table->index(['ward_id', 'status']);
            $table->index(['room_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
