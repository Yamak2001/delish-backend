<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number')->unique();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('delivery_route_id')->nullable()->constrained('delivery_routes');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users');
            $table->enum('delivery_type', ['standard', 'express', 'scheduled', 'pickup'])->default('standard');
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Customer & Address Information
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('delivery_address');
            $table->string('delivery_area')->nullable();
            $table->string('delivery_city')->default('Amman');
            $table->string('postal_code')->nullable();
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->text('delivery_instructions')->nullable();
            
            // Timing
            $table->datetime('scheduled_delivery_time')->nullable();
            $table->datetime('estimated_delivery_time')->nullable();
            $table->datetime('actual_pickup_time')->nullable();
            $table->datetime('actual_delivery_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('actual_duration_minutes')->nullable();
            
            // Costs & Pricing
            $table->decimal('delivery_fee', 10, 3)->default(0);
            $table->decimal('additional_charges', 10, 3)->default(0);
            $table->decimal('total_delivery_cost', 10, 3)->default(0);
            $table->string('currency', 3)->default('JOD');
            
            // Package Information
            $table->integer('total_items')->default(1);
            $table->decimal('total_weight_kg', 8, 3)->nullable();
            $table->text('package_description')->nullable();
            $table->json('special_requirements')->nullable(); // refrigerated, fragile, etc.
            
            // Status & Notes
            $table->text('delivery_notes')->nullable();
            $table->text('failed_reason')->nullable();
            $table->json('proof_of_delivery')->nullable(); // photos, signatures
            $table->decimal('customer_rating', 3, 2)->nullable();
            $table->text('customer_feedback')->nullable();
            
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('delivery_number');
            $table->index('order_id');
            $table->index('status');
            $table->index('assigned_driver_id');
            $table->index('delivery_route_id');
            $table->index('scheduled_delivery_time');
            $table->index(['status', 'priority']);
            $table->index(['delivery_area', 'delivery_city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};