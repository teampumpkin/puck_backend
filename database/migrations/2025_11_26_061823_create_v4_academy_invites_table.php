<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4AcademyInvitesTable extends Migration
{
    public function up(): void
    {
        Schema::create('v4_academy_invites', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->unsignedBigInteger('academy_id');

            // Invite fields
            $table->string('email_id')->nullable();
            $table->string('phone_no')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FK constraints
            $table->foreign('academy_id')
                ->references('id')->on('v4_academies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v4_academy_invites');
    }
}
