# Hockey Listing Payment Rewrite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make each hockey listing's payment fully independent (listing-scoped) so paying for one listing never blocks publishing the next, fixing the "already purchased / already own this item" bug.

**Architecture:** Extract all payment branching into a pure, DB-free decision class (`HockeyListingPaymentDecider`) that is unit-tested directly. A thin `HockeyListingPaymentService` builds decision context from Eloquent models, calls the decider, and persists. The existing `V4HockeyListingController` payment methods are rewritten to delegate to the service. No SKU-keyed `(user, SKU)` lookups anywhere in the hockey path. Reuse existing `V4PaymentRequest` / `V4PaymentTransaction` / `V4InAppPurchase`. A one-off `hockey:reconcile-listings` Artisan command remediates already-stuck users.

**Tech Stack:** PHP 8 / Laravel, JWT auth (`tymon/jwt-auth`, guard `v4api`), PHPUnit. App-store IAP only (no Stripe).

## Global Constraints

- Payment rail is app-store in-app purchase only. No Stripe. — verbatim from spec §Scope.
- No database migrations. — spec §3.1 / §2.
- Do NOT modify `V4PaymentController::processPayment` or `::isPaymentDone` (shared evaluation/consultation flow). — spec §2 Non-goals.
- No new SKU; no entitlement/subscription/credits model; no server-side receipt verification (seam only). — spec §2 Non-goals.
- Fee SKU resolved via `config('services.hockey_listing.fee_sku')`; fee product MUST be a **consumable** in App Store / Google Play (out-of-code rollout step). — spec §3.2.
- Listing statuses (`V4HockeyListing`): `draft`, `payment_requested`, `payment_failed`, `payment_rejected`, `published`, `sold`. Request statuses (`V4PaymentRequest`): `pending`, `payment_initiated`, `paid`, `parent_rejected`, `failed`. Transaction statuses (`V4PaymentTransaction`): `pending`, `success`, `failed`, `refunded`, `cancelled`. — verbatim from spec §3.3.

---

## File Structure

- **Create** `app/Services/Payments/HockeyListingPaymentDecider.php` — pure decision logic (no DB, no framework). All branching for initiate / confirm / reconcile + `gatewayForSource`. The only place the bug fix logic lives.
- **Create** `tests/Unit/Payments/HockeyListingPaymentDeciderTest.php` — unit tests for the decider (no DB).
- **Create** `app/Services/Payments/HockeyListingPaymentService.php` — orchestration: build context from models, call decider, persist (`V4PaymentRequest`/`V4PaymentTransaction`, `markPaid`/`markPublished`), stamp `meta`. Reused by controller and command.
- **Create** `app/Console/Commands/ReconcileHockeyListings.php` — `hockey:reconcile-listings --dry-run|--apply` remediation command.
- **Create** `tests/Unit/Payments/HockeyListingReconcileClassifierTest.php` — unit tests for the reconcile classifier branch (no DB).
- **Modify** `app/Http/Controllers/V4/V4HockeyListingController.php` — rewrite `initiatePayment`, `confirmPayment`, `paymentStatus`, `rejectPayment` to delegate to the service. Keep `parentListingPayment`, `store`, `update`, `destroy`, `index`, `show`, `nearby`, `myListings` and notification helpers as-is.

Routes are unchanged (`routes/api.php` hockey-listings group). No migrations.

---

### Task 1: Pure decision class — `gatewayForSource` + confirm branching

**Files:**
- Create: `app/Services/Payments/HockeyListingPaymentDecider.php`
- Test: `tests/Unit/Payments/HockeyListingPaymentDeciderTest.php`

**Interfaces:**
- Consumes: nothing (pure PHP, no DB).
- Produces:
  - `HockeyListingPaymentDecider::gatewayForSource(string $source): string` → `'app_store'|'play_store'|'web'`.
  - `HockeyListingPaymentDecider::confirm(array $c): string` returning one of the `CONFIRM_*` constants.
  - Constants: `CONFIRM_UNAUTHORIZED`, `CONFIRM_DUPLICATE`, `CONFIRM_ALREADY_PUBLISHED`, `CONFIRM_SELF_HEAL_PUBLISH`, `CONFIRM_NO_ACTIVE_REQUEST`, `CONFIRM_NOT_CONFIRMABLE`, `CONFIRM_PARENT_ONLY`, `CONFIRM_PROCEED`.
  - `confirm()` context keys (all optional, default false/null): `listing_status`, `has_request`, `request_status`, `is_owner`, `is_parent_payer`, `purchase_id_provided`, `duplicate_txn_exists`, `success_txn_exists`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Payments/HockeyListingPaymentDeciderTest.php`:

```php
<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\HockeyListingPaymentDecider;
use PHPUnit\Framework\TestCase;

class HockeyListingPaymentDeciderTest extends TestCase
{
    private HockeyListingPaymentDecider $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new HockeyListingPaymentDecider();
    }

    public function test_gateway_for_source_maps_store(): void
    {
        $this->assertSame('app_store', $this->d->gatewayForSource('ios'));
        $this->assertSame('play_store', $this->d->gatewayForSource('android'));
        $this->assertSame('web', $this->d->gatewayForSource('web'));
        $this->assertSame('web', $this->d->gatewayForSource('macos'));
    }

    private function confirmBase(array $over = []): array
    {
        return array_merge([
            'listing_status' => 'draft',
            'has_request' => true,
            'request_status' => 'payment_initiated',
            'is_owner' => true,
            'is_parent_payer' => false,
            'purchase_id_provided' => true,
            'duplicate_txn_exists' => false,
            'success_txn_exists' => false,
        ], $over);
    }

    public function test_confirm_unauthorized_when_neither_owner_nor_parent(): void
    {
        $r = $this->d->confirm($this->confirmBase(['is_owner' => false, 'is_parent_payer' => false]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_UNAUTHORIZED, $r);
    }

    public function test_confirm_duplicate_when_purchase_replayed(): void
    {
        $r = $this->d->confirm($this->confirmBase(['duplicate_txn_exists' => true]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_DUPLICATE, $r);
    }

    public function test_confirm_already_published_is_idempotent(): void
    {
        $r = $this->d->confirm($this->confirmBase(['listing_status' => 'published']));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_ALREADY_PUBLISHED, $r);
    }

    public function test_confirm_self_heal_when_success_txn_exists_but_not_published(): void
    {
        $r = $this->d->confirm($this->confirmBase(['success_txn_exists' => true]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_SELF_HEAL_PUBLISH, $r);
    }

    public function test_confirm_no_active_request(): void
    {
        $r = $this->d->confirm($this->confirmBase(['has_request' => false]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_NO_ACTIVE_REQUEST, $r);
    }

    public function test_confirm_not_confirmable_request_status(): void
    {
        $r = $this->d->confirm($this->confirmBase(['request_status' => 'paid']));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_NOT_CONFIRMABLE, $r);
    }

    public function test_confirm_pending_requires_parent(): void
    {
        $r = $this->d->confirm($this->confirmBase([
            'listing_status' => 'payment_requested',
            'request_status' => 'pending',
            'is_owner' => true,
            'is_parent_payer' => false,
        ]));
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PARENT_ONLY, $r);
    }

    public function test_confirm_proceeds_for_valid_adult_payment(): void
    {
        $r = $this->d->confirm($this->confirmBase());
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $r);
    }

    /** The regression: two independent listings both proceed; no cross-listing state exists. */
    public function test_confirm_is_listing_independent_no_user_sku_state(): void
    {
        $listingA = $this->d->confirm($this->confirmBase());
        $listingB = $this->d->confirm($this->confirmBase()); // different listing, same SKU/user
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $listingA);
        $this->assertSame(HockeyListingPaymentDecider::CONFIRM_PROCEED, $listingB);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --testsuite=Unit --filter=HockeyListingPaymentDeciderTest`
Expected: FAIL — `Class "App\Services\Payments\HockeyListingPaymentDecider" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `app/Services/Payments/HockeyListingPaymentDecider.php`:

```php
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
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --testsuite=Unit --filter=HockeyListingPaymentDeciderTest`
Expected: PASS (10 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentDecider.php tests/Unit/Payments/HockeyListingPaymentDeciderTest.php
git commit -m "feat(hockey): add pure payment decider with confirm branching + gateway map"
```

---

### Task 2: Decider — initiate + reconcile branching

**Files:**
- Modify: `app/Services/Payments/HockeyListingPaymentDecider.php`
- Test: `tests/Unit/Payments/HockeyListingPaymentDeciderTest.php` (extend), `tests/Unit/Payments/HockeyListingReconcileClassifierTest.php` (create)

**Interfaces:**
- Consumes: `HockeyListingPaymentDecider` from Task 1.
- Produces:
  - `initiate(array $c): string` → one of `INIT_ALREADY_PUBLISHED`, `INIT_CHILD_NO_PARENT`, `INIT_RETURN_EXISTING_PENDING`, `INIT_RETURN_EXISTING_INITIATED`, `INIT_CREATE_NEW`. Context keys: `listing_status`, `is_child`, `has_parent`, `existing_request_status`.
  - `reconcile(array $c): string` → one of `RECON_PUBLISH`, `RECON_RELEASE`, `RECON_SKIP`. Context keys: `listing_published`, `success_txn_exists`, `request_status`, `any_txn_exists`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Unit/Payments/HockeyListingPaymentDeciderTest.php` (inside the class):

```php
    private function initBase(array $over = []): array
    {
        return array_merge([
            'listing_status' => 'draft',
            'is_child' => false,
            'has_parent' => false,
            'existing_request_status' => null,
        ], $over);
    }

    public function test_initiate_blocks_when_already_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_ALREADY_PUBLISHED,
            $this->d->initiate($this->initBase(['listing_status' => 'published']))
        );
    }

    public function test_initiate_blocks_child_without_parent(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CHILD_NO_PARENT,
            $this->d->initiate($this->initBase(['is_child' => true, 'has_parent' => false]))
        );
    }

    public function test_initiate_returns_existing_pending_for_child(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_RETURN_EXISTING_PENDING,
            $this->d->initiate($this->initBase([
                'is_child' => true, 'has_parent' => true, 'existing_request_status' => 'pending',
            ]))
        );
    }

    public function test_initiate_returns_existing_initiated(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_RETURN_EXISTING_INITIATED,
            $this->d->initiate($this->initBase(['existing_request_status' => 'payment_initiated']))
        );
    }

    public function test_initiate_creates_new_for_fresh_or_dead_request(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => null]))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => 'failed']))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::INIT_CREATE_NEW,
            $this->d->initiate($this->initBase(['existing_request_status' => 'parent_rejected']))
        );
    }
```

Create `tests/Unit/Payments/HockeyListingReconcileClassifierTest.php`:

```php
<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\HockeyListingPaymentDecider;
use PHPUnit\Framework\TestCase;

class HockeyListingReconcileClassifierTest extends TestCase
{
    private HockeyListingPaymentDecider $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new HockeyListingPaymentDecider();
    }

    private function base(array $over = []): array
    {
        return array_merge([
            'listing_published' => false,
            'success_txn_exists' => false,
            'request_status' => null,
            'any_txn_exists' => false,
        ], $over);
    }

    public function test_publish_when_success_txn_but_not_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_PUBLISH,
            $this->d->reconcile($this->base(['success_txn_exists' => true]))
        );
    }

    public function test_release_when_stuck_request_and_no_txn(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_RELEASE,
            $this->d->reconcile($this->base(['request_status' => 'payment_initiated']))
        );
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_RELEASE,
            $this->d->reconcile($this->base(['request_status' => 'pending']))
        );
    }

    public function test_skip_when_published(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_SKIP,
            $this->d->reconcile($this->base(['listing_published' => true, 'success_txn_exists' => true]))
        );
    }

    public function test_skip_when_nothing_to_do(): void
    {
        $this->assertSame(
            HockeyListingPaymentDecider::RECON_SKIP,
            $this->d->reconcile($this->base(['request_status' => 'paid']))
        );
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --testsuite=Unit --filter=HockeyListing`
Expected: FAIL — `Call to undefined method ...::initiate()` / `::reconcile()`.

- [ ] **Step 3: Add the methods + constants**

In `app/Services/Payments/HockeyListingPaymentDecider.php`, add the constants after the confirm constants:

```php
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
```

And add the methods after `confirm()`:

```php
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
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --testsuite=Unit --filter=HockeyListing`
Expected: PASS (all decider + reconcile classifier tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentDecider.php tests/Unit/Payments/HockeyListingPaymentDeciderTest.php tests/Unit/Payments/HockeyListingReconcileClassifierTest.php
git commit -m "feat(hockey): add initiate + reconcile decision branching with unit tests"
```

---

### Task 3: Payment service — orchestration over the decider

**Files:**
- Create: `app/Services/Payments/HockeyListingPaymentService.php`

**Interfaces:**
- Consumes: `HockeyListingPaymentDecider` (Task 1–2); models `V4HockeyListing`, `V4PaymentRequest`, `V4PaymentTransaction`, `V4InAppPurchase`, `V4User`.
- Produces (called by controller in Tasks 5–8 and command in Task 9):
  - `initiate(V4HockeyListing $listing, V4User $actor): array` — `['code' => string, 'http' => int, 'payload' => array]`.
  - `confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array` — same shape. `$receipt` keys: `purchase_id`, `source`, `verification_data`, `store_status`, `transaction_date`, `payload`.
  - `status(V4HockeyListing $listing): array` — `['payload' => array]`.
  - `reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void`.
  - `feeProduct(): ?V4InAppPurchase` — resolves the configured fee SKU (active).

This task only creates the class with `feeProduct()` plus method stubs that throw `LogicException('not implemented')`, so later tasks fill each method behind its own test/verification. (No behavior to unit-test here; verified by `php -l` and that Tasks 5–9 compile against it.)

- [ ] **Step 1: Create the service skeleton**

Create `app/Services/Payments/HockeyListingPaymentService.php`:

```php
<?php

namespace App\Services\Payments;

use App\Models\V4HockeyListing;
use App\Models\V4InAppPurchase;
use App\Models\V4PaymentRequest;
use App\Models\V4PaymentTransaction;
use App\Models\V4User;
use Illuminate\Support\Facades\DB;
use LogicException;

class HockeyListingPaymentService
{
    public function __construct(private HockeyListingPaymentDecider $decider)
    {
    }

    public function feeProduct(): ?V4InAppPurchase
    {
        return V4InAppPurchase::where('sku', config('services.hockey_listing.fee_sku'))
            ->where('active', true)
            ->first();
    }

    public function initiate(V4HockeyListing $listing, V4User $actor): array
    {
        throw new LogicException('not implemented');
    }

    public function confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array
    {
        throw new LogicException('not implemented');
    }

    public function status(V4HockeyListing $listing): array
    {
        throw new LogicException('not implemented');
    }

    public function reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void
    {
        throw new LogicException('not implemented');
    }
}
```

- [ ] **Step 2: Lint**

Run: `php -l app/Services/Payments/HockeyListingPaymentService.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentService.php
git commit -m "chore(hockey): add HockeyListingPaymentService skeleton"
```

---

### Task 4: Service `initiate()` — listing-scoped request creation + clean re-initiate

**Files:**
- Modify: `app/Services/Payments/HockeyListingPaymentService.php`

**Interfaces:**
- Produces: `initiate(V4HockeyListing $listing, V4User $actor): array` with `code` ∈ decider `INIT_*` plus `'fee_missing'`, `http`, and `payload` containing `awaiting_parent`, `payment_request_id`, `sku`, `amount_cents`, `currency`, `formatted_amount`.

- [ ] **Step 1: Implement `initiate()`**

Replace the `initiate()` stub body in `app/Services/Payments/HockeyListingPaymentService.php`:

```php
    public function initiate(V4HockeyListing $listing, V4User $actor): array
    {
        $isChild = (bool) ($actor->is_child ?? false);
        $parentId = $isChild ? $actor->parent_id : null;

        $existing = $listing->payment_request_id
            ? V4PaymentRequest::with('inAppPurchase')->find($listing->payment_request_id)
            : null;

        $code = $this->decider->initiate([
            'listing_status' => $listing->status,
            'is_child' => $isChild,
            'has_parent' => (bool) $parentId,
            'existing_request_status' => $existing?->status,
        ]);

        if ($code === HockeyListingPaymentDecider::INIT_ALREADY_PUBLISHED) {
            return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Listing is already published.']];
        }
        if ($code === HockeyListingPaymentDecider::INIT_CHILD_NO_PARENT) {
            return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Child account is missing a parent. Cannot request payment.']];
        }
        if ($code === HockeyListingPaymentDecider::INIT_RETURN_EXISTING_PENDING) {
            return ['code' => $code, 'http' => 200, 'payload' => $this->requestPayload($existing, true, null)];
        }
        if ($code === HockeyListingPaymentDecider::INIT_RETURN_EXISTING_INITIATED) {
            return ['code' => $code, 'http' => 200, 'payload' => $this->requestPayload($existing, false, optional($existing->inAppPurchase)->sku)];
        }

        // INIT_CREATE_NEW
        $fee = $this->feeProduct();
        if (!$fee) {
            return ['code' => 'fee_missing', 'http' => 404, 'payload' => ['message' => 'Listing fee product not found or inactive.']];
        }

        $request = DB::transaction(function () use ($listing, $actor, $isChild, $parentId, $fee) {
            $data = [
                'payer_id' => $isChild ? $parentId : $actor->id,
                'player_id' => $actor->id,
                'in_app_purchase_id' => $fee->id,
                'amount_cents' => $fee->amount_cents,
                'currency' => $fee->currency,
                'status' => $isChild
                    ? V4PaymentRequest::STATUS_PENDING
                    : V4PaymentRequest::STATUS_PAYMENT_INITIATED,
                'meta' => ['purpose' => 'hockey_listing', 'listing_id' => $listing->id],
            ];
            if ($isChild) {
                $data['parent_id'] = $parentId;
            }
            $request = V4PaymentRequest::create($data);

            $listing->payment_request_id = $request->id;
            $listing->status = $isChild
                ? V4HockeyListing::STATUS_PAYMENT_REQUESTED
                : V4HockeyListing::STATUS_DRAFT;
            $listing->save();

            return $request;
        });

        $request->setRelation('inAppPurchase', $fee);

        return [
            'code' => $code,
            'http' => 201,
            'payload' => $this->requestPayload($request, $isChild, $isChild ? null : $fee->sku),
            'created' => true,
            'is_child' => $isChild,
            'request' => $request,
            'fee' => $fee,
        ];
    }

    private function requestPayload(V4PaymentRequest $request, bool $awaitingParent, ?string $sku): array
    {
        return [
            'awaiting_parent' => $awaitingParent,
            'payment_request_id' => $request->id,
            'sku' => $sku,
            'amount_cents' => $request->amount_cents,
            'currency' => $request->currency,
            'formatted_amount' => $request->formatted_amount,
        ];
    }
```

- [ ] **Step 2: Lint**

Run: `php -l app/Services/Payments/HockeyListingPaymentService.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Confirm decider tests still pass**

Run: `php artisan test --testsuite=Unit --filter=HockeyListing`
Expected: PASS (logic the service relies on is covered by Task 1–2 tests).

- [ ] **Step 4: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentService.php
git commit -m "feat(hockey): implement listing-scoped initiate with clean re-initiate + meta stamp"
```

---

### Task 5: Service `confirm()` — dedup-only, gateway-from-source, self-heal, publish

**Files:**
- Modify: `app/Services/Payments/HockeyListingPaymentService.php`

**Interfaces:**
- Produces: `confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array`. `code` ∈ decider `CONFIRM_*`; `http`; `payload` with listing/transaction ids. Records `V4PaymentTransaction` with `gateway` from `decider->gatewayForSource($receipt['source'])`.

- [ ] **Step 1: Implement `confirm()`**

Replace the `confirm()` stub body:

```php
    public function confirm(V4HockeyListing $listing, V4User $actor, array $receipt): array
    {
        $request = $listing->payment_request_id
            ? V4PaymentRequest::find($listing->payment_request_id)
            : null;

        $authId = (int) $actor->id;
        $isOwner = (int) $listing->user_id === $authId;
        $isParentPayer = $request && $request->parent_id && (int) $request->parent_id === $authId;

        $purchaseId = $receipt['purchase_id'] ?? null;
        $source = $receipt['source'];

        $duplicate = null;
        if (!empty($purchaseId)) {
            $duplicate = V4PaymentTransaction::where('purchase_id', $purchaseId)
                ->where('source', $source)
                ->first();
        }

        $successTxn = $request
            ? V4PaymentTransaction::where('payment_request_id', $request->id)
                ->where('status', V4PaymentTransaction::STATUS_SUCCESS)
                ->latest('id')
                ->first()
            : null;

        $code = $this->decider->confirm([
            'listing_status' => $listing->status,
            'has_request' => (bool) $request,
            'request_status' => $request?->status,
            'is_owner' => $isOwner,
            'is_parent_payer' => $isParentPayer,
            'purchase_id_provided' => !empty($purchaseId),
            'duplicate_txn_exists' => (bool) $duplicate,
            'success_txn_exists' => (bool) $successTxn,
        ]);

        switch ($code) {
            case HockeyListingPaymentDecider::CONFIRM_UNAUTHORIZED:
                return ['code' => $code, 'http' => 403, 'payload' => ['message' => 'Unauthorized.']];

            case HockeyListingPaymentDecider::CONFIRM_DUPLICATE:
                // Idempotent: the store receipt was already processed.
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Payment already processed.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_transaction_id' => $duplicate->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_ALREADY_PUBLISHED:
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Listing is already published.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_transaction_id' => $successTxn?->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_SELF_HEAL_PUBLISH:
                DB::transaction(function () use ($request, $listing) {
                    $request->markPaid();
                    $listing->markPublished();
                });
                return ['code' => $code, 'http' => 200, 'payload' => [
                    'message' => 'Listing published.',
                    'listing_id' => $listing->id,
                    'listing_status' => $listing->status,
                    'payment_request_id' => $request->id,
                    'payment_transaction_id' => $successTxn->id,
                ]];

            case HockeyListingPaymentDecider::CONFIRM_NO_ACTIVE_REQUEST:
                return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'No active payment request found. Call initiate-payment first.']];

            case HockeyListingPaymentDecider::CONFIRM_NOT_CONFIRMABLE:
                return ['code' => $code, 'http' => 400, 'payload' => ['message' => 'Payment request is not in a confirmable state.']];

            case HockeyListingPaymentDecider::CONFIRM_PARENT_ONLY:
                return ['code' => $code, 'http' => 403, 'payload' => ['message' => 'Only the parent can confirm this payment.']];
        }

        // CONFIRM_PROCEED — record transaction + publish.
        // SEAM: server-side receipt verification (Apple/Google) can be inserted here before success.
        $transaction = DB::transaction(function () use ($request, $actor, $receipt, $source, $listing) {
            $txn = V4PaymentTransaction::create([
                'payment_request_id' => $request->id,
                'payer_id' => $actor->id,
                'amount_cents' => $request->amount_cents,
                'currency' => $request->currency,
                'gateway' => $this->decider->gatewayForSource($source),
                'gateway_reference' => $source . '_' . uniqid() . '_' . time(),
                'status' => V4PaymentTransaction::STATUS_SUCCESS,
                'purchase_id' => $receipt['purchase_id'] ?? null,
                'source' => $source,
                'verification_data' => $receipt['verification_data'] ?? null,
                'store_status' => $receipt['store_status'] ?? null,
                'transaction_date' => $receipt['transaction_date'] ?? null,
                'payload' => $receipt['payload'] ?? null,
            ]);
            $request->markPaid();
            $listing->markPublished();
            return $txn;
        });

        return [
            'code' => $code,
            'http' => 200,
            'payload' => [
                'message' => 'Payment confirmed. Your listing is now live.',
                'listing_id' => $listing->id,
                'listing_status' => $listing->status,
                'listed_at' => $listing->listed_at,
                'payment_request_id' => $request->id,
                'payment_transaction_id' => $transaction->id,
            ],
            'request' => $request,
            'listing' => $listing,
            'is_parent_payer' => $isParentPayer,
        ];
    }
```

- [ ] **Step 2: Lint**

Run: `php -l app/Services/Payments/HockeyListingPaymentService.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Re-run decider tests (the logic these branches follow)**

Run: `php artisan test --testsuite=Unit --filter=HockeyListing`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentService.php
git commit -m "feat(hockey): implement listing-scoped confirm (dedup-only, gateway from source, self-heal)"
```

---

### Task 6: Service `status()` + `reject()`

**Files:**
- Modify: `app/Services/Payments/HockeyListingPaymentService.php`

**Interfaces:**
- Produces:
  - `status(V4HockeyListing $listing): array` — `payload` with `listing_id`, `listing_status`, `is_published`, `awaiting_parent`, `payment_request_id`, `payment_status`, `sku`, `amount_cents`, `currency`, `formatted_amount`.
  - `reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void` — marks request `parent_rejected`, listing `payment_rejected`, deletes the request notification.

- [ ] **Step 1: Implement `status()` and `reject()`**

Replace the two stub bodies:

```php
    public function status(V4HockeyListing $listing): array
    {
        $request = $listing->relationLoaded('paymentRequest')
            ? $listing->paymentRequest
            : $listing->load('paymentRequest.inAppPurchase')->paymentRequest;

        return [
            'payload' => [
                'listing_id' => $listing->id,
                'listing_status' => $listing->status,
                'is_published' => $listing->status === V4HockeyListing::STATUS_PUBLISHED,
                'awaiting_parent' => $request
                    && $request->status === V4PaymentRequest::STATUS_PENDING,
                'payment_request_id' => $request?->id,
                'payment_status' => $request?->status,
                'sku' => optional($request?->inAppPurchase)->sku,
                'amount_cents' => $request?->amount_cents,
                'currency' => $request?->currency,
                'formatted_amount' => $request?->formatted_amount,
            ],
        ];
    }

    public function reject(V4PaymentRequest $request, V4HockeyListing $listing, ?string $reason): void
    {
        DB::transaction(function () use ($request, $listing, $reason) {
            $request->markParentRejected($reason);
            $listing->markPaymentRejected();
        });

        $request->loadMissing('notification');
        if ($request->notification) {
            $request->notification->delete();
        }
    }
```

- [ ] **Step 2: Lint**

Run: `php -l app/Services/Payments/HockeyListingPaymentService.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/Services/Payments/HockeyListingPaymentService.php
git commit -m "feat(hockey): implement payment status + parent reject in service"
```

---

### Task 7: Rewrite controller `confirmPayment` + `paymentStatus` to delegate

**Files:**
- Modify: `app/Http/Controllers/V4/V4HockeyListingController.php:212-401` (`confirmPayment`), `:407-468` (`paymentStatus`)

**Interfaces:**
- Consumes: `HockeyListingPaymentService::confirm()`, `::status()`.
- Produces: unchanged route responses (`POST hockey-listings/{listing}/confirm-payment`, `GET hockey-listings/{listing}/payment-status`).

- [ ] **Step 1: Add the service import + constructor injection**

At the top of `app/Http/Controllers/V4/V4HockeyListingController.php`, add to the `use` block:

```php
use App\Services\Payments\HockeyListingPaymentService;
```

If the controller has no constructor, add one; if it has one, add the parameter. Add:

```php
    public function __construct(private HockeyListingPaymentService $hockeyPayments)
    {
    }
```

- [ ] **Step 2: Replace `confirmPayment` body**

Replace the entire `confirmPayment` method (`:212-401`) with:

```php
    public function confirmPayment(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing confirm payment', ['user_id' => $user->id, 'listing_id' => $listing, 'payload' => $request->all()]);

            $record = V4HockeyListing::find($listing);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Listing not found.'], 404);
            }

            $validated = $request->validate([
                'purchase_id' => 'nullable|string',
                'source' => 'required|in:ios,android,web,window,linux,macos',
                'verification_data' => 'nullable|array',
                'store_status' => 'nullable|string',
                'transaction_date' => 'nullable|date',
                'payload' => 'nullable|array',
            ]);

            $result = $this->hockeyPayments->confirm($record, $user, $validated);

            // Side-effect notifications preserved from the previous implementation.
            if ($result['code'] === \App\Services\Payments\HockeyListingPaymentDecider::CONFIRM_PROCEED
                && ($result['is_parent_payer'] ?? false)) {
                $req = $result['request'];
                $req->loadMissing('notification');
                if ($req->notification) {
                    $req->notification->delete();
                }
                $this->sendListingPaymentApprovedNotification($req, $result['listing']);
            }

            $success = in_array($result['http'], [200, 201], true);
            return response()->json(array_merge(
                ['success' => $success],
                $result['payload'],
            ), $result['http']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to confirm hockey listing payment', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
```

- [ ] **Step 3: Replace `paymentStatus` body**

Replace the `paymentStatus` method (`:407-468`) with:

```php
    public function paymentStatus(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing payment status', ['user_id' => $user->id, 'listing_id' => $listing]);

            $record = V4HockeyListing::with('paymentRequest.inAppPurchase')->find($listing);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Listing not found.'], 404);
            }

            $paymentRequest = $record->paymentRequest;
            $authId = (int) $user->id;
            $isOwner = (int) $record->user_id === $authId;
            $isParentPayer = $paymentRequest && $paymentRequest->parent_id
                && (int) $paymentRequest->parent_id === $authId;

            if (!$isOwner && !$isParentPayer) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $result = $this->hockeyPayments->status($record);
            return response()->json(array_merge(
                ['success' => true, 'message' => 'Listing payment status loaded.', 'data' => $result['payload']],
            ));
        } catch (Exception $e) {
            Log::error('Failed to load hockey listing payment status', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment status.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
```

- [ ] **Step 4: Lint**

Run: `php -l app/Http/Controllers/V4/V4HockeyListingController.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: Manual verification (real DB) — the regression**

With a logged-in (non-child) user JWT, run via `php artisan tinker` or HTTP:

1. Create listing A → `POST hockey-listings/{A}/initiate-payment` → expect `payment_request_id`, `sku`.
2. `POST hockey-listings/{A}/confirm-payment` `{ "purchase_id": "txnA", "source": "ios" }` → expect `success:true`, `listing_status:"published"`.
3. Create listing B → initiate → `POST hockey-listings/{B}/confirm-payment` `{ "purchase_id": "txnB", "source": "ios" }` → **expect `success:true`, `published`** (previously blocked with "already purchased").
4. Repeat step 2 for listing A with same `purchase_id:"txnA"` → expect `success:true` idempotent "Payment already processed." (HTTP 200), no error.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/V4/V4HockeyListingController.php
git commit -m "refactor(hockey): delegate confirm-payment + payment-status to service (fixes already-purchased)"
```

---

### Task 8: Rewrite controller `initiatePayment` + `rejectPayment` to delegate

**Files:**
- Modify: `app/Http/Controllers/V4/V4HockeyListingController.php:40-206` (`initiatePayment`), `:474-584` (`rejectPayment`)

**Interfaces:**
- Consumes: `HockeyListingPaymentService::initiate()`, `::reject()`; existing helper `sendListingPaymentRequestNotification`.

- [ ] **Step 1: Replace `initiatePayment` body**

Replace the `initiatePayment` method (`:40-206`) with:

```php
    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            Log::info('Hockey listing initiate payment', ['user_id' => $user->id, 'payload' => $request->all()]);

            $validated = $request->validate(['listing_id' => 'required|integer']);

            $listing = V4HockeyListing::where('id', $validated['listing_id'])
                ->where('user_id', $user->id)
                ->first();
            if (!$listing) {
                return response()->json(['success' => false, 'message' => 'Listing not found.'], 404);
            }

            $result = $this->hockeyPayments->initiate($listing, $user);

            // Notify parent only when a brand-new child request was created.
            if (($result['created'] ?? false) && ($result['is_child'] ?? false)) {
                $req = $result['request'];
                $req->load(['player', 'parent']);
                $this->sendListingPaymentRequestNotification($req, $listing, $result['fee']);
            }

            $success = in_array($result['http'], [200, 201], true);
            return response()->json(array_merge(['success' => $success], $this->initiateEnvelope($result)), $result['http']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to initiate hockey listing payment', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    private function initiateEnvelope(array $result): array
    {
        // Success results carry a data payload; error results carry a message.
        if (isset($result['payload']['message']) && $result['http'] >= 400) {
            return ['message' => $result['payload']['message']];
        }
        $message = $result['http'] === 201
            ? (($result['is_child'] ?? false) ? 'Payment request sent to parent.' : 'Payment initiated. Complete purchase then call confirm-payment.')
            : 'Payment already in progress.';
        return ['message' => $message, 'data' => $result['payload']];
    }
```

- [ ] **Step 2: Replace `rejectPayment` body**

Replace the `rejectPayment` method (`:474-584`) with:

```php
    public function rejectPayment(Request $request, int $listing): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();
            $validated = $request->validate(['reason' => 'nullable|string|max:500']);

            $record = V4HockeyListing::find($listing);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Listing not found.'], 404);
            }

            $paymentRequest = $record->payment_request_id
                ? V4PaymentRequest::find($record->payment_request_id)
                : null;

            $isParentPayer = $paymentRequest && $paymentRequest->parent_id
                && (int) $paymentRequest->parent_id === (int) $user->id;
            if (!$isParentPayer) {
                return response()->json(['success' => false, 'message' => 'Only the parent can decline this payment.'], 403);
            }
            if ($record->status === V4HockeyListing::STATUS_PUBLISHED) {
                return response()->json(['success' => false, 'message' => 'Listing is already published.'], 400);
            }
            if ($paymentRequest->status !== V4PaymentRequest::STATUS_PENDING) {
                return response()->json(['success' => false, 'message' => 'Payment request is not pending.'], 400);
            }

            $this->hockeyPayments->reject($paymentRequest, $record, $validated['reason'] ?? null);
            $this->sendListingPaymentRejectedNotification($paymentRequest, $record);

            return response()->json([
                'success' => true,
                'message' => 'Listing payment declined.',
                'data' => ['listing_id' => $record->id, 'listing_status' => $record->status],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to reject hockey listing payment', [
                'user_id' => Auth::id(),
                'listing_id' => $listing,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject payment.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
```

- [ ] **Step 3: Lint**

Run: `php -l app/Http/Controllers/V4/V4HockeyListingController.php`
Expected: `No syntax errors detected`.

- [ ] **Step 4: Manual verification — re-initiate after reject**

1. Child creates listing → initiate → parent `POST {listing}/reject-payment` → listing `payment_rejected`.
2. Child calls initiate again on same listing → expect a fresh `payment_request_id` (HTTP 201), not a lockout.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/V4/V4HockeyListingController.php
git commit -m "refactor(hockey): delegate initiate-payment + reject-payment to service"
```

---

### Task 9: Reconciliation command `hockey:reconcile-listings`

**Files:**
- Create: `app/Console/Commands/ReconcileHockeyListings.php`

**Interfaces:**
- Consumes: `HockeyListingPaymentDecider::reconcile()`, models. (Classifier already unit-tested in Task 2.)
- Produces: artisan command `hockey:reconcile-listings {--apply}` (default = dry-run). Prints per-bucket counts; with `--apply` publishes paid-but-unpublished listings and releases stuck-no-txn requests.

- [ ] **Step 1: Create the command**

Create `app/Console/Commands/ReconcileHockeyListings.php`:

```php
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
```

- [ ] **Step 2: Lint + command registration check**

Run: `php -l app/Console/Commands/ReconcileHockeyListings.php`
Expected: `No syntax errors detected`.

Run: `php artisan list | grep hockey`
Expected: `hockey:reconcile-listings` listed (Laravel auto-discovers commands in `app/Console/Commands`).

- [ ] **Step 3: Re-run reconcile classifier unit tests**

Run: `php artisan test --testsuite=Unit --filter=HockeyListingReconcileClassifier`
Expected: PASS.

- [ ] **Step 4: Dry-run against the real DB**

Run: `php artisan hockey:reconcile-listings`
Expected: prints `[DRY-RUN] publish=… release=… skip=…`, no data changes. Review counts before applying with `--apply`.

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/ReconcileHockeyListings.php
git commit -m "feat(hockey): add reconcile-listings remediation command (dry-run default)"
```

---

### Task 10: Full suite + docs note

**Files:**
- Modify: `docs/superpowers/specs/2026-06-19-hockey-listing-payment-rewrite-design.md` (append rollout checklist confirmation only if needed)

- [ ] **Step 1: Run the full unit suite**

Run: `php artisan test --testsuite=Unit`
Expected: PASS (decider + reconcile classifier; existing `ExampleTest` unaffected).

- [ ] **Step 2: Lint all touched PHP**

Run:
```bash
php -l app/Services/Payments/HockeyListingPaymentDecider.php && \
php -l app/Services/Payments/HockeyListingPaymentService.php && \
php -l app/Console/Commands/ReconcileHockeyListings.php && \
php -l app/Http/Controllers/V4/V4HockeyListingController.php
```
Expected: `No syntax errors detected` for each.

- [ ] **Step 3: Commit (if any doc edits)**

```bash
git add -A
git commit -m "docs(hockey): finalize payment rewrite plan verification"
```

---

## Out-of-code rollout (operator checklist, not a code task)

1. Set the fee product (`HOCKEY_LISTING_FEE_SKU`) to **consumable** in App Store Connect and Google Play.
2. Deploy backend.
3. `php artisan hockey:reconcile-listings` (dry-run) → review counts → `php artisan hockey:reconcile-listings --apply`.
4. Ship mobile change: use `GET hockey-listings/{listing}/payment-status` for paid/unpaid; stop calling `isPaymentDone(SKU)`/`processPayment(SKU)` for the fee; support "restore"/re-send-receipt.

## Self-Review

- **Spec coverage:** §3.1 zero-migration + meta stamp → Task 4. §3.2 consumable → rollout checklist + Global Constraints. §3.3 state machine → Tasks 1,2,4,5. §3.4 per-endpoint rewrite → confirm/status Task 7, initiate/reject Task 8 (parentListingPayment unchanged, as specified). §4 client contract → rollout step 4. §5 remediation: self-healing → Task 5 (CONFIRM_SELF_HEAL_PUBLISH) + Task 8 (clean re-initiate); reconcile command → Task 9; charged-but-no-record/restore → CONFIRM_DUPLICATE idempotency (Task 5) + rollout step 4. §6 tests → Tasks 1,2 (decider/classifier) + Task 7 step 5 manual regression. §7 rollout → checklist. §8 risks → addressed (mobile contract in rollout; consumable in rollout; concurrency via dedup+DB transaction in Task 5).
- **Placeholder scan:** none — every step has full code/commands.
- **Type consistency:** decider constants/method names (`confirm`, `initiate`, `reconcile`, `gatewayForSource`, `CONFIRM_*`, `INIT_*`, `RECON_*`) used identically in service (Tasks 4–6), controller (Tasks 7–8), command (Task 9), and tests (Tasks 1–2). Service method names (`initiate`, `confirm`, `status`, `reject`, `feeProduct`) consistent across skeleton (Task 3) and callers.

## Known limitation (honest note)

Full HTTP feature tests are not included because the suite has no test-DB harness (SQLite RefreshDatabase is blocked by 26 Postgres/MySQL-only migrations, there is no `.env.testing`, and `V4UserFactory` is empty). The bug's branching logic is fully covered by DB-free unit tests (Tasks 1–2); controller/service wiring is verified by lint + the manual regression in Task 7 Step 5. If a dedicated migrated test DB is later configured (`.env.testing` + `DatabaseTransactions`), add feature tests using `actingAs($user, 'v4api')` over the four payment routes.
