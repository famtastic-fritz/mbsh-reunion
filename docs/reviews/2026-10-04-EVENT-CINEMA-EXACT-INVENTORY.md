# MBSH Event Cinema exact working-tree inventory — October 4, 2026

**Decision status:** preserved for review, not approved for archival disposition or release. This is the `codex/mbsh-analytics` worktree at `c518186b0953db2805539e690dcfd6d442bca8b6`, compared by exact Git blob bytes with production code commit `67f22480d6fe2a3596d9b6117279097c8db54768` and current local `main` `07043ac41845dfd64742d51b88b8d872d803f27b`. A byte difference does not prove that a feature is absent or safe to merge; identical bytes do not prove live behavior. The production comparison is code only, not database, uploads, payments or inboxes.

**Scope and recovery:** 82 dirty status paths (34 tracked changed, 48 untracked). A separate mode-0600 preservation copy and SHA-256 manifest exist at `/Users/famtastic-fritz/Development/FAMtastic-private-evidence/2026-10-04/mbsh-event-cinema-dirty-82.tar.gz` and the sibling `.manifest.json`; archive SHA-256 `8d368b17670235339c54fd64961f294a747769a14f26dcb4d90d91ae2d95bdfa`. The manifest has 82 entries, 81 existing file bytes were extraction-hash checked, and one entry is a tracked deletion. Originals remain in the dirty worktree. This preservation copy does not authorize hiding, deleting, resetting, merging or releasing the source.

**Exact-byte comparison with production:** 13 identical, 23 different, 45 absent from production, 1 deleted in this checkout while present in production. Against current local `main`: 13 identical, 23 different, 45 absent, 1 deleted here. The status is relative to the old branch `HEAD`, so some dirty paths already match production bytes.

## Dirty paths by workstream

Status is `M` modified, `D` deleted, or `??` untracked. “Production” and “Main” are exact-byte comparisons for the current worktree file at the stated commits.

### Ticket and payment lifecycle (25)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `??` | `backend/lib/ticket-payments.php` | different | different |
| `??` | `backend/migrations/20260902_ticket_payment_link_lifecycle.sql` | absent | absent |
| `M` | `backend/ticket-order.php` | different | different |
| `M` | `backend/tickets.html` | different | different |
| `??` | `docs/operations/STRIPE_PAYMENT_LINK_TICKET_LIFECYCLE_2026-09-02.md` | absent | absent |
| `??` | `docs/operations/STRIPE_WOOCOMMERCE_CONNECT_HANDOFF_2026-08-31.md` | absent | absent |
| `??` | `frontend/assets/tickets/stripe-payment-link-qr.png` | absent | absent |
| `??` | `frontend/pay/index.html` | absent | absent |
| `??` | `frontend/payment-setup/conversation-v2.css` | absent | absent |
| `??` | `frontend/payment-setup/conversation.css` | absent | absent |
| `??` | `frontend/payment-setup/conversation.js` | absent | absent |
| `??` | `frontend/payment-setup/guard-talk.css` | absent | absent |
| `??` | `frontend/payment-setup/index.html` | absent | absent |
| `??` | `frontend/payment-setup/index.php` | absent | absent |
| `??` | `frontend/payment-setup/payment-setup.css` | absent | absent |
| `??` | `frontend/payment-setup/secure-form.css` | absent | absent |
| `??` | `frontend/payment-setup/text-animation.css` | absent | absent |
| `??` | `frontend/portal/js/ticket-payment-desk.js` | different | different |
| `M` | `frontend/tickets.html` | different | different |
| `??` | `scripts/list-pending-ticket-orders.php` | absent | absent |
| `??` | `tests/frontend/sample-ticket-contract.test.mjs` | absent | absent |
| `??` | `tests/frontend/stripe-payment-link-contract.test.mjs` | absent | absent |
| `M` | `wordpress/wp-content/plugins/famtastic-reunion-platform/famtastic-reunion-platform.php` | different | different |
| `M` | `wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-operations.php` | different | different |
| `M` | `wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-tickets.php` | different | different |

### Portal and committee operations (15)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `M` | `backend/portal/_bootstrap.php` | identical | identical |
| `M` | `backend/portal/committee/action.php` | identical | identical |
| `??` | `backend/portal/staff/dinner.php` | identical | identical |
| `M` | `backend/portal/staff/operations.php` | identical | identical |
| `M` | `backend/portal/staff/people.php` | identical | identical |
| `M` | `backend/portal/verify-email.php` | identical | identical |
| `M` | `frontend/portal/committee/index.html` | different | different |
| `??` | `frontend/portal/css/sample-ticket-studio.css` | identical | identical |
| `M` | `frontend/portal/index.html` | different | different |
| `M` | `frontend/portal/js/admin-harry-guide.js` | identical | identical |
| `M` | `frontend/portal/js/admin-trivia.js` | different | different |
| `M` | `frontend/portal/js/committee-workspace.js` | different | different |
| `M` | `frontend/portal/js/portal-api.js` | different | different |
| `M` | `frontend/portal/js/portal-config.js` | identical | identical |
| `??` | `scripts/promote-valerie-committee-lead.php` | absent | absent |

### Email and send helpers (9)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `??` | `backend/lib/portal-email-templates.php` | absent | absent |
| `??` | `docs/email/ACT_I_EMAIL_CATALOG_AND_SCHEDULE.md` | absent | absent |
| `??` | `docs/email/proofs/shay-love-note-junyeismom-2026-08-26.html` | absent | absent |
| `??` | `scripts/send-approved-20260826-campaign.php` | absent | absent |
| `??` | `scripts/send-committee-email-drafts.php` | absent | absent |
| `??` | `scripts/send-gussie-stripe-connection-instructions.php` | absent | absent |
| `??` | `scripts/send-legacy-welcome-20260826.php` | absent | absent |
| `??` | `scripts/send-stripe-options-valerie.php` | absent | absent |
| `??` | `scripts/send-ticket-launch-handoff-committee.php` | absent | absent |

### Frontend presentation (6)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `M` | `frontend/404.html` | different | different |
| `M` | `frontend/css/reunion-navigator.css` | identical | identical |
| `M` | `frontend/css/sections.css` | different | different |
| `M` | `frontend/js/cinematic-shell.js` | identical | identical |
| `M` | `frontend/js/reunion-navigator.js` | identical | identical |
| `M` | `frontend/js/ticket-order.js` | different | different |

### Creative and campaign media (21)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `??` | `frontend/assets/characters/shay/concepts/digital-fairy-v1.png` | absent | absent |
| `??` | `frontend/assets/characters/shay/email/shay-love-note-v1.png` | absent | absent |
| `??` | `frontend/assets/characters/shay/shay-stripe-call-sheet-v1.png` | absent | absent |
| `D` | `frontend/assets/mascot/09-confused.png` | deleted here | deleted here |
| `??` | `frontend/assets/mascot/act-1/cartoon/famtastic-presenter-v1.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/cartoon/registration-welcome-v1.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/cartoon/ticket-premiere-v1.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/cartoon/trailer-curtain-host-v1.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/famtastic-starter-presenter.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/registration-welcome.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/ticket-premiere.png` | absent | absent |
| `??` | `frontend/assets/mascot/act-1/trailer-curtain-host.png` | absent | absent |
| `M` | `frontend/assets/social/2026-reunion-hype/09-ultra-real-practical-effects.png` | different | different |
| `M` | `frontend/assets/social/2026-reunion-hype/README.md` | different | different |
| `M` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/CAMPAIGN-COPY.md` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_12.50__memory-panel_0.jpg` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_12.50__memory-wash_0.jpg` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_2.50__grand-panel_0.jpg` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_2.50__grand-wash_0.jpg` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_7.50__roll-panel_0.jpg` | absent | absent |
| `??` | `marketing/video-ads/hi-tide-harry-openart-ads-20260820/hyperframes/.thumbnails/v4_v3_027ede3ecf9fbcfa_jpeg_preview_index.html_1080x1920_76x135_1787261600525_7.50__roll-wash_0.jpg` | absent | absent |

### Configuration and data contract (6)

| Git | Path | Production | Main |
| --- | --- | --- | --- |
| `M` | `.env.example` | different | different |
| `M` | `backend/404.html` | different | different |
| `M` | `backend/lib/config.php` | different | different |
| `M` | `backend/lib/production-snapshot.php` | identical | identical |
| `M` | `backend/schema.sql` | different | different |
| `M` | `spec.json` | different | different |

## Branch-only history by ancestry

These twelve commits are present on this branch and not ancestors of current local `main`; they are not twelve proven unreleased features. Some resulting file bytes already match production.

- c518186 2026-09-09 docs: extract MBSH design contract
- 89e472f 2026-08-23 feat: add MBSH reunion field guide
- 76e103d 2026-08-23 feat: add role-aware MBSH reunion guide
- 24fc566 2026-08-20 feat: add Hi-Tide Harry OpenArt ad projects
- 2edd5c3 2026-08-20 feat: add OpenArt Hi-Tide Harry movie trilogy
- 6dc4ef8 2026-08-20 test: add Hi-Tide Harry story scene sequence
- 027540a 2026-08-20 test: capture MUAPI Instagram post comparison
- 98316d1 2026-08-20 feat: add Hi-Tide Harry social media kit
- 5620426 2026-08-20 docs: record MBSH alumni portal production release
- 1678b79 2026-08-20 fix: keep mobile Harry guide behind alumni invite
- 6f7d58a 2026-08-20 feat: foreground MBSH alumni portal registration
- 2daccb4 2026-08-18 Add MBSH production analytics

## Before an archival or release decision

1. Identify which of the 45 paths absent from the production code commit are intended next-release source, historical prototypes, generated previews, or unapproved private material. The send scripts, payment lifecycle, and character/marketing assets need separate authorization and provenance checks.
2. For any proposed release slice, compare its exact source against newer `main`, then prove package and migration recovery, payment/provider authority, owner/browser journey, and release-matched acceptance. The local syntax and two frontend contract tests are not that proof.
3. If the owner chooses historical preservation, keep this checksum-backed copy and the branch until a reviewed exact-file disposition is recorded. No path is removed or hidden by this inventory.
