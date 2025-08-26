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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->enum('invoice_type', ['delivery', 'credit_note']);
            $table->string('invoice_number')->unique();
            $table->foreignId('related_order_id')->nullable()->constrained('orders');
            $table->foreignId('related_job_ticket_id')->nullable()->constrained('job_tickets');
            $table->decimal('invoice_amount', 10, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->string('payment_terms');
            $table->json('line_items');
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('sent_timestamp')->nullable();
            $table->timestamp('paid_timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
