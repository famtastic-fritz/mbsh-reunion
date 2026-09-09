# Verdict: REDESIGN

The portals need a workflow and information-architecture redesign—not a visual restart—because the audit scored 12/30 and the load-bearing honesty principle scored 0: authoritative operational state must replace static promises before the cinematic styling can be trusted.

## Highest-leverage moves

1. **Principle #6 — Honest:** derive every ticket, RSVP, payment, delivery, and connection label from an authoritative response; default to loading/unknown, never success. Evidence: `frontend/portal/index.html:83,92,107` and `frontend/portal/js/portal.js:17,25-26`.
2. **Principles #2/#4 — Useful and understandable:** rebuild navigation around unique route IDs and explicit targets, then browser-test every link for each role. Evidence: `frontend/portal/index.html:28-30,154-157,182-185` and `frontend/portal/js/portal.js:10-14`.
3. **Principle #8 — Thorough:** reconcile the deployed Committee Portal into version control and add interaction tests for production-only dinner, payment verification, sample tickets, quantity totals, failures, retries, and permissions. Evidence: production/repo hash mismatch and `tests/frontend/portal-contract.test.mjs:20-52`.
4. **Principles #2/#10 — Useful and restrained:** make command metrics actionable and consolidate three tours, duplicate ticket panels, and repeated shortcuts into one contextual help pattern. Evidence: `frontend/portal/js/committee-workspace.js:2,5`, `frontend/portal/committee/index.html:2,5`, and `frontend/js/reunion-navigator.js:96-141`.
5. **Principles #4/#7 — Understandable and long-lasting:** replace operator jargon and hard-coded event-phase language with plain labels and Act-aware configuration. Evidence: `frontend/portal/committee/index.html:4,16-17` and `wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php:79-86,128-132`.

## Preserve

- The burgundy, cream, gold, and red Event Cinema visual tokens.
- The secure, server-enforced role/capability architecture.
- WooCommerce as financial authority and the custom portal as the attendee/committee experience.
- Existing loading, empty, error, success, focus, disabled, reduced-motion, and backend retry/idempotency primitives.
