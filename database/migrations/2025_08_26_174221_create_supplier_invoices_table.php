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
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders');
            $table->string('invoice_number');
            $table->string('supplier_invoice_number');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('received_date')->nullable();
            $table->decimal('subtotal_amount', 15, 3);
            $table->decimal('tax_rate', 5, 4)->default(0.16);
            $table->decimal('tax_amount', 15, 3);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->decimal('total_amount', 15, 3);
            $table->decimal('paid_amount', 15, 3)->default(0);
            $table->decimal('balance_due', 15, 3);
            $table->string('currency', 3)->default('JOD');
            $table->enum('status', ['pending', 'approved', 'paid', 'partial_paid', 'overdue', 'disputed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'card', 'other'])->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('supplier_id');
            $table->index('purchase_order_id');
            $table->index('invoice_date');
            $table->index('status');
            $table->index('due_date');
            $table->index(['supplier_id', 'status']);
            $table->unique(['supplier_id', 'supplier_invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
