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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('qr_code')->unique();
            $table->decimal('current_quantity', 10, 3);
            $table->string('unit_of_measurement');
            $table->decimal('cost_per_unit', 8, 2);
            $table->string('supplier_name');
            $table->string('supplier_contact');
            $table->decimal('minimum_stock_level', 10, 3);
            $table->decimal('maximum_stock_level', 10, 3);
            $table->integer('shelf_life_days');
            $table->string('storage_requirements');
            $table->enum('category', ['dairy', 'dry_goods', 'spreads', 'packaging', 'baking_ingredients', 'flavoring', 'chocolate', 'fruit', 'leavening']);
            $table->enum('status', ['active', 'discontinued', 'in_stock', 'low_stock', 'out_of_stock'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
