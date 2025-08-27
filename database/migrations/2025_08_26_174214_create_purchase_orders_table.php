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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->decimal('subtotal_amount', 15, 3)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0.16);
            $table->decimal('tax_amount', 15, 3)->default(0);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->decimal('shipping_cost', 15, 3)->default(0);
            $table->decimal('total_amount', 15, 3)->default(0);
            $table->decimal('received_amount', 15, 3)->default(0);
            $table->string('currency', 3)->default('JOD');
            $table->enum('payment_terms', ['cod', 'net_15', 'net_30', 'net_45', 'net_60', 'prepaid'])->default('net_30');
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('po_number');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('po_date');
            $table->index(['supplier_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
