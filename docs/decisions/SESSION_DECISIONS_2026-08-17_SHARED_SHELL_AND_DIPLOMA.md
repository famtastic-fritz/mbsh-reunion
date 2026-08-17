# Session decisions — shared shell and diploma RSVP

Date: 2026-08-17

## Decisions

1. Public pages use one universal composition model: shared header/navigation/footer, page-specific ordered sections, and page/component-aware Hi-Tide Harry.
2. `frontend/js/cinematic-shell.js` is the runtime authority for the visible public shell and canonical root-absolute route registry.
3. Static HTML remains as a semantic fallback, but nested routes may never use page-relative global navigation.
4. The diploma interaction represents the attendee's official reunion record and is currently assigned to RSVP/reservation. It is reusable, but it is not a generic wrapper for unrelated forms.
5. Tickets, dinner preferences, yearbook/memories, memorial tributes, and other experiences keep purpose-specific components within the common shell.
6. Unexplained decorative elements that resemble controls are prohibited. The RSVP readiness strip and decorative theater-seat row were removed after they were reasonably interpreted as broken buttons.
7. Mobile auto-opening may reveal the compact diploma form, but it must not steal focus or scroll users past the hero.

## Production result

- Nine primary public pages load the shared shell.
- `/menu/` navigation uses root-absolute routes and no longer prefixes destinations.
- RSVP has one interaction logic: hero, diploma, working form, confirmation.
- Production browser proof passed at 390×844 and 1440×900 with zero console errors and no horizontal overflow.
- Release commits: `c247f5e` and documentation commit `4fdc3d3`.
- Rollback archive: `/home/nineoo/backups/shared-shell-20260817T153500Z`.

## Agent and product learning

- Visual consistency is not template architecture. A shared look without a
  shared source of truth still permits route drift.
- Global structure must be centrally owned and mechanically tested.
- Page content is a sequence of accountable components; each component needs a
  purpose, data/admin owner, working states, accessible fallback, and Harry
  context when guidance is useful.
- Site Studio and Shay must treat the shared-shell contract as a build invariant,
  not a styling suggestion.

## Verification

Run:

`node tests/frontend/shared-public-shell.test.mjs`

Canonical references:

- `docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`
- `docs/deploy/PRODUCTION_SHARED_PUBLIC_SHELL_2026-08-17.md`
- `site-studio/recipe/HEADLESS_EVENT_PLATFORM_RECIPE.md`
- `site-studio/recipe/capability-manifest.json`

Google Drive mirror:

- Folder: `FAMtastic-2026 / MBSH 1996 Reunion — Event Cinema`
- Decision log: https://docs.google.com/document/d/16MdEAtdFnZni4TzzV4WIlscThTf-wr-qKjDxazBx0Bs/edit
