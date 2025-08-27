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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->date('receipt_date');
            $table->string('delivery_note_number')->nullable();
            $table->decimal('total_quantity_received', 15, 3);
            $table->decimal('total_value_received', 15, 3);
            $table->string('received_by')->nullable();
            $table->enum('status', ['pending', 'inspected', 'accepted', 'rejected', 'returned'])->default('pending');
            $table->text('quality_notes')->nullable();
            $table->text('discrepancy_notes')->nullable();
            $table->json('quality_checks')->nullable();
            $table->boolean('temperature_check_passed')->default(true);
            $table->decimal('temperature_recorded', 5, 2)->nullable();
            $table->boolean('packaging_intact')->default(true);
            $table->boolean('quantity_verified')->default(true);
            $table->boolean('quality_approved')->default(true);
            $table->json('attachments')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users');
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('receipt_date');
            $table->index('status');
            $table->index(['supplier_id', 'receipt_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
