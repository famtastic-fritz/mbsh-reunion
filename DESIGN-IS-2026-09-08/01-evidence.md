# Evidence

## Audit limits

The attendee portal was run locally in demo mode and its visible task links were physically clicked. Production attendee, committee, and WordPress routes were also opened, but protected production screens stopped at their login boundaries. The repository demo fixture does not grant a staff identity, so the protected Committee Portal and WordPress screens still require an authenticated browser pass.

Production attendee JavaScript is byte-identical to the repository version. Production Committee Portal HTML and JavaScript are not: the live deployment contains dinner, Stripe-verification, and sample-ticket additions absent from this checkout.

## 1. Innovative

- One verified identity can expose an attendee workspace and capability-filtered committee administration (`docs/architecture/PORTAL_STAFF_AUTHORIZATION_2026-08-16.md:5-15`).
- The committee experience combines operational records with contextual lessons and ticket tools (`frontend/portal/committee/index.html:3-17`).
- WordPress "Ask Harry" is a curated regex router rather than a model-backed assistant (`wordpress/wp-content/plugins/famtastic-reunion-platform/assets/admin.js:16-32`).

## 2. Useful

- Five visible attendee actions were physically clicked successfully: dinner, ticket wallet, memory upload, suggestion, and event guide (`frontend/portal/index.html:80,83,85,86,88`).
- The primary Menu button does not open the drawer. The code binds the first `.menu-button`, which is Manual, rather than the actual Menu button (`frontend/portal/index.html:28-30`; `frontend/portal/js/portal.js:10-12`).
- The Messages view exists but has no attendee navigation link (`frontend/portal/index.html:39-48,154-157`).
- Committee dashboard metrics describe destinations but are inert `<article>` elements (`frontend/portal/js/committee-workspace.js:2,5`).

## 3. Aesthetic

- The portal uses a coherent dark burgundy, cream, gold, and red cinematic system with Playfair Display headings and consistent rounded panels (`frontend/portal/css/portal.css:1-2`; `frontend/portal/css/committee.css:1-5`).
- One permission control declared `hidden` renders visibly because `.mode-switch{display:grid}` overrides the user-agent hidden rule (`frontend/portal/index.html:27`; `frontend/portal/css/portal-staff.css:2-3`).
- At 390 x 844, the attendee document overflows horizontally by 48 px: the four header actions total 423 px against a 375 px client width, pushing Menu partly off-screen.
- The mobile Harry guide overlays ticket-card content, and the production committee tour overlay covers both navigation and command-center content.
- The attendee render used 20 colors and 14 observed type sizes; committee styles reference 42 distinct colors.
- Authenticated production committee and WordPress surfaces were not available for a complete computed-style pass.

## 4. Understandable

- Task labels such as People & RSVP, Dinner & dietary, and Tickets & check-in are concrete (`frontend/portal/committee/index.html:3`).
- The portal also uses operator jargon such as data authority, entitlements, dead-letter, reconciliation, outbox, and heartbeat (`frontend/portal/committee/index.html:4,16-17`; `frontend/portal/js/committee-workspace.js:1,17-18`).
- Attendee and staff inboxes share `data-view-panel="inbox"`, so one route name represents two different surfaces (`frontend/portal/index.html:154-157,182-185`; `frontend/portal/js/portal.js:13-14`).
- Production has two sibling `data-panel="tickets"` sections for one Tickets navigation choice (read-only fetch of `https://mbsh96reunion.com/portal/admin/`, 2026-09-08).

## 5. Unobtrusive

- The attendee screen presents Manual, Guide, Menu, a persistent Harry tip, and—because of the hidden-state defect—a Committee Desk switch (`frontend/portal/index.html:27-30,200-204`).
- The committee surface exposes two authored tour launchers and the shared navigator injects another launcher (`frontend/portal/committee/index.html:2,5`; `frontend/js/reunion-navigator.js:96-141`).
- WordPress injects Ask Harry on every authorized admin screen (`wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php:140-151`).

## 6. Honest

- "Your digital ticket is ready" and "Verified attendee" render before ticket data is known. The no-ticket handler replaces only `[data-ticket-data]`, leaving both claims visible (`frontend/portal/index.html:83,92-101`; `frontend/portal/js/portal.js:17`).
- "Saved to your profile" and "All changes saved" render before the RSVP API succeeds; a failed API request leaves those claims in place (`frontend/portal/index.html:107`; `frontend/portal/js/portal.js:25-26`).
- "Text me event updates" stores a preference, but no SMS sender/worker was found (`frontend/portal/index.html:147`; `frontend/portal/js/portal.js:39`; `backend/portal/preferences.php:5-8`).
- "Connected to Resend" means only that configuration exists, not that a provider health or delivery test passed (`wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-integrations.php:141`).
- No fake scarcity, hidden cost, confirmshaming, or disguised paid action was found.

## 7. Long-lasting

- The cinematic brand system can outlive the event phase, but the WordPress dashboard hard-codes "Tonight's Command Center" and "The countdown is active" before the event date (`wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php:79-86,128-132`).
- Event date and Act-specific language are embedded directly in views rather than phase data (`frontend/portal/index.html:88,138-141`).
- A legacy owner page hard-codes localhost destinations (`frontend/portal/owner/index.html:5,7,10`).

## 8. Thorough

- Attendee code includes loading, empty, error, success/toast, focus, disabled, reduced-motion, and retry-oriented states (`frontend/portal/js/portal.js:6-9,17-18,21-29,31-41`; `frontend/portal/css/portal.css:2`).
- The isolated backend integration suite passed registration, authorization, cross-account denial, duplicate/replay protection, tickets/check-in, and email retry/dead-letter coverage.
- The frontend contract test checks source markers, not click behavior, and therefore passes while Menu is broken (`tests/frontend/portal-contract.test.mjs:20-52`).
- Production committee payment UI is absent from the repository, so local tests do not cover it.
- The deployed payment handler correctly enforces quantity-based minimum amount and rejects duplicate payment references; the live form nonetheless defaults to $191 and the Verify shortcut does not populate quantity-based amount (read-only production source, `/home/nineoo/public_html/portal/committee/action.php:55-101` and `https://mbsh96reunion.com/portal/js/ticket-payment-desk.js:6-29`).
- Primary text contrast passed WCAG AA in measured attendee and WordPress-login states; the lowest sampled ratio was 5.09:1.
- The mobile Harry dismiss control is about 25 x 25 px and the Guide me control is 101 x 40 px, both below a 44 x 44 px touch target. The Committee Portal has no skip link.

## 9. Environmentally friendly

- Attendee initial JavaScript is 58,648 raw bytes across six scripts; committee initial JavaScript is 54,588 raw bytes across seven scripts.
- The settled attendee screen has no persistent idle animation; one 300 ms entrance animation is finite.
- Reduced-motion is respected (`frontend/portal/css/portal.css:2`; `frontend/js/reunion-navigator.js:96-141`).
- Attendee boot makes session plus seven parallel API calls before a secondary view is opened (`frontend/portal/js/portal.js:26`).

## 10. As little design as possible

- The authored attendee DOM contains 73 interactive elements; 58 remain for an ordinary attendee. The local committee DOM contains 33, and the current production committee HTML contains 42 before generated records.
- Nine attendee tasks are repeated between navigation and in-page CTAs (`frontend/portal/index.html:39-48,75-157`).
- The committee offers three tour entry points for one purpose (`frontend/portal/committee/index.html:2,5`; `frontend/js/reunion-navigator.js:96-141`).
- Five WordPress dashboard shortcuts repeat destinations already available in the admin menu (`wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php:121-136`).

## Measured operating evidence

| Surface | Initial JS | Requests | Navigation-to-load bound | Idle animation | Result |
|---|---:|---:|---:|---:|---|
| Local attendee demo | 58,648 B | 21 observed | 450 ms | 0 | Five visible CTAs worked; Menu failed |
| Production login | 31,929 B local-source estimate | 10 observed | 4,640 ms | 0 | Login boundary rendered |
| WordPress login boundary | unavailable | 24 observed | 555 ms | 0 | Correctly redirected to `/cms/wp-login.php` |

At 390 px, attendee focus/DOM order is Skip, Brand, leaked Committee Desk, Manual, Guide, Menu, five dashboard tasks, Harry dismiss, and Guide me. Menu is keyboard focusable but does not function. The attendee portal has banner, navigation, main, and complementary landmarks plus a skip link; Committee and WordPress-login surfaces have no skip link.

## Test results

- `node tests/frontend/portal-contract.test.mjs` — PASS
- `node tests/frontend/reunion-navigator-contract.test.mjs` — PASS
- `bash tests/backend/run.sh` — PASS
- `bash tests/backend/integration.sh` — PASS in an isolated Docker proof environment

These passes prove backend primitives and source contracts, not the protected production UI or parity between the repository and the deployed Committee Portal.
