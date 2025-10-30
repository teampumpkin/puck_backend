<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductTypeToV4InAppPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_in_app_purchases', function (Blueprint $table) {
            $table->string('product_type')->nullable()->after('title')->comment('Product type: consumable, non_consumable, or subscription');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_in_app_purchases', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });
    }
}
