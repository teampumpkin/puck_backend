<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationSubmissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('payment_request_id')->nullable()->constrained('v4_payment_requests')->onDelete('set null');
            $table->unsignedBigInteger('current_version_id')->nullable(); // No foreign key constraint yet
            $table->enum('status', [
                'uploaded',
                'assigned',
                'evaluating',
                'rejected',
                'accepted',
                'completed'
            ])->default('uploaded');
            $table->json('result_report_meta')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['player_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('payment_request_id');
            $table->index('current_version_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_submissions');
    }
}