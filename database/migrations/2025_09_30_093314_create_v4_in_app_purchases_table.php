<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4InAppPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_in_app_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique(); // Stock Keeping Unit - unique identifier
            $table->string('title');
            $table->integer('amount_cents'); // Amount in cents to avoid floating point issues
            $table->string('currency', 3)->default('USD'); // ISO currency code
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_in_app_purchases');
    }
}