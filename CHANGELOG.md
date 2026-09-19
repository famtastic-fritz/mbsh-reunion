# Changelog

## 2026-09-09

### Git-addressed production recovery

- Replaced file-copy rollback guidance with immutable Git-addressed application releases and rollback-by-commit.
- Preserved production payment fulfillment, paid-order reconciliation, shared-host isolation, portal data visibility, and the corrected role-aware Committee Desk tour in source control.
- Corrected the reconciliation marker so background checks inspect the same idempotency record written by ticket issuance.
- Kept orders, payments, tickets, databases, secrets, uploads, and curated social/yearbook media outside application-code rollback scope.
- Added release manifests, checksum verification, dry-run change review, recoverable retired-file handling, and deployment documentation.

## 2026-08-24

### Reunion Field Guide and role-safe tours

- Reconciled the already-live analytics and Alumni Login release history into `main` so Git and the GoDaddy frontend share one source record.
- Added the public `/manual/` Reunion Field Guide, presentation mode, role-safe attendee/committee/owner chapters, and the companion owner deck.
- Added a non-mutating `How it works` tour on public pages plus `Guide` / `Start tour` entry points for verified attendees and committee members.
- Bumped the shared-shell asset reference to `cinematic7` so returning visitors receive the tour rather than a cached prior shell.
- Promoted the verified frontend package to GoDaddy with an atomic, file-scoped release; no attendee records, payments, mail, uploads, or CMS data changed.

## 2026-08-18

### Privacy-safe site analytics

- Added a dedicated GA4 loader for the MBSH production property with advertising consent denied by default.
- Added page-view, CTA, and confirmed form-outcome events without sending form answers, email addresses, order codes, or other personal data.
- Added the analytics loader to all nine public routes and the reusable page templates.
- Registered Google Site Kit as the private WordPress analytics dashboard for the sole administrator.
- Added contract tests for route coverage, event coverage, and prohibited sensitive fields.

## 2026-08-17

### Unified public experience

- Established `frontend/js/cinematic-shell.js` as the only public header, navigation, drawer, route registry, and footer source.
- Removed the retired compass shell and duplicate back-header controls from all primary public routes and page templates.
- Preserved RSVP, tickets, sponsorship, dinner, survey, memories, memorial, time-capsule, playlist, and Hi-Tide Harry functionality.
- Canonicalized Dinner Preferences at `/menu/`; `/menu.html` now redirects permanently.
- Added shell-exclusivity and feature-contract regression tests.
- Added architecture, QA, agent, Site Studio, and campaign-creative records.


## 2026-09-14 Independent repository foundation

Private-by-default customer source ownership, complete agent/design/research/provenance records and clean-clone verification are mandatory. Preserve the existing remote identity and public/private visibility; no provider, DNS, owner, database or email change was performed. Original source revision: `5ed9a49314aac4195354094b56c1e1abe2603d4d`. Source task: Codex01a097f9-4915-7640-b92f-abd74a1e49ca. Public crawler/policy files remain in their authored public roots; the scaffold does not overwrite them.
# 2026-09-18 — Mandatory creator credit

- Added exact original FAMtastic logo as a centered final linked row across
  52 authored HTML documents, including source templates and portal previews.
- Added deterministic synchronization, immutable-asset checking and release
  packaging enforcement. Existing footer copy and event workflows unchanged.
- Attribution is a public site-slug UTM link, not a new visitor tracker.
- Local checks: eight test files pass; repository identity valid. Live receipt
  is recorded separately in docs/operations/CREATOR-CREDIT-2026-09-18.md.
