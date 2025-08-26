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
        Schema::create('job_ticket_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_ticket_id')->constrained()->onDelete('cascade');
            $table->integer('step_number');
            $table->string('step_name');
            $table->string('assigned_role');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users');
            $table->string('step_type');
            $table->enum('status', ['pending', 'active', 'completed', 'skipped'])->default('pending');
            $table->timestamp('start_timestamp')->nullable();
            $table->timestamp('completion_timestamp')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->integer('time_spent_minutes')->nullable();
            $table->boolean('quality_check_passed')->nullable();
            $table->integer('next_step_override')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_ticket_steps');
    }
};
