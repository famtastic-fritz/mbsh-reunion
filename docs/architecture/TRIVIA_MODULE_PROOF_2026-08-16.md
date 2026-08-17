# Event Cinema Trivia Module Proof — 2026-08-16

## Purpose

Trivia is the reference vertical slice proving that Event Cinema can accept a
new feature without weakening identity, permissions, mobile UX, or operational
accountability.

## Lifecycle

```text
Staff creates draft game
  → creates and reviews questions
  → publishes questions
  → opens one game
  → verified attendee starts one attempt
  → server records each answer once and calculates score
  → completed attempt enters privacy-safe leaderboard
  → staff closes game
  → completed play remains immutable evidence
```

## Authority and boundaries

- The portal service owns live game state, published question snapshots,
  attempts, answers, scores, and leaderboard results.
- WordPress exposes reusable Trivia Game and Trivia Question editorial record
  types. A future publication adapter may promote approved WordPress revisions
  into portal snapshots; it must never rewrite completed attempts.
- Staff authoring requires `manage_event_content` on every request.
- Attendee play requires an active, verified, same-origin session and CSRF for
  all mutations.
- Correct indexes never appear in attendee GET or start payloads.
- A unique game/attendee attempt and unique attempt/question answer prevent
  replay and double scoring.
- Leaderboards abbreviate the first name and never expose email addresses.

## Proven locally

- draft game and question creation;
- publishing a reviewed question;
- refusing to open a game with no published questions;
- one-open-game control;
- attendee start, correct answer, server-side 100-point score, completion;
- correct-answer secrecy;
- replay rejection;
- privacy-safe leaderboard;
- Admin Portal authoring UI and attendee game UI;
- real browser journey from authoring through final score;
- PHP, JavaScript, schema, and fresh-database integration tests.

## Reusable feature-module recipe

Every future feature must provide: schema with lifecycle states; server-side
capabilities; attendee ownership; CSRF; replay/idempotency rules; audit actions;
private-safe API output; empty/loading/error/completed UI states; contextual
Harry instruction; fresh-database tests; browser proof; and an explicit source
of truth plus rollback/retention behavior.
