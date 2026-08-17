# Changelog

## 2026-08-17

### Unified public experience

- Established `frontend/js/cinematic-shell.js` as the only public header, navigation, drawer, route registry, and footer source.
- Removed the retired compass shell and duplicate back-header controls from all primary public routes and page templates.
- Preserved RSVP, tickets, sponsorship, dinner, survey, memories, memorial, time-capsule, playlist, and Hi-Tide Harry functionality.
- Canonicalized Dinner Preferences at `/menu/`; `/menu.html` now redirects permanently.
- Added shell-exclusivity and feature-contract regression tests.
- Added architecture, QA, agent, Site Studio, and campaign-creative records.
