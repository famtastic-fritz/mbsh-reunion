# Production shared public shell — 2026-08-17

## Outcome

The nine primary public routes now load one shared visible header/navigation/footer implementation from `frontend/js/cinematic-shell.js`. Canonical navigation uses root-absolute paths, including `/menu/`, so nested routes cannot prefix destination URLs.

The RSVP page now presents one interaction flow: cinematic hero, approved diploma experience, working RSVP form, and confirmation. The unexplained legacy readiness strip and decorative seat row were removed. Mobile auto-opening no longer steals focus or scrolls past the hero.

## Proof

- Shared-shell contract: 9/9 pages pass.
- Production browser QA: 8 interior routes pass at 390×844 and 1440×900.
- Production console errors: 0 in both viewport runs.
- Horizontal overflow: 0 tested routes.
- RSVP production assertions: hero starts at scroll position 0; diploma present; legacy journey strip absent; legacy seat row absent.
- HTTP status: RSVP, menu, tickets, survey, through-years, memorial, capsule, and playlist return 200.

## Source and rollback

- Release commit: `c247f5e`
- Production backup: `/home/nineoo/backups/shared-shell-20260817T153500Z`
- Architecture contract: `docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`
- Enforcement: `node tests/frontend/shared-public-shell.test.mjs`

Rollback restores the backed-up public HTML and the two backed-up JavaScript files. No database, email, WordPress, WooCommerce, RSVP endpoint, or Stripe configuration changed in this release.
