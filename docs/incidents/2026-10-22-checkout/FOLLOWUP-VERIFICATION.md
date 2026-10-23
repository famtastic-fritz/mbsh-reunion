# Follow-up verification — October 22, 2026

Read-only contact reconciliation found six real current-mixer contacts with
names and email addresses, five with phones, across seven saved requests:
October 19 (three), October 20 (three), October 22 (one). Three unique contacts
have attempts in the October 20–22 incident window. Three diagnostic request
rows were excluded. No contact list was exported or published.

All seven requests correlate to Woo attempts. Five contacts have current-offer
Woo orders marked paid/processing with Stripe transaction IDs, paid dates and
captured flags; one contact has no completed current-offer purchase. This is not
inferred from legacy `ticket_orders.payment_status`, and it is not a list of all
HTTP-rejection victims. HTTP rejects happened before saving contact details.

Fritz authorized continued safe testing. A fresh disposable WordPress 7.0.4 /
WooCommerce 11.0.1 run passed the signed AJAX bridge, retry/deduplication, $60
two-admission order, simulated payment event, exactly-once ticket issuance,
partial/full local refund revocation, failed-order isolation, and stale/extra-fee
guards. The relevant commerce, ticket and mailer sources match deployed `9d93db8`.

Extended receipt tests now run the real reunion mail adapter against a local
mock, with an internal Docker network and no provider credentials. They verify
no paid notice for an unpaid order, paid-notice queuing, correct synthetic
recipient/current-event content, recorded simulated provider failure, retry
using the same idempotency key, success/error-state cleanup, and suppression of
duplicate receipt sends. All passed. Only `example.test` recipients are accepted
by the mock; SMTP and external HTTP are blocked. Disposable containers were
removed after the run. No production financial or mail action was used to test.

The production gateway remains live and unchanged. Its test publishable key,
test secret key and test webhook secret are all absent. No MBSH-specific sandbox
credential was found in the configured local vault index. An unrelated site's
test credential was not used. Real Stripe test-card success, decline, 3DS,
webhook delivery and actual receipt inbox delivery remain unverified.

Classification for this follow-up: `candidate` (test/evidence source only).
The earlier incident remains `deployed`. Owner purchase acceptance is pending.
Any individually authorized repair notice is a separate outbound action with
private recipient/content/audit records, not a test email or payment proof.
