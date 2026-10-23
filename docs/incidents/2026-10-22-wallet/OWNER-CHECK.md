# Owner and attendee verification

Release: see the adjacent deployment receipt. Acceptance remains **pending** until a real person completes these steps independently. Local synthetic proof is separate.

1. Open https://mbsh96reunion.com/portal/ and sign in with the email used at checkout. A purchaser without an account uses the normal Create Attendee Account flow and verifies the message sent to that same email. No WordPress account is needed.
2. Open **My mixer pass / Open wallet**. Confirm **Your admissions** shows the purchased quantity. Select each admission; check holder, current mixer label and status. An active pass shows its private rotating code.
3. Keep the live code private. On a connection interruption it disappears until the wallet refresh succeeds. A checked-in or revoked pass must not show an active code.
4. If no ticket appears, verify the account email matches the paid order and ask the committee to check WooCommerce. Do not manually mark the old request paid or issue an extra ticket.

Owner task `wallet-retrieve-20261022` maps to `mbsh.portal.commerce-wallet`: mobile browser, verified purchaser account, actual paid current-offer order; expected result is the correct number of current admissions. Viewing does not mutate payment/ticket state. Real owner/attendee observation: pending.

The automatic reconciliation worker can recover interrupted missing issuance, but it cannot charge a customer, override a refund, verify an account, or replace a deliberately revoked pass. Real Stripe sandbox decline/3DS and unattended future retry scheduling remain separate from the actual captures and isolated integration proof.
