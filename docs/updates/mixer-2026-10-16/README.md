# MBSH Reunion Mixer — implementation and review

Status: **changed / local-tested candidate; not deployed; owner visual acceptance pending**. This is the implementation of Fritz's October 16 plan, not a new event proposal. The canonical source is `famtastic-fritz/mbsh-reunion`, branch `codex/mbsh-mixer-update-20261016`, based on `origin/main` at `730eea5`. Worktree: `/Users/famtastic-fritz/Development/ClientWorktrees/mbsh-mixer-20261016`.

## Locked event facts and direction

The mixer replaces the former country-club dinner: **Saturday, November 7, 2026, 7–10 PM Eastern, Twisted Tonic, $30 final attendee charge per person; food on your own.** The venue publishes **723 Lincoln Ln N, inside The Lincoln Eatery, Miami Beach, FL 33139** on its [official website](https://thetwistedtonic.com/). No dress code, included drinks, entertainment, reserved rooftop area, new sponsorship benefits, or refund policy has been invented.

Fritz selected cinematic Miami mixer styling: recognizable Harry, original commissioned seal, red/black/gold, warm evening atmosphere. Exact rendered artwork still needs review. See [local graphics review](http://127.0.0.1:8976/mixer-review/), [generation prompts](GRAPHICS-PROMPTS.md), and [asset hashes](ASSET-MANIFEST.json).

## Implemented behavior

- Homepage, venue/map links, ticket offer, public navigation, injected slides, Harry answers, metadata/Event schema and the attendee portal use the new facts. Changed JS URLs carry an event revision to prevent stale browser caches restoring old prices or meal prompts.
- `/menu/` is now food/location information; `/menu.html` remains compatible. The public meal endpoint returns 410 without a DB write or email. Portal attendance/access edits preserve prior meal history and reject stale catered-meal selections. Authorized staff retain clearly labeled historical meal records.
- One admission offer replaces early-bird/regular tiers. New server reservations are $30 × quantity and `price_tier=mixer`; the additive migration preserves old tiers/amounts. The API retains existing response fields and adds `offer_id`.
- New HMAC checkout tokens bind the offer revision as well as purchaser, quantity and expiry. Woo rejects old tokens, stale carts/unpaid orders and final totals other than $30 × quantity. One reservation occupies one cart line; retry does not add admissions. The Woo product remains financial truth; the legacy table remains a request ledger.
- Existing confirmation templates and wallet artwork reflect the mixer. A missing template dependency in main prevented a clean-checkout portal bootstrap; the existing template source was recovered from `cf7a835`, then updated to mixer facts. The static creator-credit synchronizer excludes this PHP string producer; rendered email credit is tested separately. No email was sent.
- Two generated masters produce eight graphic exports plus an optimized wallet WebP. Masters and old approved assets are preserved. New source masters are excluded from deployment packaging.

## Evidence and limits

| Capability | Entry / saved result / reversal | Evidence | Provider / owner state |
|---|---|---|---|
| `mbsh.mixer.event-info` | Home, ticket page, food/location page, Harry and portal event guide; source/config changes can roll back by Git revision | `local_tested`: actual 390×844, 768×1024 and 1280×900 viewports; no horizontal overflow; correct mobile source; reduced-motion verified; required-field focus recovery | Provider `not_applicable`; owner `pending` |
| `mbsh.mixer.graphics` | Hero, announcement set, link preview, wallet and location card; versioned original references and masters | `local_tested`: deterministic typography, manual image review, hash manifest; screenshots below | Built-in image tool output received; model not exposed; owner `pending` |
| `mbsh.mixer.reservation` | Ticket form → server request → Woo AJAX bridge/attempt ledger; old requests preserved | `local_tested`: real MariaDB migration, $30/$60, replay, price tampering, invalid quantity, retired meal endpoint; real Woo AJAX one-line cart / $60 | Woo `local_tested`; Stripe sandbox `unconfigured`; owner `pending` |
| `mbsh.mixer.entitlement` | Paid Woo order → one entitlement per admission; failed order gets none; refunds revoke correct quantity | `simulated`: real WooCommerce 11.0.1 isolated DB, payment-complete event, duplicate issuance, partial/full refund and replay; no external provider or mail | Gateway remains `connected_unverified` for changed offer; owner `pending` |
| `mbsh.mixer.live-cutover` | Retire old Stripe link, additive legacy schema, update product 26 and current-offer marker | `proposed`: inspect/apply scripts prepared; no production mutations | Old link is still active; Stripe sandbox keys absent; owner review/provider proof pending |

[Production read-only reconciliation](PRODUCTION-READ-ONLY.json): nine admission orders and one test order were fully refunded in WooCommerce. The configured Stripe API confirmed all ten referenced transactions fully refunded. No outstanding paid admissions or pending Woo orders were found. This does not authorize deleting history. The superseded Stripe Payment Link remains active and must be retired at cutover. No live charge, refund, provider setting, customer message, catalog update or production migration was performed.

The local preview deliberately rejects checkout submissions; do not enter real purchaser data there. A preview render is not a connected checkout or an issued admission. Local Woo simulation does not satisfy Stripe success/decline/3DS/webhook provider acceptance.

## Reproduction

```sh
npm ci
npm test
bash tests/backend/run.sh
bash wordpress/tests/static-check.sh
python3 tests/backend/mixer_integration.py
bash tests/backend/integration.sh
python3 tests/wordpress/run-mixer-proof.py
node scripts/sync-event-config.mjs --check
node scripts/sync-creator-credit.mjs --check
```

Docker proof creates only named, isolated fixtures, blocks external provider requests and mail, and cleans its own containers/volumes/network. It requires the pinned official WordPress/MariaDB images and WooCommerce 11.0.1; the runner uses the pinned local vendor cache or downloads the official package. No existing local DB/settings are copied. Source-only/unit results and real Woo simulation are separate from provider evidence.

Preview: `php -S 127.0.0.1:8976 -t frontend scripts/local-portal-router.php`. Server launched for owner review, PID **84462**, source directory above. It was verified as this turn's server; unrelated servers are not stopped. [Desktop capture](screenshots/home-desktop.jpg) and [phone capture](screenshots/home-mobile.jpg) record local review, not owner approval.

The worktree's foundation verifier encounters a pre-existing common-directory manifest gap in the older primary checkout. Do not patch another checkout to hide this. Verify the committed candidate from a fresh independent clone with the original GitHub origin; record that result in the closeout receipt.

## Release sequence after review

1. Fritz reviews the exact website/artwork and records acceptance of this candidate. Configure a separate Stripe sandbox through the owner control plane; complete success, decline, 3DS, webhook replay and receipt proof using test recipients. Do not test by making a live charge.
2. Reconcile paid/pending Woo and Stripe state again; stop for any new outstanding paid admission. Preserve scoped code/catalog/schema backups. Inspect the immutable release diff using the existing Git deployment procedure.
3. Promote the reviewed source with checkout held closed by the absent current-offer marker. Preserve the shared-host isolation rules, uploads, provider state and all customer records.
4. Stage the two CLI helpers outside the public webroot. Inspect first with `wp eval-file <helper> inspect` against `/home/nineoo/public_html/cms`. Use PHP CLI memory 512M if the host's 128M default fails to bootstrap Woo.
5. Run `retire-legacy-mixer-payment-link.php apply` to deactivate only the identity-checked superseded link. [Stripe documents the inactive-link behavior](https://docs.stripe.com/api/payment-link/update). Check for and expire any still-open checkout sessions associated with that superseded link before opening sales. Then run `prepare-mixer-commerce.php apply` to add `mixer` to the legacy enum, snapshot product metadata, set product 26 to $30, and set the current-offer marker. The helper refuses tax/currency changes or outstanding paid admissions; it sends no messages and does not charge or refund.
6. Verify the immutable production marker/checksums, hosted pages/graphics, old handoff redirect, product/offer revision, $30 and $60 totals, and credential/auth boundaries. Record provider and owner evidence separately. No campaign send is part of this release.

Rollback: close sales first by clearing `famtastic_mixer_offer_revision`; preserve all accepted data and provider evidence. Roll application code back only by the approved Git release procedure. Keep the superseded Payment Link inactive; do not roll back schema destructively or restore old prices to newly accepted orders. The stored catalog-before snapshot supports a deliberate owner-reviewed recovery, not an automatic whole-DB restore.

## Owner task acceptance

Acceptance is **pending**, not inferred from these tests. On Fritz's phone: (1) find the changed venue/time/price and food terms; (2) open directions; (3) select one/two admissions and observe $30/$60; (4) in the separate sandbox complete a test checkout, retrieve the private wallet ticket, verify a failed payment produces none; (5) as the authorized owner, inspect the Woo order/attempt and historical meal context. Record device, release, uncoached result and any issue under the capability IDs above. No owner has performed this walkthrough yet.

## Site-local learning

Event facts were duplicated in visible markup, generated slides, configuration, countdown, portal guidance and commerce. A changed venue/price requires tracing that full journey. Consolidated event configuration, version-bound payment tokens and changed-asset cache revisions prevent an old browser or unpaid checkout from silently restoring the previous offer. Classification: `site_local`; no shared skill/profile promotion or Studio-consumption claim.
