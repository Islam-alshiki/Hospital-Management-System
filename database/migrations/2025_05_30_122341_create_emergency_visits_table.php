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
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->string('patient_name')->nullable(); // For walk-in patients
            $table->string('patient_phone')->nullable();
            $table->timestamp('arrival_time');
            $table->text('chief_complaint');
            $table->text('incident_details')->nullable();
            $table->enum('triage_level', ['critical', 'urgent', 'less_urgent', 'non_urgent'])->default('less_urgent');
            $table->enum('arrival_mode', ['walk_in', 'ambulance', 'police', 'referral'])->default('walk_in');
            $table->foreignId('triage_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('attending_doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'admitted', 'discharged', 'transferred'])->default('waiting');
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('treatment_given')->nullable();
            $table->text('disposition')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['arrival_time', 'triage_level']);
            $table->index('visit_number');
            $table->index('status');
            $table->index('triage_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_visits');
    }
};
