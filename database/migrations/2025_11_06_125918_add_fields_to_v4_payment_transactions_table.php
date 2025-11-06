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
            $table->string('purchase_id')->nullable()->before('status');
            $table->enum('source', ['ios', 'android', 'web', 'window', 'linux', 'macos'])->nullable()->after('purchase_id');
            $table->json('verification_data')->nullable()->after('source');
            $table->json('local_data')->nullable()->after('verification_data');
            $table->string('store_status')->nullable()->after('local_data');
            $table->timestamp('transaction_date')->nullable()->after('store_status');
            $table->json('payload')->nullable()->after('transaction_date');
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
            $table->dropColumn([
                'purchase_id',
                'source',
                'verification_data',
                'local_data',
                'store_status',
                'transaction_date',
                'payload',
            ]);
        });
    }
}
