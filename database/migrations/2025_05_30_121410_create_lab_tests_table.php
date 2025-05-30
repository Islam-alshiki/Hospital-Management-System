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
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_number')->unique();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->foreignId('medical_record_id')->nullable()->constrained()->onDelete('set null');
            $table->string('test_name');
            $table->string('test_category');
            $table->text('description')->nullable();
            $table->string('sample_type');
            $table->timestamp('ordered_at');
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['ordered', 'collected', 'processing', 'completed', 'cancelled'])->default('ordered');
            $table->text('result')->nullable();
            $table->string('normal_range')->nullable();
            $table->enum('result_status', ['normal', 'abnormal', 'critical'])->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->decimal('cost', 8, 2)->default(0);
            $table->timestamps();

            $table->index(['patient_id', 'ordered_at']);
            $table->index(['doctor_id', 'ordered_at']);
            $table->index('test_number');
            $table->index('status');
            $table->index('test_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
