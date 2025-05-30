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
        Schema::create('room_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->timestamp('admission_date');
            $table->timestamp('discharge_date')->nullable();
            $table->enum('status', ['active', 'discharged', 'transferred'])->default('active');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('discharged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admission_notes')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status']);
            $table->index(['patient_id', 'admission_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_assignments');
    }
};
