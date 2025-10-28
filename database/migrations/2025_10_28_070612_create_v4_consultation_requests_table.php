<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4ConsultationRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_version_id')
                ->constrained('evaluation_submission_versions')
                ->cascadeOnDelete();
            $table->foreignId('evaluator_id')
                ->nullable()
                ->constrained('v4_users')
                ->nullOnDelete();
            $table->string('status');
            $table->text('admin_notes')->nullable();
            $table->text('evaluator_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_consultation_requests');
    }
}
