<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4AcademyAdminsTable extends Migration
{
    public function up(): void
    {
        Schema::create('v4_academy_admins', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('academy_id');
            $table->unsignedBigInteger('admin_id');

            $table->timestamps();
            $table->softDeletes();

            // FK Constraints
            $table->foreign('academy_id')
                ->references('id')->on('v4_academies')
                ->onDelete('cascade');

            $table->foreign('admin_id')
                ->references('id')->on('v4_users')
                ->onDelete('cascade');

            // Prevent duplicate admin assignment
            $table->unique(['academy_id', 'admin_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v4_academy_admins');
    }
}
