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
        Schema::create('merchant_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipe_id')->constrained()->onDelete('cascade');
            $table->decimal('base_cost', 8, 2);
            $table->decimal('merchant_price', 8, 2);
            $table->decimal('markup_percentage', 5, 2);
            $table->date('effective_date');
            $table->date('expiration_date')->nullable();
            $table->enum('price_tier', ['standard', 'volume', 'premium'])->default('standard');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_pricing');
    }
};
