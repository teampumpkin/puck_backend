<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4UserReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_user_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reported_user_id')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('reported_by')->constrained('v4_users')->onDelete('cascade');
            $table->foreignId('reason_id')->constrained('v4_user_report_reasons')->onDelete('cascade');
            $table->text('message');
            $table->text('status')->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_user_reports');
    }
}
