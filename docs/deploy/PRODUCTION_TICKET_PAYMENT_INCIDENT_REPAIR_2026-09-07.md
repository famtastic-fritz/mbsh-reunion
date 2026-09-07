# MBSH ticket-payment incident repair — deployment and proof gate

Status: local repair implemented; not deployed; no payment, ticket issuance, or email performed by this change.

Incident evidence supplied for this repair: the public form writes `ticket_orders`, WooCommerce product `26` then returns `error: true` from the generic add-to-cart endpoint, the active theme does not render `the_content`, production has zero WooCommerce orders, and the legacy table contains 17 pending attempts representing 27 requested seats with repeat submissions. Treat those counts as incident evidence to reconcile, not as paid or issued admissions.

## Repair boundary

- `backend/ticket-order.php` preserves the legacy request record, serializes same-purchaser identical submissions, replays an identical recent reservation, and sends no pre-payment mail.
- `frontend/js/ticket-order.js` sends the reservation metadata and short-lived signed checkout token to a reservation-aware WooCommerce endpoint.
- `class-commerce.php` records attempt and event timestamps, repeat flags, reservation-to-order linkage, Woo status transitions, abandoned-attempt monitoring, and a Site Owner WooCommerce ledger. It rejects direct unlinked adds of product 26 and fails closed when the product event key or shared checkout secret is absent.
- The WooCommerce theme renders `woocommerce_content()` for cart/checkout and `the_content()` for ordinary WordPress pages.
- Existing `ticket_orders` rows are not marked paid, imported as Woo orders, deleted, or emailed by this repair. WooCommerce remains the only financial authority.

## Pre-deploy read-only reconciliation

1. Export `ticket_orders` from the authoritative production database to an immutable, access-controlled file. Include row ID, reservation code, normalized email hash, quantity, amount, price tier, payment status, and UTC creation timestamp; do not place raw email in the report.
2. Produce a unique-purchaser summary and an all-attempt timeline. Group exact repeated requests by normalized email, quantity, amount, and close timestamp; preserve every source row and label it `legacy_unreconciled`.
3. Compare the export with WooCommerce orders and the Stripe Dashboard by reservation code, billing email hash, amount, order ID, and provider receipt. A Stripe link or dashboard charge without a matching Woo order is an exception requiring owner review; it is not permission to mark a legacy row paid.
4. For every possible customer recovery, prepare a draft only: reservation code, requested quantity, source timestamp, whether a matching paid Woo order exists, and the next safe action. No customer or committee message is sent in this run.

## Controlled deployment sequence

Approval required immediately before step 6. Steps 1–5 are preparation and verification only.

1. Create a dated, file-scoped backup of the public frontend, backend PHP, `/cms/wp-content/plugins/famtastic-reunion-platform`, theme, `wp_options`, WooCommerce tables, and the legacy ticket table. Record checksums and available disk space. Do not use a recursive delete or `rsync --delete`.
2. Generate one high-entropy `MBSH_CHECKOUT_SECRET` outside web root with mode `0600`. Configure the same value in the backend secret file and WordPress `FAMTASTIC_REUNION_CHECKOUT_SECRET`; use a separate value for test/staging. Never commit or log it.
3. In WooCommerce, verify product `26` is published, purchasable, in stock, priced by the approved catalog, and has `_famtastic_ticket_event=mbsh-1996-30th`. Verify Cart and Checkout pages are assigned in WooCommerce settings and that the official Stripe gateway is in test mode for proof.
4. Install/activate the repaired plugin and theme, allow the activation migration to create the two prefixed attempt-ledger tables, and confirm the plugin version is `0.5.0`. Run the static checks and a no-payment browser smoke.
5. Run the complete test-mode matrix below with a test recipient/sink. Confirm no live key, live gateway, real customer, or production email recipient is used.
6. Fritz explicitly approves the exact production promotion and any later test payment. At that boundary, promote the listed files atomically, run the schema/route checks, and keep the public CTA closed until the post-deploy read-only checks pass.
7. With the CTA still closed, verify `/cms/cart/` and `/cms/checkout/` render real Woo content, the custom endpoint accepts a signed token, an invalid/expired token fails, and generic product-26 cart insertion fails. Only Fritz can authorize opening the CTA and enabling payment traffic.

## Test-mode proof matrix

| Case | Required assertion |
|---|---|
| Valid reservation | One legacy request, one ledger attempt, one cart line, one Woo order with reservation metadata. |
| Double click/retry | Same legacy reservation code is replayed; ledger increments an event/timestamp and does not create a second cart line. |
| Same purchaser, new request | A distinct source request is preserved and ledger marks `repeat_flag`; unique-purchaser count remains one. |
| Invalid/expired/tampered token | 400/failed closed; no cart line and no order. |
| Missing/misconfigured product 26 | 409; attempt becomes `abandoned`; no order. |
| Cart/network failure after reservation | Legacy row remains pending/unpaid; ledger records the failed/abandoned bridge; retry can reuse the reservation. |
| Abandoned checkout | No paid order or ticket; monitor marks the attempt `abandoned` after the timeout. |
| Stripe test success | Woo order becomes paid through the official gateway; exactly one ticket per paid quantity; paid notification is queued once. |
| Decline/3DS failure | No valid ticket and no paid notification; order/ledger is `failed` or remains processing until provider truth arrives. |
| Duplicate/reordered provider events | No duplicate order, ticket, notification, or legacy mutation. |
| Full/partial refund | Woo refund remains financial truth; the correct ticket quantity is revoked and ledger becomes `refunded` for a full refund. |
| Accessibility/device | Cart, checkout, ticket form, keyboard focus, reduced motion, and narrow mobile layouts work without cinematic effects. |

## Recovery-customer plan after approval

Do not bulk-convert the 17 rows. For each unique purchaser, Fritz reviews the source timeline and any authoritative Woo/Stripe evidence. A customer receives one owner-approved instruction: either use the repaired public form to create a fresh Woo checkout, or, only when an authoritative paid Woo order is verified, receive the existing Woo receipt/ticket recovery path. The legacy reservation code remains a reconciliation reference; it is never treated as proof of payment. Any ambiguity is held for manual review, and no ticket is issued from a legacy-only row.

## Rollback

If any post-deploy check fails, close the ticket CTA and make product 26 non-purchasable/private, retain all Woo/Stripe records, and restore only the backed-up application files needed to return the public site to its prior safe state. Do not delete orders, refunds, attempt-ledger rows, or legacy requests. Disable paid-ticket processing only after recording any paid orders lacking tickets; replay verified provider events after repair. Restore the previous theme only if Woo pages are still accessible through an explicitly tested fallback.

## Monitoring and escalation

Review the WooCommerce Ticket attempts ledger and Action Scheduler every five minutes during the first controlled window. Escalate when the heartbeat is late, a paid order lacks tickets for five minutes, a paid notification is repeatedly retrying, an attempt is unexpectedly marked abandoned, or the Woo/Stripe/legacy reconciliation count changes. The ledger is operational evidence, not a replacement for WooCommerce order history or Stripe receipts.

## Exact safe next action

Run the local/test-mode proof with a configured non-production checkout secret and a test Stripe gateway, then return the redacted evidence and the read-only production reconciliation export to Fritz for explicit approval. Do not deploy, enable live payment, contact the committee/customer, mark a legacy row paid, issue a real ticket, or charge a real card before that approval.
