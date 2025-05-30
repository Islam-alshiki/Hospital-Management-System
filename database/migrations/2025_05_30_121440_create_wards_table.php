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
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('code')->unique();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('location');
            $table->integer('total_beds')->default(0);
            $table->integer('available_beds')->default(0);
            $table->enum('ward_type', ['general', 'private', 'icu', 'emergency', 'maternity', 'pediatric', 'surgical'])->default('general');
            $table->foreignId('head_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('ward_type');
            $table->index(['department_id', 'ward_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
