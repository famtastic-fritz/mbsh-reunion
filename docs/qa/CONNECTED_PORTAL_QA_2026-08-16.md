# Connected portal QA — 2026-08-16

## Verdict

The local proof is now a connected permission-driven system, not a decorative
dashboard. Attendee and Admin Portal boundaries are enforced by the same
verified attendee session plus revocable staff membership. The full WordPress
dashboard is a separate Site Owner-only maintenance view.

## Proven journeys

| Journey | Evidence | Result |
|---|---|---|
| Site Owner login | `fritz.medine@gmail.com` local proof identity | Pass |
| Attendee RSVP/dinner load | `GET /portal/my-event.php` | Pass |
| Attendee RSVP/dinner save | CSRF-protected `PATCH`; canonical response returned | Pass |
| Committee opens attendee record | Current portal response and linked read-only dinner history shown | Pass |
| Attendee creates message | Conversation, first message, three-day response deadline | Pass |
| Committee opens timeline | Participant, status, message, delivery state | Pass |
| Committee replies | Audit record plus Resend outbox job | Pass |
| Attendee opens/replies/closes/reopens thread | Owner-scoped timeline and state transitions | Pass |
| Cross-account conversation access | Different attendee receives 404 | Pass |
| Attendee submission correction | Media/suggestion update, close/withdraw, resubmit | Pass |
| Ticket exception lifecycle | Owner issue, rotating credential, check-in/void, replay conflicts | Pass |
| Owner self-lockout prevention | Account suspension and membership demotion both return 409 | Pass |
| Owner sees WordPress registry | 12 actual Page Component records | Pass |
| Committee sees WordPress content | Actual CMS records; no mock rows | Pass |
| Owner manages roles | Server-authorized, CSRF-protected, audited | Pass |
| Ordinary attendee opens committee/owner routes | Redirected away; staff APIs deny access | Pass |
| Unified Admin Portal | `/portal/admin/`; capability-filtered common tools plus owner-only controls | Pass |
| Legacy staff routes | `/portal/committee` and `/portal/owner` redirect into the unified Admin Portal | Pass |
| Direct hidden-panel navigation | Ungranted hash routes return the user to command center | Pass |
| Full WordPress boundary | Committee users redirect to Admin Portal; Site Owner retains `/wp-admin/` | Pass |
| Admin Portal Harry guide | Context lessons, free-text question, authoritative workspace routing | Pass |
| WordPress Harry guide | Persistent owner guide and screen-aware operating instructions | Static/runtime contract pass; owner visual session still required |
| Responsive attendee/committee/owner | 390 × 844; document width 375, no overflow | Pass |
| WordPress workers | Two recurring `famtastic-reunion` Action Scheduler jobs | Pass |
| PHP/JS/static contracts | PHP lint, Node syntax, portal contract, diff check | Pass |

Latest unified Admin Portal browser proof: Site Owner sees the common reunion
operations plus owner-only areas and the **Full Site Admin** link. At a
390 × 844 viewport, attendee and admin documents measured 375 CSS pixels wide
with no horizontal overflow. Harry's contextual lesson was visible on mobile;
People loaded 13 connected records, Messages loaded 9 threads, lesson copy
changed with the selected workspace, and browser logs contained no warnings or
errors. Desktop at 1440 × 900 also had no horizontal overflow.

### Backend Harry clarification

The first QA pass proved a contextual lesson card in the branded Admin Portal;
it did not prove an interactive guide in WordPress. That gap is now corrected.
The Admin Portal accepts an operational question and routes the user to People,
Dinner, Harry, Media, Messages, Tickets, Content, Access, Delivery, or Audit.
WordPress now includes a persistent **Ask Harry** guide whose guidance changes
for memories, FAQs, tickets, WooCommerce orders, and editorial screens. Both
guides are curated operational assistance—not an unrestricted model that can
invent policy or bypass capabilities.

## Data truth

- Production snapshot: read-only. Current sample contains 10 RSVPs, 21 dinner
  selections, 88 historical surveys, and 8 Harry questions.
- Portal authority: accounts, current event responses, conversations, media,
  preferences, notification records, staff memberships, and staff audit.
- WordPress authority: page components, events, FAQs/Harry knowledge,
  announcements, sponsors, approved memories, and tributes.
- WooCommerce authority: products, orders, payments, refunds, and paid ticket
  issuance. No real payment was attempted.

## Failure and security checks

- Unauthenticated and ordinary-attendee staff access fails closed.
- Portal writes require same-origin session cookies and CSRF.
- Snapshot connection is read-only and attendee writes cannot mutate it.
- Staff record views and mutations are audited.
- Email replies are queued; provider delivery uses bounded retries and a dead
  state. This local proof does not claim a live Resend delivery.
- Admission credentials remain opaque, signed, rotating, and owner-scoped.

## Production gates intentionally left closed

1. Reconcile every real production person to a verified portal identity.
2. Run Stripe test success, decline, 3DS, refund, and webhook-replay evidence.
3. Install the real Resend secret and execute a controlled allowlisted delivery.
4. Approve customer-facing ticket/refund/privacy/archive consent language.
5. Back up, migrate, rehearse rollback, and obtain explicit deployment approval.

These are deployment gates, not missing local UI. The local platform is ready
for owner/committee evaluation and staged migration.
