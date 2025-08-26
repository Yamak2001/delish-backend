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
        Schema::create('merchant_product_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_delivered', 10, 3);
            $table->timestamp('delivery_date');
            $table->date('expiration_date');
            $table->decimal('current_estimated_quantity', 10, 3);
            $table->enum('status', ['fresh', 'warning', 'expired'])->default('fresh');
            $table->foreignId('job_ticket_id')->nullable()->constrained();
            $table->text('driver_notes')->nullable();
            $table->boolean('collection_required')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_product_tracking');
    }
};
