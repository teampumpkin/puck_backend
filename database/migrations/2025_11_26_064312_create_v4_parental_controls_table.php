<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4ParentalControlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_parental_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('v4_users')->onDelete('cascade'); // Reference to parent
            $table->foreignId('child_id')->constrained('v4_users')->onDelete('cascade');  // Reference to child
            $table->boolean('enabled')->default(true);
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
        Schema::dropIfExists('v4_parental_controls');
    }
}
