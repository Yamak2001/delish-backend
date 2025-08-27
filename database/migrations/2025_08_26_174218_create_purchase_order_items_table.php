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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items');
            $table->string('item_description')->nullable();
            $table->decimal('quantity_ordered', 15, 3);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('quantity_outstanding', 15, 3);
            $table->string('unit_of_measure', 20)->default('kg');
            $table->decimal('unit_price', 15, 3);
            $table->decimal('line_total', 15, 3);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->date('expected_date')->nullable();
            $table->text('item_notes')->nullable();
            $table->enum('status', ['pending', 'partially_received', 'received', 'cancelled'])->default('pending');
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_order_id');
            $table->index('inventory_item_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
