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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('whatsapp_order_id')->nullable();
            $table->json('order_items');
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('order_date');
            $table->date('requested_delivery_date');
            $table->enum('order_status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('special_notes')->nullable();
            $table->text('delivery_address');
            $table->foreignId('assigned_workflow_id')->nullable()->constrained('workflows');
            $table->string('payment_terms_override')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
