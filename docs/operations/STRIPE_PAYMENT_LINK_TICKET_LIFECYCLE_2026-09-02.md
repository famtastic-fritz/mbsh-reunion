# Stripe Payment Link Ticket Lifecycle

## Story authority

- Act: Act I — The Preview
- Scene: Official reunion box office
- Production authority: the Stripe account is authoritative for payment; the reunion database is authoritative for request, ticket, wallet, notification, and check-in state.

## Public flow

1. The buyer submits one reunion ticket request.
2. The site creates an `MBSH-XXXXXX` order before showing payment.
3. The embedded Stripe Payment Link receives that value as its client reference ID and receives the order email when the button supports it.
4. A fallback payment page and QR code are available. QR buyers must use the same email as the reunion request because the generic QR cannot carry a unique order reference.
5. Stripe collects the card data and sends its payment receipt. The reunion request remains `pending` until a successful Stripe payment is verified.

## Owner verification and fulfillment

1. Open **Committee Portal → Tickets & check-in**.
2. Select a pending payment-link order.
3. Open Stripe in a separate authenticated tab and confirm:
   - payment succeeded;
   - payer email matches the reunion order;
   - the Payment Link item is the MBSH Early Bird admission;
   - the amount is at least the current $191 subtotal, including any tax Stripe calculated;
   - the Stripe payment, charge, or checkout reference has not been used before.
4. Enter the Stripe reference and amount, check the verification statement, and confirm.
5. The system records an audited paid state and sends the branded payment-confirmation email.
6. If a verified attendee account exists for the same email, the system issues the wallet ticket, adds a notification, and sends the branded ticket-ready email.
7. If the account is not verified, the paid order waits. Verifying that same portal email triggers fulfillment automatically.

## Safety rules

- A redirect, buyer screenshot, email receipt, or ticket request alone is not payment authority.
- Never issue a valid ticket while `payment_status` is `pending`.
- Never accept bank, card, or Stripe credentials through the reunion site.
- The manual exception-ticket form is not a substitute for verifying Payment Link sales.
- Repeating the same verified confirmation is idempotent; a Stripe reference cannot be attached to two orders.

## Current limitations and future automation

The supplied Payment Link and publishable key cannot securely prove successful payment to the server. Reliable unattended fulfillment requires a Stripe webhook endpoint and its signing secret. When the committee can provide Stripe Dashboard access, create an event destination for successful checkout/payment events and reuse the same fulfillment service in `backend/lib/ticket-payments.php`.

The current Payment Link processes one admission per checkout and displays a $191 subtotal. Stripe presently asks for an address to calculate any applicable tax. Those Stripe settings must be changed in Stripe Dashboard, not inferred or overridden by reunion-site code.
