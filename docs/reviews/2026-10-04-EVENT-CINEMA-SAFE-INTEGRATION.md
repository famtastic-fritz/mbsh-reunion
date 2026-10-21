# Event Cinema local-main integration — October 4, 2026

**October 5 active-status correction:** Safe integration is complete. Fritz confirmed retaining the working WooCommerce flow and directed retirement of outdated work. The superseded checkout/wallet replacement and repeated commerce-direction question are removed from the active queue. Use the [current report](2026-10-05-CURRENT-INTEGRATION-STATUS.md) for next work; the exact table and receipts below retain the October 4 historical dispositions.

Fritz authorized committing and merging the complete candidate, with exact production regressions held for review: “all of this should be committed and merged into main unless it will overwrite something in prod. then lets review after.” This supersedes the tentative archival decision and the earlier hold on staging this candidate. No archive, reset, push, deployment, migration execution, email send, payment test or provider activation is authorized here.

Classification: **changed source only**. Owner acceptance is pending; no customer release is implied. Initial local main `02c8fe4db`; complete sanitized candidate `56ad7b8202a5e3a721ff9aa6edf4699a0adb8b0a` (`codex/mbsh-analytics`). The isolated merge branch is `codex/mbsh-event-cinema-safe-integration-20261004`. Merge commit `52135e3b3b1d068277ea9dc3fb755818aad7da8c` has the baseline and sanitized candidate as parents. [The verification receipt](2026-10-04-EVENT-CINEMA-INTEGRATION-RECEIPT.json) pins the tested code and exact package. Subsequent closeout commits add evidence only.

## Production anchor and preservation

Read-only cPanel DomainInfo confirms `mbsh96reunion.com` is an addon domain at `/home/nineoo/public_html`, sharing the Inc host root. The deployed marker and current-release symlink still name `67f22480d6fe2a3596d9b6117279097c8db54768`; all **251/251** managed live hashes match. HTTPS apex responds 200. No authenticated journey or business-data check was performed.

The private archive `/Users/famtastic-fritz/Development/FAMtastic-private-evidence/2026-10-04/mbsh-event-cinema-dirty-82.tar.gz` still hashes to `8d368b17670235339c54fd64961f294a747769a14f26dcb4d90d91ae2d95bdfa`; its 81 file hashes and one deletion match the initial candidate. Original bytes remain there. Newly created local commits were sanitized before main integration: setup password/hash and old production credential prose are excluded. Earlier candidate ancestry still contains historical credential prose, so this is not a clean-history publication approval. All outgoing historical tools now need both an explicit action and a separate operator gate; no tool is activated here. The recovery branch `recovery/mbsh-pre-integration-working-tree-20261004` remains independent and untouched.

## Integrated source

Preserved all candidate ancestry in the merge, including historical creative/marketing projects, generation receipts, source compositions, licensed package notices, and scene evidence. Added the new Harry/Shay variants without replacing the existing approved master. Kept portal operations already present on main, its functional sample studio, the compatible artwork update, email renderers and dormant helpers, the read-only order listing, dormant schema/migration source, and disabled future checkout configuration. The email renderer fills a dependency already required by main's ticket helper; its direct rendering and transport wrapper both preserve one creator logo. Page-credit sync now excludes PHP library producers, avoiding corruption of HTML inside string literals. Historical proofs were not rewritten.

Thirty textual conflicts were resolved against the production-compatible main behavior. All 82 original dirty paths have exact source dispositions and hashes in [the machine-readable record](2026-10-04-EVENT-CINEMA-INTEGRATION-DISPOSITION.json). Keeping a newer equivalent implementation is integration, not a missing feature. Formatter-only, old shell cache-key and creator-credit conflicts retain main and do not need owner design approval.

## Exact behavior held for review

| Path | Production/main behavior | Candidate proposal | Risk | Recommendation |
| --- | --- | --- | --- | --- |
| `backend/ticket-order.php` | Signed checkout token; identical request replay; no pre-payment mail | Single-ticket raw insert plus direct emails and Payment Link handoff | Duplicate reservations, lost signing, new mail side effects | Keep main; port any wanted copy onto current bridge |
| `frontend/js/ticket-order.js`; `frontend/tickets.html`; `backend/tickets.html` | Reservation-aware Woo bridge and quantity/price handling | Expired Early Bird one-ticket $191 link | Regular-price checkout stops; incorrect totals or bypass | Keep Woo bridge; separately review any Payment Link product |
| `backend/lib/ticket-payments.php`; `.env.example` signing line | $190.67 historical amount, ten-quantity helper; signing secret retained | $191, one quantity; remove signing config | Mismatch with working commerce contract | Main behavior kept; only harmless disabled config additions integrated |
| `wordpress/.../includes/class-tickets.php` | Reservation linkage, non-admission test tickets, revocation safeguards | Alternative legacy wallet bridge; remove test restrictions | Test tickets could become admission; competing fulfillment | Keep main; port wallet work behind existing paid lifecycle |
| `wordpress/.../includes/class-operations.php` | Reconcile the issuer's actual `_famtastic_tickets_issued` marker | Inspect `_famtastic_portal_tickets_issued` instead | Repeated or stalled reconciliation | Keep main |
| `frontend/portal/index.html`; `frontend/portal/js/portal.js` | Loaded ticket/RSVP status, canonical meal labels, working mobile-menu hook | Static “ticket ready” and RSVP copy, old meals, remove hook | False ticket state; phone navigation regression | Keep main |
| `frontend/portal/committee/index.html`; `frontend/portal/js/committee-workspace.js` | One ticket panel, action metrics, expected amount, retry/error handling | Duplicate panel, fixed $191 confirmation minimum, non-action metrics, session logout on any load failure | Hidden controls, wrong amounts, lost workflow and session | Keep main |
| `frontend/portal/js/portal-api.js`; `frontend/portal/js/ticket-payment-desk.js` | Explicit fictional owner demo; payment amount prefill | Remove demo staff and prefill data | Demo cannot open operations; lost amount checking | Keep main |
| `frontend/pay/index.html`; `frontend/assets/tickets/stripe-payment-link-qr.png`; `tests/frontend/stripe-payment-link-contract.test.mjs` | Current reservation-aware checkout | New unsigned expired Early Bird entry and contract expecting it | Adds a route that bypasses current working purchase flow | Entire 3-path dependency held on candidate |
| `frontend/payment-setup/{index.html,index.php,conversation.js,conversation-v2.css,conversation.css,guard-talk.css,payment-setup.css,secure-form.css,text-animation.css}` | Current owner/committee authentication and mail flows | Separate setup password room plus public static alternate page and direct saved-message mail | New auth and mail authority, hardcoded host paths; static index bypasses PHP login | Entire 9-path dependency held; integrate with portal auth/outbox before activation |
| `frontend/assets/mascot/09-confused.png`; `spec.json` | Existing mascot and recorded reference | Delete asset; replace fallback identity | Existing consumer/approved identity could lose its source | Keep originals; new variants integrated alongside |
| `frontend/assets/social/2026-reunion-hype/09-ultra-real-practical-effects.png`; sibling `README.md` | Existing square approved master | Replace with portrait curtain host and change provenance wording | Overwrite prior approved artwork | Keep master/README; curtain host is available as separate Act I asset |

All held changes remain committed and recoverable at the candidate revision. No automatic cherry-pick of the separate recovery work was performed.

## Capability evidence and owner tasks

Source revision for each row is the tested merge commit recorded in the receipt; runtime revision remains production `67f2248` above.

| Capability ID; job/role and entry | Saved result, visibility and reversal | Evidence/environment | Provider state | Owner acceptance / consumption | Gap and next owner |
| --- | --- | --- | --- | --- | --- |
| `mbsh.source-integration`: operator uses local owning main | Git source, explicit hold record; recover via candidate/previous main | Local merge, hash comparisons, fresh-clone/package receipt | not_applicable | not_requested / source_captured | Fritz reviews held behavior; deploy separately |
| `mbsh.sample-ticket`: committee fills Sample Ticket Studio at `/portal/admin/#tickets` | Fictional preview only; no wallet, QR, order or send; reset form | Existing contract plus local browser simulation receipt; new artwork loads | not_applicable | pending / not_applicable | Uncoached owner task on release pending |
| `mbsh.ticket-email-rendering`: operator renders permitted lifecycle state | HTML result, one final logo, escaped input; no transport in rendering | Local PHP test for six states, invalid state and URL; no provider call | connected_unverified for production transport; no new activation | pending / source_captured | Provider/outbox retries and delivery not exercised |
| `mbsh.historical-tools`: operator retains old send/role helpers | Dormant source outside release package; separate action + operator gate | Source inspection and refusal-only checks; no authorized action executed | connected_unverified; dormant helpers disabled | not_requested / source_captured | Historical recipients/content/price and idempotency must be reviewed before running |
| `mbsh.commerce`: attendee reserves/pays; owner issues | Woo financial source, reservation signing/replay/test-ticket exclusion preserved | Exact unchanged source for critical paths; commerce contract; no live payment test | connected_unverified in this session | pending / not_applicable | Hosted journey and owner acceptance need approved release testing |
| `mbsh.schema-candidate`: operator keeps additive migration source | Files only; no database changes; fresh install columns nullable | Source inspection only | not_applicable | not_requested / source_captured | Migration applicability, idempotency/reversal not exercised; deployment reviewer |
| `mbsh.creative-source`: creator retrieves variant/media project | Git deliverables and provenance; no media publication; existing master retained | 31-image contact sheet, source/hash review; composition sources retained | not_applicable | pending / source_captured | Campaign rights/publication and exact Studio consumption unverified |

Owner-task record `mbsh.sample-ticket.preview`: committee role, phone/desktop route `/portal/admin/#tickets`, approved release and role required; enter fictional name and label, generate preview and verify “SAMPLE · NOT VALID FOR ENTRY” with no credential, reset or close to reverse. Source matches the receipt's merge revision; local simulation is separate from acceptance. Owner/date observation: unavailable; **pending**. Payment/refund/mail owner tasks are not requested or observed and must not be inferred from source tests.

## Validation and remaining limits

Ten frontend/commerce suites pass; backend syntax/security and offline renderer tests pass; WordPress syntax/contracts pass. Page credit covers 52 documents and retains the approved logo hash. Refusal checks never activate the send/role tools. Added/modified text secret scan found no private provider keys, password hashes or identified embedded credentials; the 31-image contact sheet was inspected; rights/publication and unrecorded generation provenance remain unverified. This bounded scan does not certify historical ancestry clean.

The present worktree's `verify-repository` reports `common_directory_mismatch` because its canonical primary checkout at `9fc32ba` lacks the matching site manifest. Preserve that checkout and the guard. The fresh independent clone is verified separately; see the receipt. No schema import or data restore was performed. Package validation is code-only; live payments, email delivery, database compatibility/reversal, authenticated device behavior, campaign publication, and owner acceptance remain open.

Agency profile guidance: Git Workflow Master, Reality Checker, Project Shepherd at pinned library `765be42358100bf89d2faa567668a94c602f9a26`. FAMtastic full definition v1.0.0, build-review and client-owner-training guidance were read and applied. Site-local lessons were appended; no shared skill promotion or Studio consumption is claimed.

## Historical review questions — reconciled October 5

1. **Settled; removed from the active queue:** retain the working WooCommerce checkout. The obsolete replacement is retired; no generic wallet/email porting task is implied. The safe integration and email renderer were already committed.
2. Build the setup conversation within existing portal authentication/outbox before making it reachable? Recommended.
3. Keep the current approved mascot/master and review the new variants alongside it? Recommended.

## Final source verification

Fresh independent clone at tested merge `52135e3` passes the site identity guard, ten frontend/commerce suites, PHP/backend security and offline renderer tests, and WordPress static checks. Exact release builds/extraction compare **263/263** integrated artifact files; relative to confirmed production, **249 unchanged, 2 changed, 12 added, zero removed**. Changed existing webroot files are only `lib/config.php` (disabled unreferenced prototype config) and `portal/js/admin-trivia.js` (separate sample artwork). Added files are 11 creative assets plus the renderer already required by main’s helper. No `pay/`, `payment-setup/`, historical script or migration enters the webroot.

Local baseline-relative browser simulation at 390×844 and 1280×844 with reduced motion confirms fictional sample generation, literal holder escaping, loaded new artwork/logo, zero page errors and zero writes. It also documents inherited **480px document width at 390px** and **two visible tour controls** in both baseline and integrated source. This is a preserved baseline issue, not mobile acceptance or a new regression; fix and owner testing remain a separate release gate. Evidence and captured screenshots are in `2026-10-04-event-cinema-integration-evidence/`. No browser test contacted a payment or mail provider.

Original 82-path dispositions: 13 already on main, 33 integrated verbatim, 4 adapted safely, 32 retained main/withheld candidate variants (including creator-credit/cache-key conflicts with no lost capability). The exact behavior review table is the owner decision scope; do not treat all formatting variants as a request for approval. Historical local source/reflog objects may contain credentials; no push is authorized.
