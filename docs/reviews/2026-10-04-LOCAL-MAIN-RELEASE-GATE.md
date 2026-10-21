# MBSH local-main release gate — October 4, 2026

Purpose: keep the verified public release separate from unfinished Event Cinema work while preserving a recoverable next step.

Goal: use a known code baseline, review one coherent next slice, and require its own proof before any customer release.

- [x] Reconcile the live code marker and public manifest with Git.
- [x] Prove a code-only restore from the exact live commit in a disposable directory.
- [x] Classify the Event Cinema worktree by public presentation, payment, portal and outbound-message authority.
- [ ] Choose the next intended customer-visible subset with the owner.
- [ ] Commit and test that subset on a branch from current `main`; verify its exact package, data and owner flow before promotion.

Status: candidate; no new MBSH capability deployed or owner accepted.

Started: 2026-10-04

Ended: 2026-10-04 for the read-only classification; release selection remains open.

Execution: read-only production source-marker and manifest check, isolated code-package rebuild, and local branch inventory. The `codex/mbsh-analytics` Event Cinema worktree was preserved unchanged.

Research: the live Inc-host root's `current`, `DEPLOYED_COMMIT`, and release `commit` all named `67f22480d6fe2a3596d9b6117279097c8db54768`; 251/251 manifest paths matched the public code files at observation. A disposable build and extraction of the same Git commit matched 251/251 files. This is code parity and code reconstruction, not database/uploads/secrets recovery or an attendee, ticket, payment, mail or committee journey.

Review: `codex/mbsh-analytics` at `c518186` held 82 path-level changes (34 tracked, 48 untracked) and diverged from the live commit by 44 live-side and 12 branch-side commits. Three payment PHP files linted and two frontend contract tests passed locally, but no provider, migration/reversal, owner-price, or live-payment receipt exists. Public shell/media, financial lifecycle, portal authority and send scripts require separate reviews. Do not merge or stage this whole worktree. The retention candidate `bd77228` has Bash syntax proof only and is not installed; no host pruning was performed.

Skills: FAMtastic build review and Agency Agents Git Workflow Master, pinned upstream `765be42358100bf89d2faa567668a94c602f9a26`.

Proof: local source baseline `730eea5c47a9b6f61bbf118a1c88a36d03d5ce02` (the verified remote `main` head at this review), live code `67f22480d6fe2a3596d9b6117279097c8db54768`, and the umbrella Development closeout `docs/briefs/development-briefing/MBSH-SHAY-HOSTING-SHARED-CLOSEOUT-2026-10-04.md` in its separate repository. Release-handoff classification: `candidate`; capability and owner-task acceptance remain pending because this record changes no customer behavior.

## Subsequent authorized integration

The owner subsequently authorized committing the complete candidate and merging verified safe source, with exact production regressions held for review. The earlier tentative no-staging/next-slice boundary is superseded only for this local source integration. See `2026-10-04-EVENT-CINEMA-SAFE-INTEGRATION.md` and its receipt. Deployment and business-data/provider/owner gates remain closed.
