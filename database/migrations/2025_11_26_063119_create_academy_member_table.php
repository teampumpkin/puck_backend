<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademyMemberTable extends Migration
{
    public function up(): void
    {
        Schema::create('academy_member', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('team_id');

            // Track who added/removed the team
            $table->unsignedBigInteger('added_by')->nullable();
            $table->unsignedBigInteger('removed_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('academy_id')
                ->references('id')->on('v4_academies')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')->on('v4_teams')
                ->onDelete('cascade');

            $table->foreign('added_by')
                ->references('id')->on('v4_users')
                ->onDelete('set null');

            $table->foreign('removed_by')
                ->references('id')->on('v4_users')
                ->onDelete('set null');

            // Prevent duplicate academy-team mapping
            $table->unique(['academy_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_member');
    }
}
