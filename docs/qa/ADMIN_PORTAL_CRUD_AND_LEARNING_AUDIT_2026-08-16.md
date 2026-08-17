# Admin Portal CRUD and Learning Audit — 2026-08-16

## Honest verdict

The Admin Portal is connected and permission-controlled. Its launch-critical
portal lifecycles are now implemented and tested locally. Generic CRUD is not
automatically
appropriate everywhere: financial history, audit events, production snapshots,
and delivered email records should be immutable or changed through explicit
domain actions rather than generic edit/delete buttons.

| Area | Create | Read | Update/action | Delete/archive | Automated proof | Current verdict |
|---|---:|---:|---:|---:|---:|---|
| Staff memberships | Yes | Yes | Grant/change/revoke | Revoke, not hard delete | Yes | Complete local lifecycle |
| Attendee conversations | Attendee creates | Owner-scoped timeline | Attendee/committee reply and status | Close/reopen, not delete | Yes | Complete local lifecycle |
| Harry questions | Legacy intake | Yes | Audited response draft | No | Partial | Delivery and FAQ promotion missing |
| Media submissions | Attendee creates | Owner/staff scoped | Metadata resubmit; approve/reject | Withdraw, not hard delete | Yes | Portal lifecycle complete; CMS publication adapter remains gated |
| Attendee profile/preferences | Yes | Yes | Yes | No | Yes | Account deletion/privacy workflow missing |
| Current RSVP/dinner response | Yes | Yes | Attendee update | No | Yes | Committee correction workflow missing |
| Production RSVP/menu history | Production only | Yes | No | No | Snapshot proof | Correctly read-only pending migration |
| Tickets | Woo/payment flow | Summary + wallet | Audited exception issue/check-in/void | Void, not delete | Yes locally | Portal lifecycle complete; Stripe provider scenarios remain gated |
| WordPress content/components | WordPress | Yes in portal | WordPress owner only | Archive in WordPress | Partial | Portal-framed editing and preview proof pending |
| Email/outbox | System creates | Counts | Retry worker only | Dead-letter retention | Partial | Provider delivery/bounce/suppression proof pending |
| Audit/reconciliation | System creates | Yes | Never | Retention policy only | Yes for writes | Correctly immutable |
| Polls, sponsors, memorials, capsules, outreach | Legacy/WordPress | Read registry | Owner workflows | Archive in authority | Contract only | Later modules; legacy routes remain active until parity proof |

## Required completion standard

Every domain must have tests for its **allowed lifecycle**, not blindly four
CRUD verbs. Each test suite must cover:

1. successful authorized action;
2. ordinary attendee and insufficient-role denial;
3. invalid input and missing record;
4. conflict, duplicate, and idempotent replay;
5. audit trail and notification side effects;
6. mobile completion at 390 px;
7. rollback, archive, revoke, or retention behavior appropriate to the record;
8. source-of-truth reconciliation when legacy, portal, WordPress, or Woo data meet.

## Harry as the administrative teacher

Harry now provides a contextual backstage briefing for every Admin Portal
section. Each lesson explains the job, a three-step operating checklist, and an
honest system boundary. The teaching curriculum covers:

- triaging exceptions rather than trusting dashboard counts blindly;
- reconciling a classmate across RSVP, dinner, portal, messages, and tickets;
- protecting dietary, accessibility, yearbook, and memorial information;
- answering a person before promoting an answer into Harry's knowledge base;
- consent and rights checks before publishing memories;
- response ownership and transactional-versus-promotional messaging;
- reservation versus paid-order versus valid-ticket truth;
- component ownership, preview, accessibility, and workflow accountability;
- least-privilege role assignment and revocation;
- delivery status, worker health, retries, suppression, and dead letters;
- data authority, reconciliation, migration, audit, and rollback.

This is a working embedded operations academy with twelve contextual lessons.
The next maturity step is to make lessons completion-aware: link each lesson to its real task,
track acknowledged/onboarding state per staff member, surface role-specific
practice scenarios, and require owner approval for high-risk actions.
