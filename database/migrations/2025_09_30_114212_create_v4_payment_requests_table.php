<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PaymentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('v4_users')->onDelete('cascade');
<<<<<<< HEAD
            $table->foreignId('player_id')->constrained('v4_users')->onDelete('cascade');
=======
>>>>>>> 8195f09359457f493e2e83a875f8e4760febbc66
            $table->foreignId('in_app_purchase_id')->constrained('v4_in_app_purchases')->onDelete('cascade');
            $table->integer('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',
                'parent_approved',
                'parent_rejected',
                'payment_initiated',
                'paid',
                'failed'
            ])->default('pending');
            $table->integer('retry_count')->default(0);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['payer_id', 'status']);
            $table->index(['parent_id', 'status']);
<<<<<<< HEAD
            $table->index(['player_id', 'status']);
=======
>>>>>>> 8195f09359457f493e2e83a875f8e4760febbc66
            $table->index(['status', 'created_at']);
            $table->index('in_app_purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_payment_requests');
    }
}