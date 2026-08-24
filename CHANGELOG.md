# Changelog

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
