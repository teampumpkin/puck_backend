<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterPrcAdvanceAssessmentCategoriesTableToAddCategoryInfoColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_advance_assessment_categories', function (Blueprint $table) {
            $table->string('category_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_advance_assessment_categories', function (Blueprint $table) {
            $table->dropColumn('category_info');
        });
    }
}
