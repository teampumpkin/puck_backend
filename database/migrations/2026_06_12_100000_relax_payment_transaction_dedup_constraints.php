<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelaxPaymentTransactionDedupConstraints extends Migration
{
    /**
     * Replace the strict `(purchase_id, source)` unique index with a partial
     * index that ignores sentinel values produced by Xcode StoreKit testing
     * (`'0'` / empty string), and add a partial unique index that allows at
     * most one successful transaction per payment_request.
     */
    public function up()
    {
        if (Schema::hasTable('v4_payment_transactions')) {
            Schema::table('v4_payment_transactions', function ($table) {
                $table->dropUnique('unique_purchase_source');
            });
        }

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS unique_purchase_source_prod
            ON v4_payment_transactions (purchase_id, source)
            WHERE purchase_id IS NOT NULL
              AND purchase_id NOT IN ('0', '')
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS unique_success_per_payment_request
            ON v4_payment_transactions (payment_request_id)
            WHERE status = 'success'
        SQL);
    }

    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS unique_success_per_payment_request');
        DB::statement('DROP INDEX IF EXISTS unique_purchase_source_prod');

        if (Schema::hasTable('v4_payment_transactions')) {
            Schema::table('v4_payment_transactions', function ($table) {
                $table->unique(['purchase_id', 'source'], 'unique_purchase_source');
            });
        }
    }
}
