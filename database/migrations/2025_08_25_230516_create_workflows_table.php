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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_name');
            $table->text('description');
            $table->json('workflow_steps');
            $table->integer('estimated_total_duration_minutes');
            $table->enum('workflow_type', ['cakes', 'cupcakes', 'cookies', 'pastries', 'standard', 'rush', 'custom']);
            $table->boolean('active_status')->default(true);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
