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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('generic_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('code')->unique();
            $table->string('barcode')->nullable();
            $table->string('category');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('dosage_form');
            $table->string('strength')->nullable();
            $table->string('unit');
            $table->string('manufacturer')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('minimum_stock_level')->default(0);
            $table->integer('maximum_stock_level')->default(1000);
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('storage_conditions')->nullable();
            $table->json('side_effects')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('interactions')->nullable();
            $table->string('pregnancy_category')->nullable();
            $table->boolean('controlled_substance')->default(false);
            $table->boolean('requires_prescription')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('category');
            $table->index('name');
            $table->index(['stock_quantity', 'minimum_stock_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
