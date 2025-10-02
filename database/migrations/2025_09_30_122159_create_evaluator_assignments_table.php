<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluatorAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluator_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('evaluation_submissions')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('v4_users')->onDelete('cascade');
            $table->enum('status', [
                'pending',
                'in_progress',
                'accepted',
                'completed',
                'rejected'
            ])->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['evaluator_id', 'status']);
            $table->index(['submission_id', 'status']);
            $table->index(['status', 'assigned_at']);
            $table->index('assigned_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluator_assignments');
    }
}