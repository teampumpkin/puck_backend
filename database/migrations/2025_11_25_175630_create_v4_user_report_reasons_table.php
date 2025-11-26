<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4UserReportReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_user_report_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->string('slug');
            $table->string('description');
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['active', 'reason']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_user_report_reasons');
    }
}
