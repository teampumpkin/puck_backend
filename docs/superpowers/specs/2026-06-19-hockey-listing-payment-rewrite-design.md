# Hockey Listing Payment Rewrite — Design

- **Date:** 2026-06-19
- **Status:** Approved (design), pending spec review
- **Scope:** `puck_backend` (Laravel) hockey-listing payment flow only. No Stripe. Payment rail stays app-store in-app purchase (IAP).
- **Author:** mihir.pipermitwala@teampumpkin.com

## 1. Problem

Users report **"already purchased / already own this item"** when trying to publish a second (and subsequent) hockey listing.

The hockey listing fee is **one SKU** (`HOCKEY_LISTING_FEE_SKU`, currently `test_marketplace_listing_min_fee`) charged **per listing** (0.99 CAD each). The model is correct: pay once per item, that item publishes, repeat for the next item — unlimited items, one payment each.

The bug is that payment **state is checked per `(user, SKU)`**, not per listing. Because the same SKU is reused for every listing, the checks collide across listings.

### Root cause (evidence)

- `V4PaymentController::isPaymentDone` (`app/Http/Controllers/V4/V4PaymentController.php:453`): queries `V4PaymentRequest where payer_id + in_app_purchase_id + status=PAID`. After the first listing is paid, this returns `is_paid=true` **forever** for that SKU → the client believes the fee is already paid → "you already own this item" → never charges or publishes the next listing.
- `V4PaymentController::processPayment` (`:90`): fetches the latest `V4PaymentRequest` for `(player_id, in_app_purchase_id)`. After listing #1 is `PAID`, paying for listing #2 enters the "handle paid status" branch (`:110`) which looks for an `EvaluationSubmission` (never exists for hockey) and returns **"Submission is pending for previous payment"** (400) — blocking listing #2.

These two endpoints are the shared **evaluation/consultation** payment path ("big app approach"). They are correct for products where one SKU = one purchasable thing per cycle, but wrong for a per-listing consumable fee.

The dedicated hockey `confirmPayment` (`app/Http/Controllers/V4/V4HockeyListingController.php:212`) is already **listing-scoped** (keys off `listing.payment_request_id`). The bug surfaces whenever the client/flow uses the **SKU-keyed** shared checks for the hockey fee.

### Second, store-side source of the same message

If the fee product is configured as **non-consumable** in App Store / Google Play, the **store itself** returns "you already own this item" on repurchase. This is independent of the backend and is fixed only by making the product **consumable**.

A *new SKU* is explicitly **not** a fix: it resets the per-SKU collision by exactly one listing, then breaks again on the next listing (SKU still reused). New-SKU idea is dropped.

## 2. Goals / Non-goals

**Goals**
- Each hockey listing's payment is fully independent (listing-scoped). Paying for listing N never affects listing N+1.
- Reuse existing infra: `V4PaymentRequest` + `V4PaymentTransaction` + `V4InAppPurchase` (IAP) and the parent-approval flow. Mirror the big-app structure without mutating the shared `processPayment`/`isPaymentDone`.
- Remediate users already stuck by the bug.
- No schema migrations.

**Non-goals**
- No Stripe.
- No server-side receipt verification in this change (leave a seam to add later).
- No changes to evaluation/consultation `processPayment`/`isPaymentDone`.
- No new SKU. No entitlement/subscription/credits model.

## 3. Design — Approach A (listing-scoped)

### 3.1 Data model — zero migrations
- `v4_hockey_listings.payment_request_id` already links a listing to its payment request.
- `v4_payment_transactions` already has a unique `(purchase_id, source)` constraint (migration `2025_11_06_125918`).
- Stamp `v4_payment_requests.meta` (existing JSON column) with `{ "purpose": "hockey_listing", "listing_id": <id> }` on create — traceability and clean admin queries. No schema change.
- Fee remains a `V4InAppPurchase` row resolved via `config('services.hockey_listing.fee_sku')`.

### 3.2 Store configuration (required, out-of-code)
- The fee product (`HOCKEY_LISTING_FEE_SKU`) **must be a consumable** product in App Store Connect and Google Play. This makes repurchase possible and removes the store-side "already own" error.

### 3.3 State machine (per listing, independent)

```
create listing                 → status = draft
initiate-payment(listing)       → create V4PaymentRequest (this listing only)
                                    child  → request PENDING            (listing → payment_requested, notify parent)
                                    adult  → request PAYMENT_INITIATED  (listing stays draft)
[device buys the consumable IAP fee]
confirm-payment(listing)        → dedup(purchase_id, source);
                                    record V4PaymentTransaction (success);
                                    request.markPaid(); listing.markPublished()  (sets listed_at)
```

"Is **this listing** paid?" = `listing.status === published` (backed by its own `payment_request.status === paid`). The client uses `GET payment-status/{listing}` — **never** `isPaymentDone(SKU)`.

Listing statuses (existing constants on `V4HockeyListing`): `draft`, `payment_requested`, `payment_failed`, `payment_rejected`, `published`, `sold`. Payment-request statuses (`V4PaymentRequest`): `pending`, `payment_initiated`, `paid`, `parent_rejected`, `failed`. Transaction statuses (`V4PaymentTransaction`): `pending`, `success`, `failed`, `refunded`, `cancelled`.

### 3.4 Code changes per endpoint

`app/Http/Controllers/V4/V4HockeyListingController.php`

- **`initiatePayment`** (rewrite)
  - Resolve fee IAP by config SKU (active). 404 if missing.
  - Per-listing payment request; stamp `meta.purpose/listing_id`.
  - Idempotency for genuinely in-flight requests only: existing `PENDING` (child, awaiting parent) or `PAYMENT_INITIATED` → return the existing request.
  - **Clean re-initiate:** if the listing's prior request is `failed` or `parent_rejected`, create a **fresh** request and reset the listing to `draft` (adult) / `payment_requested` (child). No stale-request lockout.
  - No `(user, SKU)` lookups.

- **`confirmPayment`** (rewrite)
  - Authorize: listing owner, or parent payer of the attached request.
  - **Dedup on `(purchase_id, source)` only** (replay guard). A duplicate returns an idempotent signal referencing the existing transaction — not a hard failure that reads as "already purchased".
  - Validate the listing is confirmable (`draft`/`payment_requested` + has a request) and the request status is `PAYMENT_INITIATED`/`PENDING`. `PENDING` may be confirmed only by the parent payer.
  - Record `V4PaymentTransaction` with **`gateway` derived from `source`** (`app_store` for ios, `play_store` for android, else `web`) — not hardcoded `'internal'`. Leave a clearly-marked seam where server-side receipt verification can be inserted before marking success.
  - `request.markPaid()` + `listing.markPublished()` in one DB transaction.
  - **Self-heal:** if a successful transaction already exists for this request but the listing is not published (stuck state), publish it instead of erroring.
  - Remove **all** SKU-keyed "already paid / already in process" logic from this path.

- **`paymentStatus`** (authoritative)
  - Returns `listing_status`, `is_published`, `awaiting_parent`, `payment_status`, fee fields. This is the single source of truth the client uses to decide paid/unpaid. (Already listing-scoped; promoted from advisory to authoritative in the client contract.)

- **`rejectPayment`** (keep, align)
  - Parent declines a `PENDING` request → request `parent_rejected`, listing `payment_rejected`. Re-initiate is allowed afterward (see `initiatePayment`).

- **`parentListingPayment`** (keep)
  - Parent fetches listing + request details (incl. SKU) to drive the IAP purchase on the parent device.

- **Unchanged:** `store`, `update`, `destroy`, `index`, `show`, `nearby`, `myListings`, and the admin controller (`stats`, `manage`, `markSold`, `markAvailable`). Admin `stats` revenue join (SKU + success txns) keeps working.

### 3.5 Parent / child (unchanged semantics)
Child lists → request `PENDING` → parent notified → parent calls `parentListingPayment` → parent buys the consumable IAP → `confirmPayment` (parent authorized) → child's listing publishes. Approve/reject notifications retained; stale request notification deleted on confirm/reject.

## 4. Client contract (mobile app must change)
- Decide paid/unpaid via **`GET hockey-listings/{listing}/payment-status`**. **Stop** calling `isPaymentDone(SKU)` / `processPayment(SKU)` for the listing fee.
- Treat the fee product as **consumable**; support "restore"/re-send-receipt so a charged-but-unrecorded purchase can be re-confirmed (see §5).

## 5. Remediation for existing/stuck users

The rewrite fixes new attempts; already-stuck users are handled by:

1. **Self-healing endpoints** — rewritten `initiate`/`confirm` recover stuck listings on the user's next action (re-initiate over a stale request; confirm publishes if a paid txn already exists). Most users unblock by simply retrying.

2. **One-off Artisan reconciliation command** `php artisan hockey:reconcile-listings` — idempotent, logged, `--dry-run` default:
   - Listing not `published` **and** has a `success` `V4PaymentTransaction` → `markPublished()`.
   - Request stuck `PAYMENT_INITIATED`/`PENDING` **and** no transaction → release/clear so the listing is re-payable.
   - Dry-run prints per-bucket counts → real impact is measured before applying.

3. **Charged-but-no-server-record** (worst case): the store took money but no transaction exists. The app re-sends the store receipt to `confirm-payment`; dedup won't match (no prior txn) → it records + publishes (self-heals). Otherwise → support/manual refund. This is the only case requiring app cooperation or support.

## 6. Tests (regression proof)
Feature tests:
- Pay listing #1 → publishes; **immediately pay listing #2 → publishes** (the bug; must be green).
- Replay same `(purchase_id, source)` → blocked exactly once (idempotent signal).
- Parent approves child listing → publishes; parent rejects → `payment_rejected`, then re-initiate works.
- Unauthorized confirm (non-owner, non-parent) → 403.
- Confirm an already-published listing → clean idempotent response, no error spam.
- `gateway` recorded as `app_store`/`play_store` per `source`.
- Reconciliation command dry-run + apply on seeded stuck states.

## 7. Rollout
1. Set fee product to **consumable** in both stores.
2. Ship backend rewrite + reconciliation command.
3. Run `hockey:reconcile-listings --dry-run`, review counts, then apply.
4. Mobile app switches to `payment-status/{listing}` and consumable/restore handling.

## 8. Risks
- **Mobile coupling:** the fix is only fully effective once the app stops using `isPaymentDone(SKU)` for the fee. Backend changes are necessary but not sufficient alone. — Mitigated by `paymentStatus` already existing and self-healing endpoints.
- **Store misconfiguration:** if the product stays non-consumable, the store still blocks repurchase. — Mitigated by §3.2 rollout step.
- **Concurrency:** double confirm → guarded by `(purchase_id, source)` dedup + DB transaction + listing-status check.
```
