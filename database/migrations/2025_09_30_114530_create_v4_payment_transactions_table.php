<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_request_id')->constrained('v4_payment_requests')->onDelete('cascade');
            $table->foreignId('payer_id')->constrained('v4_users')->onDelete('cascade');
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('gateway')->nullable(); // stripe, paypal, apple_pay, etc.
            $table->string('gateway_reference')->nullable(); // transaction ID from payment gateway
            $table->enum('status', [
                'pending',
                'success',
                'failed',
                'refunded',
                'cancelled'
            ])->default('pending');
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['payer_id', 'status']);
            $table->index(['payment_request_id', 'status']);
            $table->index(['gateway', 'gateway_reference']);
            $table->index(['status', 'created_at']);
            $table->unique(['gateway', 'gateway_reference'], 'unique_gateway_reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_payment_transactions');
    }
}