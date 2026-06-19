<?php

namespace App\Console\Commands;

use App\Models\V4HockeyListing;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Services\Payments\HockeyListingPaymentDecider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileHockeyListings extends Command
{
    protected $signature = 'hockey:reconcile-listings {--apply : Apply changes (default is dry-run)}';
    protected $description = 'Remediate hockey listings stuck by the legacy payment bug';

    public function handle(HockeyListingPaymentDecider $decider): int
    {
        $apply = (bool) $this->option('apply');
        $counts = ['publish' => 0, 'release' => 0, 'skip' => 0];

        V4HockeyListing::whereIn('status', [
            V4HockeyListing::STATUS_DRAFT,
            V4HockeyListing::STATUS_PAYMENT_REQUESTED,
            V4HockeyListing::STATUS_PAYMENT_REJECTED,
            V4HockeyListing::STATUS_PAYMENT_FAILED,
        ])->whereNotNull('payment_request_id')->chunkById(200, function ($listings) use ($decider, $apply, &$counts) {
            foreach ($listings as $listing) {
                $request = V4PaymentRequest::find($listing->payment_request_id);
                $successTxn = $request
                    ? V4PaymentTransaction::where('payment_request_id', $request->id)
                        ->where('status', V4PaymentTransaction::STATUS_SUCCESS)->latest('id')->first()
                    : null;
                $anyTxn = $request
                    ? V4PaymentTransaction::where('payment_request_id', $request->id)->exists()
                    : false;

                $action = $decider->reconcile([
                    'listing_published' => $listing->status === V4HockeyListing::STATUS_PUBLISHED,
                    'success_txn_exists' => (bool) $successTxn,
                    'request_status' => $request?->status,
                    'any_txn_exists' => $anyTxn,
                ]);

                $counts[$action]++;

                if (!$apply || $action === HockeyListingPaymentDecider::RECON_SKIP) {
                    continue;
                }

                DB::transaction(function () use ($action, $listing, $request) {
                    if ($action === HockeyListingPaymentDecider::RECON_PUBLISH) {
                        $request?->markPaid();
                        $listing->markPublished();
                    } elseif ($action === HockeyListingPaymentDecider::RECON_RELEASE) {
                        // Release the stale request so the listing is re-payable.
                        $listing->payment_request_id = null;
                        $listing->status = V4HockeyListing::STATUS_DRAFT;
                        $listing->save();
                        $request?->markFailed('reconcile: released stale request');
                    }
                });
            }
        });

        $mode = $apply ? 'APPLIED' : 'DRY-RUN';
        $this->info("[$mode] publish={$counts['publish']} release={$counts['release']} skip={$counts['skip']}");
        return self::SUCCESS;
    }
}
