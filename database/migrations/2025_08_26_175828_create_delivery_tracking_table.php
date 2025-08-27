<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed', 'returned', 'cancelled']);
            $table->text('status_description')->nullable();
            $table->datetime('timestamp');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable(); // photos, signatures, etc.
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('delivery_id');
            $table->index('status');
            $table->index('timestamp');
            $table->index(['delivery_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tracking');
    }
};