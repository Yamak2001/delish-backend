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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->decimal('payment_amount', 10, 2);
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'credit_card']);
            $table->date('payment_date');
            $table->string('reference_number')->nullable();
            $table->json('applied_to_invoices');
            $table->enum('status', ['pending', 'cleared', 'returned'])->default('cleared');
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
