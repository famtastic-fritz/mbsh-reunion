# Paid mixer portal wallet repair — October 22, 2026

Classification: `changed` until the deployment receipt is recorded. Fritz explicitly authorized implementation, deployment, and reconciliation of every genuinely paid current $30 mixer purchase, including purchases arriving during the repair. No further routine release approval is required.

## Cause and repair

WooCommerce issued `reunion_ticket` admissions and delivered confirmations. The separate PHP portal queried only `ticket_wallet_items`, so those admissions were invisible. Updating the legacy request to `paid` would create a second financial authority and potentially duplicate credentials.

The repaired portal reads the existing WooCommerce admissions through a local server adapter, after authenticating an active, email-verified portal account. Its identity comes from the authenticated database record, never query parameters. Billing email matches are normalized and checked again. Current mixer offer, USD currency, $30 item/order amounts, paid state, refund quantity, ticket status and ownership determine eligibility. Existing legacy wallet items remain visible alongside current commerce admissions.

The stable portal identifier is a namespace prefix plus the existing random admission identity. No original credential is replaced, no shadow payment ledger is created, and no fabricated account is verified. Purchases before registration remain durably associated with their trusted billing email; normal registration and verification make them immediately visible. Refund/void state is read from the authority on each request. The API is private/no-store and fails with 503 if commerce is unavailable.

The wallet exposes each admission through a keyboard-operable selector and renders its rotating credential locally as a readable QR. No credential is sent to an external QR service. The existing staff capability and CSRF checks protect check-in/void, with atomic status transitions and attributable portal actor audits. Staff scanning UI and a real owner's independent task are not claimed by this release.

Missing issuance recovery uses a per-order database lock, counts existing admissions per order item, honors item-quantity refunds and manual revocations, preserves credential IDs, and retries interrupted issuance through the existing scheduled reconciliation worker. The worker paginates paid orders. The backfill additionally verifies live Stripe captured amount, currency, purchaser email and refunded amount against WooCommerce before invoking recovery. It neither changes financial state nor sends email.

## Before release: provider-verified inventory

At 8:38 PM EDT, six real paid mixer orders/purchasers represented eight expected admissions. All eight original WordPress admission records existed; the old portal exposed none of these commerce records. Three purchasers had verified portal accounts, one was unverified, and two had not registered. The new adapter's read-only candidate projection returned all eight. This candidate projection is not evidence that the portal had already been deployed.

## Validation

- Fresh isolated WordPress 7.0.4 / WooCommerce 11.0.1 / MariaDB proof, external network blocked, fake provider configuration and synthetic recipients only. See `local-proof.txt`.
- Real HTTP registration, verification, login, anonymous/unverified denial, purchase-before-account, multiple admissions, cross-account/query exclusion, staff capability and invalid credential rejection, check-in and duplicate-scan rejection.
- Paid-event replay, stable IDs, unpaid/failed exclusion, partial/full refunds and replay, manual revocation, interrupted issuance and retry. Refund objects are explicitly excluded from purchase queries.
- Existing real checkout AJAX, final $30/$60 totals, stale token/order rejection, mocked mail failure/retry/idempotency, and no duplicate receipt calls continue to pass.
- Mobile browser proof at 390×844: two admissions, active and checked-in states, selector switching, no horizontal overflow, real 240px QR. Saved JPEG is a full-page image, not an 844px viewport screenshot. Identity and credential are synthetic and local-only.
- An independent QR decoder recovered the full synthetic rotating payload from generated modules.
- PHP syntax/security/email tests, WordPress static checks, shared public shell and creator-credit contract pass. The inherited managed-worktree `common_directory_mismatch` remains; independent-clone source verification is required, without weakening the checker.

Actual failures retained: the first read-only production audit encountered a refund object in the order list; type filtering and a refund-object regression assertion fixed it before any live source deployment. Re-running the one-shot fixture initializer against a retained populated test database failed its exactly-two assertion because it added another fixture order; the final recorded run starts from a clean database. An initial isolated-network browser connection was unavailable; a loopback-only bridge provided browser access without granting containers external network access.

## Release and rollback

The immutable base is `9d93db84796c6069833d9240f05330dadd1ca714`, with the prior HTTPS-only overlay retained. This release deploys only the explicitly hashed wallet files. Their pre-change source matches the base; unrelated source-only additions are excluded. The private deployment directory records before/after hashes, original file backups, replacement order, and a rollback script. Rollback restores source files and preserves all orders, admissions, identity and audit data.

GitHub remains source-only with Actions disabled. No charge, refund, legacy paid-state mutation, replacement credential, extra repair notice or receipt resend is authorized or performed by this repair. No new transactional mail is needed to expose already-issued admissions whose confirmation messages already link to the portal.

## Capability evidence

| capability_id | Customer job / entry | Saved authority and visibility | Evidence | Provider state | Owner acceptance |
|---|---|---|---|---|---|
| `mbsh.portal.commerce-wallet` | Verified attendee opens My Reunion → wallet; selects each admission | Existing Woo order/admission; matching verified portal email only; refund/void removes active access | Local real HTTP + mobile synthetic proof; hosted receipt follows | Stripe captures verified for scoped paid orders | Pending real attendee login |
| `mbsh.commerce.missing-ticket-recovery` | Existing paid event / scheduled reconciliation; owner runs scoped reconciliation | Woo admission records; locked, quantity bounded, no credential replacement | Local failed/retry, replay, refund proof; production reconciliation receipt follows | Stripe capture/refund match required for backfill | Not requested for background operation |
| `mbsh.portal.commerce-checkin` | Authorized portal staff submits rotating credential | Atomic state on original Woo admission; portal actor audit; revoked/used credential rejected | Local authenticated HTTP only; no real admission checked in | Not applicable | Pending; scanning UI not part of this repair |

[Owner verification steps](OWNER-CHECK.md). Reusable pattern is site-local source capture, not claimed Studio adoption. Applied Agency Agents reference: `engineering/engineering-backend-architect`, pinned catalog `765be42358100bf89d2faa567668a94c602f9a26`; applied directly, no delegated agents.
