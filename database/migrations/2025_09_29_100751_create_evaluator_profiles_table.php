<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluatorProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->json('leagues')->nullable();
            $table->string('level_hockey_played')->nullable();
            $table->string('current_involvement_level')->nullable();
            $table->string('current_sport_role')->nullable();
            $table->string('number_of_years_experience')->nullable();
            $table->string('resume')->nullable();
            $table->string('address')->nullable();
            $table->json('references')->nullable();
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
        Schema::dropIfExists('evaluator_profiles');
    }
}
