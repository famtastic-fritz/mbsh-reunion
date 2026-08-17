# Headless WordPress/WooCommerce Operations Plan

**Status:** non-live proof scaffold
**Decision:** staged hybrid migration; no production cutover is implied by this repository state.

## 1. Authority map

| Capability | Proof authority | Cutover authority |
|---|---|---|
| Public cinematic frontend | Existing frontend | Existing frontend consuming WordPress APIs |
| Editorial pages, FAQs, events | Existing files | WordPress custom post types and blocks |
| Accounts and recovery | Existing/custom where present | WordPress users behind same-origin session endpoints |
| Memories and media rights | Existing custom tables | WordPress media + `reunion_memory`, moderated before publication |
| Catalog, coupons, orders, refunds | Existing reservations | WooCommerce |
| Card collection and payment truth | None/live disabled | Official Woo Stripe gateway + verified webhooks |
| Virtual admission | Existing reservation concept | `reunion_ticket` created idempotently from a paid Woo order |
| Transactional delivery | Existing Resend adapter | Resend API through a WordPress adapter; SMTP only as compatibility fallback |

The custom backend stays available until record counts, customer access, payments, emails, and rollback are proven. Do not perform a big-bang database replacement.

## 2. WordPress data model

- `reunion_event`: schedule, venue, agenda, attendee instructions.
- `reunion_memory`: story, media, submitter, explicit usage scope, moderation state.
- `reunion_suggestion`: private committee workflow.
- `reunion_ticket`: owner, Woo order/item, status, issuance/check-in audit fields.
- WordPress user: profile, verified-email timestamp, transactional preferences, separate promotional consent and timestamp.
- Woo product: ticket/add-on SKU and `_famtastic_ticket_event` event key.
- Woo order: financial source of truth. Never create a valid ticket from `pending`, `failed`, `cancelled`, or unpaid orders.

Yearbook/diploma source photos are private archive inputs. Faces, names, signatures, student records, or diploma details are not bulk-published. Public derivatives require moderation, usage permission, contextual captions, and removal workflow.

## 3. Authentication boundary

Use HTTPS and secure, HttpOnly, SameSite cookies. React calls same-origin endpoints with a WordPress REST nonce; no long-lived JWT, Stripe secret, Resend token, or WordPress application password belongs in local storage. Require email verification before personal memories, orders, or tickets are returned. Rate-limit login, reset, registration, uploads, suggestions, and check-in.

Recommended roles:

- `attendee`: own profile, submissions, preferences, orders, tickets.
- `committee_member`: review memories/suggestions and view attendee operations.
- `check_in_staff`: ticket lookup and redemption only.
- `event_manager`: catalog, refunds, communications, reports, exports.
- `administrator`: configuration; not used for daily work.

## 4. Ticket lifecycle

1. Customer signs in or creates and verifies an account.
2. Woo Checkout creates an order. Coupon and guest/add-on data remain line-item metadata.
3. The official Stripe gateway hosts payment UI and verifies Stripe webhooks.
4. A paid `processing`/`completed` order triggers idempotent issuance once per qualifying quantity.
5. The plugin stores an opaque 128-bit public ID and derives an HMAC-signed QR payload from WordPress auth salts.
6. Wallet/email renders the signed payload as QR plus a human fallback code.
7. Authorized staff redeem it through an atomic `valid → checked_in` database transition. Concurrent reuse produces one success and one `409` response.
8. Woo full-refund, cancellation, and failed-order hooks atomically revoke all still-valid tickets for the order. Partial refunds revoke only the refunded quantity for each original line item.
9. Successful check-in and revocation append immutable ticket audit entries. Replayed Woo hooks are idempotent because only `valid → revoked` can succeed.

The scaffold covers issuance, atomic one-time check-in, full-order revocation, and line-item quantity revocation for partial refunds. Downloadable wallet passes, scanner UI, guest transfer, offline reconciliation, and provider-runtime evidence remain launch-gated work.

## 5. Stripe test gates

Use WooCommerce plus the official Stripe gateway rather than custom card handling. Use the current Stripe API version and dynamic payment methods. Prefer a restricted key with minimum permissions, distinct test/live keys, IP restrictions where feasible, and verified webhook signatures. Secrets live in hosting secrets/config outside Git.

Required test matrix before any live key is introduced:

- Successful card payment, receipt, Woo paid state, exactly one ticket per quantity.
- Decline, insufficient funds, expired card, and cancelled checkout create no valid ticket.
- 3DS authentication success, failure, abandonment, and later webhook completion.
- Duplicate/reordered webhooks do not duplicate tickets or email.
- Coupon (including a friend-specific price) preserves list price, discount evidence, and order audit.
- Full/partial refund, dispute, cancellation, and chargeback revoke the correct admissions.
- Mobile Checkout, account recovery, wallet, QR scan, already-used, revoked, and offline fallback.
- Two scanners attempting the same ticket simultaneously produce one success and one rejection through the conditional database update.
- Tax remains disabled until the business confirms registration and final treatment. Enabling Stripe automatic tax alone is not proof that tax is collected.

**Live gate:** written price/refund/transfer policy; legal consent wording; successful test evidence; backups; alert recipient; rollback rehearsal; restricted live key created separately; webhook endpoint verified; test mode unmistakably removed only in the final controlled change.

## 6. Resend and notifications

Prefer Resend's HTTPS API for transactional email because the existing reunion configuration already uses Resend. Configure `RESEND_API_KEY`, verified sender/domain, reply-to, and committee alert recipients outside Git. SMTP is a fallback for WordPress plugins that cannot use an API transport; do not maintain two independent delivery paths without a single event log.

Transactional templates:

- Verify email and password reset.
- RSVP/account welcome.
- Payment receipt link (Woo/Stripe owned).
- Ticket issued/reissued/transferred/revoked.
- Memory received/approved/rejected and rights change.
- Suggestion received and committee alert.
- Event update and schedule change.

Promotional consent is independent. Every promotional message carries unsubscribe and suppression handling. A promotional opt-out must not stop security, receipt, ticket, or material event notices. Store provider message ID, template, recipient hash, attempt, result, and correlation ID; never log raw API keys.

## 7. Workers, retries, and alerts

Disable traffic-triggered WP-Cron in production. Run system cron every minute and use Action Scheduler (bundled with WooCommerce) for durable asynchronous jobs. The Docker proof includes a WP-CLI cron loop only for local use.

Workers must be idempotent and have bounded exponential retry. Dead-letter after the configured limit and alert the committee. Track last success, duration, processed, retried, failed, and oldest pending job. Alert when:

- heartbeat is more than 10 minutes late;
- Stripe webhook failures repeat;
- paid order lacks tickets after five minutes;
- Resend delivery/bounce/complaint fails;
- upload scan/moderation queue stalls;
- database backup or restore verification fails.

Daily digest goes to configurable committee recipients even when no failures occur, so silence is not mistaken for health.

## 8. Migration and rollback

1. Export custom tables and media inventory; checksum and retain immutable backup.
2. Import users/content with a source ID and migration batch ID; imports are repeatable/upserted.
3. Run both systems read-only for comparison, then shadow-write nonfinancial content.
4. Cut over one capability at a time: content → accounts → memories → orders → tickets.
5. Reconcile counts, samples, timestamps, consent, order totals, and ticket states after each step.
6. Keep old routes and database read-only during the rollback window.

Rollback means: stop checkout, disable issuance worker, restore frontend routing to the prior endpoints, retain Woo/Stripe records (never delete financial evidence), replay only verified provider events, and communicate impact. Database restore is the last resort, not the first response.

## 9. Definition of done

Production is not ready until a new user can verify email, purchase in Stripe test mode, receive exactly one ticket, view it on mobile, submit a moderated memory with explicit rights, update promotional preferences, and be checked in by a least-privilege staff account; then refunds, retries, alerts, backup restore, accessibility, authorization isolation, and rollback all pass with recorded evidence.

## 10. Current scaffold evidence

- Plugin PHP files pass `php -l`.
- Capability manifest parses as JSON and the static check rejects credential-shaped Stripe/Resend secrets.
- Static contract assertions confirm full refund, cancelled, failed-order, partial refund, refund lock/reconciliation, atomic status transition, and audit code remain present.
- `docker-compose config` succeeds with the installed legacy Compose binary. The Docker daemon was reachable, but containers were intentionally not started; this is configuration evidence, not WordPress/WooCommerce runtime proof.
