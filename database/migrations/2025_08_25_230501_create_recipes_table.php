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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_name');
            $table->text('description');
            $table->integer('preparation_time_minutes');
            $table->string('serving_size');
            $table->decimal('cost_per_unit', 8, 2)->default(0);
            $table->boolean('active_status')->default(true);
            $table->text('special_instructions')->nullable();
            $table->string('storage_requirements')->nullable();
            $table->integer('shelf_life_days');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
