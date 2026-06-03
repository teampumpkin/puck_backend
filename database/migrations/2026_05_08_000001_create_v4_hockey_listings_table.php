<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4HockeyListingsTable extends Migration
{
    public function up()
    {
        Schema::create('v4_hockey_listings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('v4_users')
                ->onDelete('cascade');

            $table->foreignId('payment_request_id')
                ->nullable()
                ->constrained('v4_payment_requests')
                ->onDelete('restrict');

            $table->string('name', 255);
            $table->integer('price_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('description')->nullable();
            $table->string('category');

            $table->string('condition');

            // Geolocation (Google Maps)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->unsignedInteger('sell_radius')->default(50); // in km

            $table->timestamp('listed_at')->nullable();

            $table->string('status')->default('draft');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'listed_at']);
            $table->index(['category', 'status']);
            $table->index('payment_request_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_hockey_listings');
    }
}
