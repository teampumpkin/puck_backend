<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4MarketplacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('v4_marketplaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_app_purchase_id')
                ->constrained('v4_in_app_purchases')
                ->onDelete('cascade');

            $table->string('icon')->nullable(); // URL or file path
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('header_url')->nullable();
            $table->string('type');

            $table->integer('price_cents')->default(0); // price in cents
            $table->string('currency', 3)->default('USD'); // ISO currency code

            $table->json('price_breakdown')->nullable(); // store detailed pricing info, taxes, etc.

            $table->boolean('active')->default(true);


            $table->timestamps();
            $table->softDeletes();

            $table->index(['active', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('v4_marketplaces');
    }
}
