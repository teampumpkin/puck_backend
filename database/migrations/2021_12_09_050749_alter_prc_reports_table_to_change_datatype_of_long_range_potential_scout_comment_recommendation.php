<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterPrcReportsTableToChangeDatatypeOfLongRangePotentialScoutCommentRecommendation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('prc_reports', function (Blueprint $table) {
            DB::statement("alter table prc_reports alter column long_range_potential type text using long_range_potential::text");
            DB::statement("alter table prc_reports alter column scout_comment type text using scout_comment::text");
            DB::statement("alter table prc_reports alter column recommendation type text using recommendation::text");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('prc_reports', function (Blueprint $table) {
            //
        });
    }
}
