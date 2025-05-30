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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('bill_date');
            $table->decimal('consultation_fee', 8, 2)->default(0);
            $table->decimal('lab_tests_total', 8, 2)->default(0);
            $table->decimal('medicines_total', 8, 2)->default(0);
            $table->decimal('procedures_total', 8, 2)->default(0);
            $table->decimal('room_charges', 8, 2)->default(0);
            $table->decimal('other_charges', 8, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'insurance', 'cheque'])->nullable();
            $table->foreignId('insurance_provider_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('insurance_coverage', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['patient_id', 'bill_date']);
            $table->index('bill_number');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
