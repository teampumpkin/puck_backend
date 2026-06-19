<?php

namespace App\Services\Payments;

/**
 * Pure decision logic for hockey listing payments. No DB, no framework state.
 * Every decision is scoped to a single listing's own context — there is
 * deliberately no (user, SKU) input, which is what caused the
 * "already purchased / already own this item" bug.
 */
class HockeyListingPaymentDecider
{
    // confirm outcomes
    public const CONFIRM_UNAUTHORIZED      = 'unauthorized';
    public const CONFIRM_DUPLICATE         = 'duplicate';
    public const CONFIRM_ALREADY_PUBLISHED = 'already_published';
    public const CONFIRM_SELF_HEAL_PUBLISH = 'self_heal_publish';
    public const CONFIRM_NO_ACTIVE_REQUEST = 'no_active_request';
    public const CONFIRM_NOT_CONFIRMABLE   = 'not_confirmable';
    public const CONFIRM_PARENT_ONLY       = 'parent_only';
    public const CONFIRM_PROCEED           = 'proceed';

    // initiate outcomes
    public const INIT_ALREADY_PUBLISHED       = 'already_published';
    public const INIT_CHILD_NO_PARENT         = 'child_no_parent';
    public const INIT_RETURN_EXISTING_PENDING = 'return_existing_pending';
    public const INIT_RETURN_EXISTING_INITIATED = 'return_existing_initiated';
    public const INIT_CREATE_NEW              = 'create_new';

    // reconcile outcomes
    public const RECON_PUBLISH = 'publish';
    public const RECON_RELEASE = 'release';
    public const RECON_SKIP    = 'skip';

    public function gatewayForSource(string $source): string
    {
        return match ($source) {
            'ios' => 'app_store',
            'android' => 'play_store',
            default => 'web',
        };
    }

    /**
     * @param array $c listing_status, has_request, request_status, is_owner,
     *                 is_parent_payer, purchase_id_provided, duplicate_txn_exists,
     *                 success_txn_exists
     */
    public function confirm(array $c): string
    {
        if (!($c['is_owner'] ?? false) && !($c['is_parent_payer'] ?? false)) {
            return self::CONFIRM_UNAUTHORIZED;
        }
        // Replay guard FIRST so a re-sent store receipt is idempotent, never "already published".
        if (($c['purchase_id_provided'] ?? false) && ($c['duplicate_txn_exists'] ?? false)) {
            return self::CONFIRM_DUPLICATE;
        }
        if (($c['listing_status'] ?? null) === 'published') {
            return self::CONFIRM_ALREADY_PUBLISHED;
        }
        // Stuck: paid txn exists but listing never flipped to published.
        if ($c['success_txn_exists'] ?? false) {
            return self::CONFIRM_SELF_HEAL_PUBLISH;
        }
        $confirmableListing = in_array($c['listing_status'] ?? null, ['draft', 'payment_requested'], true);
        if (!$confirmableListing || !($c['has_request'] ?? false)) {
            return self::CONFIRM_NO_ACTIVE_REQUEST;
        }
        if (!in_array($c['request_status'] ?? null, ['payment_initiated', 'pending'], true)) {
            return self::CONFIRM_NOT_CONFIRMABLE;
        }
        if (($c['request_status'] ?? null) === 'pending' && !($c['is_parent_payer'] ?? false)) {
            return self::CONFIRM_PARENT_ONLY;
        }
        return self::CONFIRM_PROCEED;
    }

    /**
     * @param array $c listing_status, is_child, has_parent, existing_request_status
     */
    public function initiate(array $c): string
    {
        if (($c['listing_status'] ?? null) === 'published') {
            return self::INIT_ALREADY_PUBLISHED;
        }
        if (($c['is_child'] ?? false) && !($c['has_parent'] ?? false)) {
            return self::INIT_CHILD_NO_PARENT;
        }
        $existing = $c['existing_request_status'] ?? null;
        if ($existing === 'pending' && ($c['is_child'] ?? false)) {
            return self::INIT_RETURN_EXISTING_PENDING;
        }
        if ($existing === 'payment_initiated') {
            return self::INIT_RETURN_EXISTING_INITIATED;
        }
        // null, failed, parent_rejected -> clean re-initiate
        return self::INIT_CREATE_NEW;
    }

    /**
     * @param array $c listing_published, success_txn_exists, request_status, any_txn_exists
     */
    public function reconcile(array $c): string
    {
        if (!($c['listing_published'] ?? false) && ($c['success_txn_exists'] ?? false)) {
            return self::RECON_PUBLISH;
        }
        if (!($c['listing_published'] ?? false)
            && in_array($c['request_status'] ?? null, ['payment_initiated', 'pending'], true)
            && !($c['any_txn_exists'] ?? false)) {
            return self::RECON_RELEASE;
        }
        return self::RECON_SKIP;
    }
}
