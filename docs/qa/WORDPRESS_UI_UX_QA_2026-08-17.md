# WordPress UI/UX QA — 2026-08-17

## Experience model

The platform has three intentionally different experiences:

1. **Attendee Portal** — task-focused, mobile-first, no WordPress surface.
2. **Admin Portal** — committee and owner operations, permission-driven modules, guided by Hi-Tide Harry.
3. **WordPress Owner Studio** — full editorial, growth, commerce, integrations, and platform configuration for the site owner only.

## UI findings

| Area | Result | Correction |
|---|---|---|
| Owner orientation | Previously fragmented | Added **Growth & Delivery**, with status cards, authority rules, consent proof, and worker status. |
| Marketing discoverability | Previously absent | FluentCRM is reachable from Growth & Delivery; Harry explains audience safety and campaign boundaries. |
| Forms discoverability | Previously absent | Fluent Forms is reachable from Growth & Delivery and explicitly scoped to polls/surveys/volunteer/contact use cases. |
| Delivery confidence | Host mail was opaque | Resend connection is visible in the owner screen; controlled delivery proof is recorded. |
| Scheduled work | No visual inspection path | WP Crontrol provides owner-only event inspection; Harry explains late/failed jobs. |
| Security | Password only | Two-Factor enrollment is linked from the owner screen, without unsafe auto-enforcement. |
| Terminology | Generic plugin names can confuse operators | Custom screen leads with jobs-to-be-done, not plugin names. |
| Mobile | Existing custom admin CSS already has responsive targets and tables | New integration cards use auto-fit grids and collapse without horizontal overflow. |
| Guidance | Harry lacked marketing/forms/cron lessons | Added contextual answers and a Growth & Delivery screen lesson. |

## Usability doctrine

- Every visible tool answers: what job is this for, what system owns the truth, who may use it, and what proves success.
- Plugin menus are implementation details; the custom owner screen is the operational map.
- Attendees never see WordPress or plugin terminology.
- Committee admins use the branded Admin Portal for people, replies, moderation, RSVP, dinner, tickets, and permissions.
- The owner uses WordPress for editorial and growth configuration, and can still open the Admin Portal to see the committee experience.
- Empty states must explain what creates the first record; they may never invent production activity.

## Remaining manual checks

- Enroll the site owner in Two-Factor and store recovery codes.
- Open each vendor plugin screen at 390px and 1280px while authenticated; isolate any vendor-specific narrow-screen correction.
- Draft—not send—the first campaign and verify unsubscribe/footer rendering.
- Create one unpublished Fluent Form and preview its cinematic frontend wrapper before public use.

