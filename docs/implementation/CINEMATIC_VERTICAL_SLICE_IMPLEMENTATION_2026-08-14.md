# Cinematic Vertical Slice — Implementation and Learning Record

## Outcome

The isolated proof demonstrates a mobile-first cinematic homepage and RSVP journey without replacing the current PHP-backed form contracts. It also replaces the circular chatbot metaphor with a full-character, page-aware Hi-Tide Harry who can move between sides of the experience, step aside during tasks, react to submissions, and open a stable conversation surface.

This is a proof branch, not a production deployment.

## Isolation

- Source repository: `/Users/famtastic-fritz/Development/FAMtastic/sites/site-mbsh-reunion`
- Proof worktree: `/Users/famtastic-fritz/Development/FAMtastic/sites/site-mbsh-reunion-cinematic-proof`
- Proof branch: `codex/cinematic-facelift-proof`
- Source lane was not modified by this implementation.
- The proof inherited the source lane's working state so the test represents the current site, not an obsolete clean commit.

## Site Studio Rules Applied

1. **Research first:** the existing page audit, cinematic blueprint, forms, endpoints, Premiere theme, and available assets were inspected before recomposition.
2. **Template first:** shared chrome and behavior live in `cinematic-system.css` and `cinematic-shell.js`; the pages consume those primitives.
3. **Reuse before generation:** the approved OpenArt/Kling movie, existing medallion, existing Harry pose library, current form fields, and current content were reused.
4. **Do not break working paths:** RSVP field names, IDs, validation hooks, consent copy, endpoint behavior, and success containers remain intact.
5. **Proof before promotion:** syntax, assets, browser layout, interaction, mobile behavior, console output, and focused diffs were checked in the worktree.

## Component Blueprint Proven

### Shared shell

- Fixed branded header with the commemorative mark.
- Extendable hamburger drawer instead of an ornamental navigation control.
- Current-page state, prominent RSVP action, backdrop close, Escape close, and keyboard focus loop.
- Skip link and focusable primary content target.
- The legacy compass remains in markup for rollback but is hidden only when the proof shell is active.

### Cinematic media

- The homepage hero uses the approved Harry reunion movie, with a dedicated 720×988 mobile delivery encode.
- The still image is preloaded and used as a poster; the optimized 1.1 MB movie is loaded progressively after the page initializes. The 19 MB source remains preserved as the production master.
- Reduced-motion and reduced-data users remain on the still experience.
- The RSVP page proves the same media container with a high-quality still instead of forcing video everywhere.

### Journey containers

- Hero: promise, event identity, and primary action.
- Status strip: date, destination, and attire.
- Next-scene cards: RSVP, ticketing, and reunion story.
- RSVP progress: check-in, RSVP, tickets, and meal.
- Existing long-form page scenes remain below the new entry experience for comparison and gradual migration.

### Hi-Tide Harry

Harry is not a circular utility icon in this proof.

- Full transparent character artwork is the visible control.
- Page personality map selects pose, greeting, prompt, and suggested questions.
- Scroll progress changes Harry's side of the viewport and briefly uses a walking pose.
- Form focus moves Harry into a polite, reduced-obstruction state.
- Menu-open events also make Harry step back.
- Form submission changes Harry into a cheering pose.
- The chat panel uses curated answers first and keeps the existing committee fallback endpoint for unknown questions.
- On mobile, conversation becomes a stable bottom sheet; Harry's movement transform is intentionally separated from the open-panel state.
- Reduced-motion users get the personality and answers without travel animation.

## Errors Found and Resolved

1. **Duplicate navigation:** the legacy compass and cinematic drawer appeared together. Resolution: hide the compass only inside `.cinematic-proof`, leaving rollback markup intact.
2. **Forced scene position:** the old mandatory snap behavior could restore or settle the homepage deep in the story. Resolution: remove `data-snap` from the proof homepage and keep ordinary scrolling.
3. **Mobile chat compression:** the full-character movement transform became the containing block for the fixed chat panel, squeezing it to character width. Resolution: the open state expands Harry's root to the viewport and disables the movement transition while conversation is open.
4. **Large movie pressure:** immediately loading the movie would compete with first paint. Resolution: poster-first progressive loading, with reduced-motion/save-data exceptions.
5. **Required-field bypass:** the inherited `novalidate` form submitted before checking native constraints. Resolution: call `checkValidity()` and `reportValidity()` before any network request.
6. **Mobile Harry collisions:** Harry could cover CTAs and form controls. Resolution: use a smaller phone perch, hide the proactive bubble on scroll, detect task regions, and move Harry mostly beyond the active edge while preserving a character-shaped reopen target.
7. **Duplicate legacy interaction layer:** the old medallion/menu and scene chevrons remained active behind the new shell. Resolution: feature-gate them out of the cinematic proof skin.
8. **Long RSVP path:** countdown content separated the hero from the form. Resolution: prioritize the form immediately after the hero/progress at runtime while retaining the countdown below it.

## Browser Evidence

Tested locally at `http://127.0.0.1:8946/` on 2026-08-14.

- Desktop viewport: 1280 × 720.
- Mobile viewport: 390 × 844.
- Homepage and RSVP horizontal overflow: none.
- Mobile menu target: 44 px.
- Legacy compass in proof skin: hidden.
- Homepage movie: loaded and playing; poster remains the fallback.
- Harry trigger border radius: 0 px.
- Harry movement: right at entry, left at middle-page progress, then right later.
- Mobile chat: 360 px wide inside a 390 px viewport, stable and readable.
- RSVP conditional details: hidden initially and revealed after selecting “Yes.”
- RSVP input controls: approximately 48 px tall.
- Desktop RSVP grid: two columns; mobile grid: one column.
- Drawer: opens, reports its state, moves focus inside, closes with Escape, and returns focus.
- Curated Harry privacy response: returned correctly.
- Browser console errors across the tested interactions: none.
- RSVP submission contract remains compatible; a distinct optional `maiden_name` field was added because the backend already accepts it.
- Parent FAMtastic plan audit: clean; no drift, conflicts, missing active files, or orphan tasks.

## Independent QA Agent Review

Three independent agents reviewed the proof after implementation:

- Mobile/accessibility: verified 320×568 and 390×844 without horizontal overflow or broken media.
- Functional logic: verified drawer state, RSVP disclosure, Harry personas/FAQ/fallback/movement, video fallback, routes, and console health.
- Visual/brand: scored desktop direction 9.4/10, brand consistency 9.5/10, imagery 9.2/10, mobile polish 8.1/10 before corrections, and expansion readiness 8.7/10.

Their material findings—validation bypass, mobile Harry collisions, legacy duplicate controls, drawer focus timing, missing attendee anchor, video weight, and RSVP task distance—were corrected in the proof. Real RSVP persistence and email confirmation still require a controlled PHP/backend staging pass; the static local server cannot honestly prove those external effects.

The final RSVP submission was intentionally not transmitted during local visual QA. Its existing handler and field contract were preserved; endpoint integration belongs to the controlled staging acceptance pass before promotion.

## Repeatable Recipe

1. Create a named worktree and proof branch from the target site.
2. Reproduce the current working state in the proof lane without cleaning the source lane.
3. Inventory page goals, working forms, endpoints, branded assets, media, and existing behavior.
4. Define shared tokens and containers before editing individual pages.
5. Prove one acquisition page and one task page first.
6. Separate character choreography from task interaction states.
7. Preserve existing IDs, names, endpoints, consent, and success hooks.
8. Run `./scripts/qa-cinematic-proof.sh http://127.0.0.1:8946` while the local proof server is active.
9. Test desktop, mobile, keyboard, reduced motion, conditional form states, and console output in a real browser.
10. Record collisions and solutions before expanding the system page by page.
11. Promote through a reviewed patch or controlled merge; never overwrite the source lane from the proof worktree.

## Recommended Expansion Order

1. Tickets and sponsorship: cinematic offer cards plus clear payment-state truth.
2. Dinner preferences: ticket-aware selection journey.
3. Class check-in: short, distinct pre-RSVP signal.
4. Through the Years: horizontal era reel with upload workflow.
5. Memorial: respectful low-motion treatment.
6. Time capsule: private guided composition.
7. Soundtrack: interactive audio stage.
8. Shared authenticated readiness state across RSVP, tickets, and meal selection.

Each expansion should reuse the shell and Harry contract, add only the page-specific components it needs, and repeat the same proof gate.
