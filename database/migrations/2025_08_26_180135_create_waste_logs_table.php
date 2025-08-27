<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->string('waste_log_number')->unique();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->enum('waste_type', ['expired', 'damaged', 'overproduction', 'spoiled', 'returned', 'quality_control', 'other']);
            $table->enum('waste_source', ['kitchen', 'storage', 'delivery', 'customer_return', 'quality_check']);
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->decimal('quantity_wasted', 15, 3);
            $table->string('unit_of_measure', 20)->default('kg');
            $table->decimal('unit_cost', 15, 3)->default(0);
            $table->decimal('total_waste_cost', 15, 3)->default(0);
            $table->string('currency', 3)->default('JOD');
            $table->date('expiry_date')->nullable();
            $table->date('production_date')->nullable();
            $table->date('waste_date');
            $table->text('waste_reason');
            $table->text('prevention_notes')->nullable();
            $table->enum('disposal_method', ['compost', 'landfill', 'donation', 'recycling', 'other']);
            $table->text('disposal_notes')->nullable();
            $table->json('photos')->nullable();
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            
            // Indexes
            $table->index('waste_type');
            $table->index('waste_source');
            $table->index('waste_date');
            $table->index('status');
            $table->index('inventory_item_id');
            $table->index(['waste_date', 'waste_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
    }
};