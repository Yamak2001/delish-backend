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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['purchase', 'usage', 'adjustment', 'waste']);
            $table->decimal('quantity_change', 10, 3);
            $table->decimal('unit_cost', 8, 2);
            $table->string('reference_id')->nullable();
            $table->foreignId('performed_by_user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
