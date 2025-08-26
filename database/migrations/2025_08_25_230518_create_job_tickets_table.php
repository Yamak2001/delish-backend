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
        Schema::create('job_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_id')->constrained();
            $table->string('job_ticket_number')->unique();
            $table->enum('priority_level', ['normal', 'high', 'urgent'])->default('normal');
            $table->enum('current_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('current_step_number')->default(1);
            $table->json('assigned_users')->nullable();
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('estimated_completion_timestamp')->nullable();
            $table->timestamp('actual_completion_timestamp')->nullable();
            $table->decimal('total_production_cost', 10, 2)->default(0);
            $table->text('quality_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_tickets');
    }
};
