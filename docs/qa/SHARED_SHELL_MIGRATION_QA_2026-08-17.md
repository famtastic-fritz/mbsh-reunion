# Shared Public Shell Migration QA — August 17, 2026

## Outcome

All nine primary public routes now use one navigation implementation, one route registry, and one footer implementation from `frontend/js/cinematic-shell.js`. The retired compass navigation, compass script, compass stylesheet reference, and duplicate branded back control were removed from page source and reusable templates.

## Functionality preserved

- RSVP form and diploma experience
- Ticket-order and sponsor-inquiry forms
- Dinner-preference form
- Class check-in survey
- Memory submission
- Memorial listing
- Time-capsule submission
- Playlist suggestion
- Page-aware Hi-Tide Harry

The shell contract test asserts that each page retains its form/list DOM contract and its page-specific controller script or submission handler.

## Rendered-browser proof

Playwright loaded all nine routes at 390 × 844 and 1440 × 900. Every route rendered exactly one shared header, zero legacy compass navigations, the canonical footer, an operable drawer with root-absolute links, and no horizontal overflow. The static preview cannot serve the existing `/in-memory.php` API; that expected preview-only 404 was isolated from shell verification and the backend security suite remained green.

## Canonical routing

- `/menu/` is the sole dinner-preference source.
- `/menu.html` permanently redirects to `/menu/` through `.htaccess`, with an HTML fallback for non-Apache previews.
- Every shared-shell navigation destination is root-absolute.

## Regression commands

```bash
node tests/frontend/shared-public-shell.test.mjs
node tests/frontend/seo-contract.test.mjs
node tests/frontend/portal-contract.test.mjs
php tests/backend/portal_security_test.php
```

## Rollback

Revert the shared-shell migration commit. Do not reintroduce page-relative navigation or maintain two rendered copies of the Menu page.
