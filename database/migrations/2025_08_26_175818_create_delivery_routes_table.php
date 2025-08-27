<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->text('description')->nullable();
            $table->json('coverage_areas'); // Array of areas/neighborhoods
            $table->decimal('estimated_distance_km', 8, 2)->default(0);
            $table->integer('estimated_time_minutes')->default(0);
            $table->decimal('delivery_cost_per_order', 10, 3)->default(0);
            $table->integer('max_orders_per_trip')->default(10);
            $table->enum('route_type', ['standard', 'express', 'bulk', 'special'])->default('standard');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->json('operating_hours')->nullable(); // {start: "08:00", end: "18:00"}
            $table->json('operating_days')->nullable(); // [1,2,3,4,5] for weekdays
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index('status');
            $table->index('route_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_routes');
    }
};