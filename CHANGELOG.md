# Changelog

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
