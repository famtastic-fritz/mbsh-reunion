# Act I Email Catalog and Initial Schedule

Status: planning baseline; no bulk campaign is authorized by this document.

| Email | Audience | Trigger or target timing | Primary job | Harry treatment |
|---|---|---|---|---|
| Welcome home | Newly verified attendee | Immediately after verification | Introduce My Reunion and profile/preferences | Waving |
| Committee member welcome | New committee member | After access is granted | Explain Committee Desk duties and boundaries | Presenting |
| Committee lead briefing | New committee lead | After lead access is granted | Explain expanded operations and Site Owner boundary | Salute |
| RSVP call | Verified attendees without submitted RSVP | Initial campaign, then a restrained reminder | Complete reunion response | Clipboard |
| Ticket status/update | Relevant attendees | When payment process or order status materially changes | Give accurate next step | Ticket stub |
| Dinner selection call | Attending guests without a completed meal response | After RSVP attendance is established | Choose entree and disclose dietary needs | Presenting |
| Venue and night preview | Event-update subscribers | Milestone announcement | Prepare guests for November 7 | Usher |
| Memory call | Memory-update subscribers | Campaign milestone | Invite approved memories/photos | Wax stamp or presenting |
| Countdown reminder | Event-update subscribers | Committee-approved milestones | Reinforce one timely next step | Pointing or running |
| Final details | Attending guests | Committee-approved final window | Deliver schedule, arrival, attire, and support details | Salute |

Promotional campaigns require explicit `promotional_email` consent. Account,
security, payment, ticket, and essential event-service messages remain separate.
Every campaign requires recipient preview, test send, committee approval,
scheduled/send confirmation, delivery history, and an opt-out path appropriate
to its classification.

## Ticket purchase lifecycle

These are transactional service messages driven by authoritative order state;
they are not disabled by the optional marketing preference.

| Trigger | Email | Admission meaning |
| --- | --- | --- |
| Legacy order request recorded | We received your ticket request | Payment is not confirmed; no admission |
| WooCommerce reports paid | Payment confirmed - welcome home | Payment receipt; ticket issuance follows |
| Ticket issuance succeeds | Your reunion ticket is ready | Live ticket is available only in the protected wallet |
| Payment fails | Your reunion payment needs attention | No ticket is issued |
| Order is cancelled | Your reunion ticket order was cancelled | No admission |
| Refund is recorded | Your reunion ticket refund was recorded | Related ticket quantity is revoked |

The branded HTML is implemented in `backend/lib/portal-email-templates.php`.
It renders only; live WooCommerce sending remains disabled until payment
configuration is reviewed, order-state mapping is verified, and owner-approved
test orders pass end-to-end QA.

## Sample Ticket Studio

The Admin Portal ticket workspace can create a printable committee preview.
Every preview is visibly marked `SAMPLE - NOT VALID FOR ENTRY`; it has no QR
code or credential, creates no database record, and cannot enter check-in.
