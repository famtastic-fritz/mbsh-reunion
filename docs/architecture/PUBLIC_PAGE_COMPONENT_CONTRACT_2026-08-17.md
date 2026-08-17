# Public page component contract

## Page structure

Every public route follows one composition model:

`Page = SharedShell(Header + Navigation + Footer) + PageContent(ordered Sections) + PageAwareHarry`

- `frontend/js/cinematic-shell.js` is the runtime authority for the visible header, route-safe navigation, and footer.
- Public HTML retains semantic fallback content, but nested routes must never use relative site-navigation links.
- Each page declares one `data-page` value. Navigation state and Hi-Tide Harry use it as page context.
- Page-specific visuals, forms, archives, players, and calls to action live in content sections—not in the shared shell.
- Reusable sections must have one clear job, one data/admin owner, and an accessible non-cinematic fallback.

## RSVP decision

The legacy readiness strip and decorative seat row were removed because they appeared to be unexplained buttons and competed with the approved diploma interaction. The production RSVP journey is now:

`Shared hero -> Start RSVP -> Diploma opens -> Working RSVP form -> Confirmation`

The diploma currently belongs to RSVP/reservation because it represents the attendee's official reunion record. It is a reusable component, but it should not be placed on unrelated pages without a meaningful use case.

## Enforcement

Run:

`node tests/frontend/shared-public-shell.test.mjs`

The contract fails when a primary page omits the shared shell, omits page identity, loses a canonical route, or restores the competing RSVP decorations.

## Migration status — August 17, 2026

The legacy compass shell has been removed from all primary public-page source and from reusable page templates. Page files now provide content, a shared-footer mount, page identity, and feature-specific controllers. The shared shell exclusively owns the visible header, navigation registry, drawer behavior, and footer content. `/menu/` is canonical; `/menu.html` is redirect-only compatibility.
