<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4OtpsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('v4_otps', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->string('otp');            // no length limit
            $table->string('type');
            $table->string('provider');

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('expire_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Foreign Key
            $table->foreign('user_id')
                ->references('id')
                ->on('v4_users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('v4_otps');
    }
}
