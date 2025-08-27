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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->unique();
            $table->string('supplier_name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Jordan');
            $table->string('postal_code')->nullable();
            $table->string('tax_number')->nullable();
            $table->enum('supplier_type', ['ingredient', 'packaging', 'equipment', 'service', 'other'])->default('ingredient');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->enum('payment_terms', ['cod', 'net_15', 'net_30', 'net_45', 'net_60', 'prepaid'])->default('net_30');
            $table->decimal('credit_limit', 15, 3)->default(0);
            $table->decimal('current_balance', 15, 3)->default(0);
            $table->string('currency', 3)->default('JOD');
            $table->integer('lead_time_days')->default(7);
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->text('notes')->nullable();
            $table->json('contact_info')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('supplier_code');
            $table->index('status');
            $table->index('supplier_type');
            $table->index(['supplier_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
