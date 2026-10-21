# Stripe and WooCommerce connection handoff

Status: production prepared, checkout disabled
Act: Act I - The Preview
Scene: Open the reunion box office

## Prepared by FAMtastic Designs

- WooCommerce 11.0.1 and the official WooCommerce Stripe gateway are installed under `/cms`.
- The FAMtastic Reunion Platform 0.5.0 connects paid Woo orders to the existing reunion request and attendee wallet.
- Hidden Woo product `MBSH96-30-EARLY` is product ID 26 at $190.67 per admission:
  - $185.00 Early Bird admission
  - $5.67 committee-approved buyer processing fee
- Checkout carries the existing `MBSH-XXXXXX` request reference without placing email, bank, or card data in the URL.
- Paid orders are matched by order reference, email, and quantity before a ticket can be issued.
- Repeated paid-order events are idempotent. Refund/cancellation hooks revoke the applicable admission.
- The existing private portal remains the attendee wallet. No Stripe secret or card data enters its tables.
- The public checkout handoff is deployed behind `ticket_checkout_enabled=false`.
- Stripe remains disabled and in test mode. No live payment is accepted by this preparation.

## Committee connection steps

1. Fritz signs into Full Site Admin and opens:
   `https://mbsh96reunion.com/cms/wp-admin/admin.php?page=wc-settings&tab=checkout&section=stripe`
2. Leave the gateway in test mode.
3. Click **Connect with Stripe**.
4. The authorized committee Stripe owner signs into the existing Stripe account and approves the WooCommerce connection.
5. Return to WooCommerce and confirm the Stripe account shows connected. Do not paste API keys, webhook secrets, banking credentials, card data, or verification codes into email, text, or the reunion site.
6. Tell Fritz the connection is complete. Do not enable live mode and do not publish a checkout link yet.

The official gateway creates and verifies its Stripe webhook connection. The committee does not need to build a webhook or copy its signing secret to FAMtastic.

## FAMtastic test gate after connection

Before live checkout is enabled, record evidence for successful test payment, decline, 3DS success/failure/abandonment, webhook replay, exact ticket quantity, branded delivery, full/partial refund, revocation, mobile checkout, and one-time check-in. Only after those pass may the production configuration set the product ID and enable the public checkout handoff.

## Still awaiting committee decisions

- Confirm the refund and ticket-transfer policy.
- Confirm whether the future $200 regular ticket also passes its calculated processing fee to the buyer. The $190.67 product covers only the approved Early Bird price.
