<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToV4PaymentTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('v4_payment_transactions', function (Blueprint $table) {
            // New product_id column (PostgreSQL + Laravel 8 safe)
            $table->bigInteger('product_id')->nullable()->after('payment_request_id');
            $table->string('purchase_id')->nullable()->before('status');
            $table->enum('source', ['ios', 'android', 'web', 'window', 'linux', 'macos'])->nullable()->after('purchase_id');
            $table->json('verification_data')->nullable()->after('source');
            $table->string('store_status')->nullable()->after('verification_data');
            $table->timestamp('transaction_date')->nullable()->after('store_status');
            $table->json('payload')->nullable()->after('transaction_date');

            $table->foreign('product_id')->references('id')->on('v4_in_app_purchases')->onDelete('set null');
            $table->index('product_id');

            $table->unique(['purchase_id', 'source'], 'unique_purchase_source');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('v4_payment_transactions', function (Blueprint $table) {
            $table->dropUnique('unique_purchase_source');
            // Drop FK + index + column
            $table->dropForeign(['product_id']);
            $table->dropIndex(['product_id']);

            // Reverse the store fields
            $table->dropColumn('product_id');
            $table->dropColumn('purchase_id');
            $table->dropColumn('source');
            $table->dropColumn('verification_data');
            $table->dropColumn('store_status');
            $table->dropColumn('transaction_date');
            $table->dropColumn('payload');
        });
    }
}
