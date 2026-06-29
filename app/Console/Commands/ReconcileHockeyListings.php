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

        // Phase 2 — safety net for paid-but-unpublished listings: any SUCCESS
        // transaction whose owning request maps (via meta.listing_id) to a listing
        // that never flipped to published, including transactions recorded against a
        // now-superseded request. Idempotent: skips already-published / missing
        // (soft-deleted) listings. This catches any drift the live root-reconcile
        // path could miss (e.g. a crash between recording the txn and publishing).
        $stranded = 0;
        V4PaymentTransaction::where('status', V4PaymentTransaction::STATUS_SUCCESS)
            ->where('created_at', '>=', now()->subDays(30)) // bound the recurring scan
            ->chunkById(200, function ($txns) use ($apply, &$stranded) {
                foreach ($txns as $txn) {
                    $request = V4PaymentRequest::find($txn->payment_request_id);
                    if (!$request) {
                        continue;
                    }
                    $listingId = data_get($request->meta, 'listing_id');
                    $listing = $listingId ? V4HockeyListing::find($listingId) : null;
                    if (!$listing || $listing->status === V4HockeyListing::STATUS_PUBLISHED) {
                        continue;
                    }
                    $stranded++;
                    if (!$apply) {
                        continue;
                    }
                    DB::transaction(function () use ($request, $listing) {
                        // Re-read the listing under a row lock so a concurrent live
                        // confirm / root-reconcile cannot double-publish or clobber
                        // listed_at. Skip if it was published in the meantime.
                        $locked = V4HockeyListing::whereKey($listing->id)->lockForUpdate()->first();
                        if (!$locked || $locked->status === V4HockeyListing::STATUS_PUBLISHED) {
                            return;
                        }
                        $request->markPaid();
                        $locked->markPublished();
                    });
                }
            });

        $mode = $apply ? 'APPLIED' : 'DRY-RUN';
        $this->info("[$mode] publish={$counts['publish']} release={$counts['release']} skip={$counts['skip']} stranded_published={$stranded}");
        return self::SUCCESS;
    }
}
