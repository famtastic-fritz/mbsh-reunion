# MBSH Cinematic Vertical Slice

## Purpose

Prove that the reunion site can become a reusable, mobile-first cinematic experience without breaking its working PHP-backed attendee workflows.

## Goal

Deliver an isolated, testable homepage + RSVP vertical slice with a shared cinematic shell, conventional expandable navigation, the approved OpenArt Harry movie, and a personality-driven Harry assistant that moves contextually without obstructing tasks.

## Tasks

- [x] Create a dedicated Git worktree and branch from the live repository lane.
- [x] Reproduce the current dirty working state inside the isolated worktree without altering the source lane.
- [x] Build the shared cinematic design tokens, shell, header, navigation, media, and form primitives.
- [x] Replace the circular chatbot trigger with a free-moving, page-aware Harry guide.
- [x] Recompose the homepage using the approved Harry movie and reusable containers.
- [x] Recompose RSVP while preserving field names, endpoint behavior, validation, consent, and success hooks.
- [x] Verify desktop, mobile, reduced-motion, keyboard, console, navigation, form state, and fallback behavior.
- [x] Record defects, fixes, reusable component rules, and promotion guidance.

## Status

Complete — isolated proof ready for review

## Started

2026-08-14

## Ended

2026-08-14

## Execution

- Branch: `codex/cinematic-facelift-proof`
- Worktree: `/Users/famtastic-fritz/Development/FAMtastic/sites/site-mbsh-reunion-cinematic-proof`
- Source lane remains: `/Users/famtastic-fritz/Development/FAMtastic/sites/site-mbsh-reunion`
- Landing expectation: review the proof locally and promote deliberately after approval; do not overwrite `main` from the proof lane.

## Research

- `docs/MBSH_CINEMATIC_FACELIFT_BLUEPRINT_2026-08-14.md`
- FAMtastic Site Studio doctrine and its template-first/shared-shell rules.
- Existing reunion page audit images, form scripts, endpoints, media assets, and premiere system.

## Review

- Visual comparison at desktop and mobile widths.
- Functional RSVP state and endpoint-contract review.
- Motion/reduced-motion review.
- Harry obstruction, focus, and personality-state review.

## Skills

- Local Site Studio reference doctrine (reference only; no unattended Site Studio build invocation).
- Browser QA for the local proof.

## Proof

- Local proof URL and screenshots.
- Focused automated assertions and browser checks.
- `git diff --check`.
- Implementation learning and post-evaluation records.

## Result

- Proof implementation: `frontend/css/cinematic-system.css`, `frontend/js/cinematic-shell.js`, updated homepage and RSVP composition.
- Harry implementation: `frontend/js/chatbot.js` plus the full-character and mobile-sheet rules in `frontend/css/chatbot.css`.
- Repeatable check: `scripts/qa-cinematic-proof.sh`.
- Learning record: `docs/implementation/CINEMATIC_VERTICAL_SLICE_IMPLEMENTATION_2026-08-14.md`.
