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
        Schema::create('waste_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->date('scheduled_collection_date');
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users');
            $table->enum('collection_status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('actual_collection_date')->nullable();
            $table->json('waste_items_collected')->nullable();
            $table->json('photos')->nullable();
            $table->decimal('total_waste_value', 10, 2)->default(0);
            $table->boolean('credited_to_merchant')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_management');
    }
};
