<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('evaluation_submissions')->onDelete('cascade');
            $table->foreignId('assignment_id')->constrained('evaluator_assignments')->onDelete('cascade');
            $table->foreignId('evaluator_id')->constrained('v4_users')->onDelete('cascade');
            $table->decimal('overall_rating', 3, 2)->default(0.00); // 0.00 to 5.00
            $table->text('notes')->nullable();
            $table->enum('status', [
                'draft',
                'submitted',
                'rejected'
            ])->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['evaluator_id', 'status']);
            $table->index(['submission_id', 'status']);
            $table->index(['assignment_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('overall_rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluations');
    }
}