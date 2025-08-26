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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->text('location_address');
            $table->string('contact_person_name');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->enum('payment_terms', ['net_30', 'net_15', 'cash_on_delivery'])->default('net_30');
            $table->decimal('credit_limit', 10, 2);
            $table->enum('account_status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('whatsapp_business_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
