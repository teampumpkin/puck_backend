<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateV4PlayerPortfolioSubsTable extends Migration
{
    public function up(): void
    {
        Schema::create('v4_player_portfolio_subs', function (Blueprint $table) {
            $table->id();

            // Foreign key to main portfolio
            $table->foreignId('portfolio_id')
                ->constrained('v4_player_portfolios')
                ->onDelete('cascade');

            // Polymorphic relation
            $table->morphs('subable');
            // creates: subable_id (BIGINT) + subable_type (VARCHAR)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('v4_player_portfolio_subs');
    }
}
