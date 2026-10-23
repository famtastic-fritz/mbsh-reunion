# Checkout entry repair — October 22, 2026

Classification: `deployed`. The reported ticket-submission failure is repaired.
This record does **not** claim a completed payment, receipt delivery, ticket
issuance, or owner acceptance.

## Cause and impact

The public HTTP site rendered the ticket form without upgrading the visitor to
HTTPS. Its same-origin POST supplied `Origin: http://mbsh96reunion.com`; the
existing HTTPS-only origin allowlist correctly returned HTTP 403 with
`{"error":"origin_not_allowed","origin":"http:\/\/mbsh96reunion.com"}`.
Because that response has no `message`, `frontend/js/ticket-order.js` displayed
“Your order could not be submitted. Please try again or email
committee@mbsh96reunion.com.” This happens **before** the request ledger, Woo cart,
or payment provider. The allowlist was not widened.

The public certificate also expired at **October 20, 2026, 9:41:31 PM Eastern**
(`2026-10-21T01:41:31Z`). Independent local and server TLS clients rejected it.
The MBSH apex domain had no entry in the server's existing ACME renewal
configuration. The certificate problem is additional to the missing redirect;
the first logged HTTP failures precede certificate expiry.

Sanitized access-log counts: 6 HTTP ticket POST 403s on October 20, 11 on
October 21, and 18 on October 22 before our diagnostic probe. These are
**35 requests, not 35 unique people**. Today's 18 carried an HTTP MBSH referrer.
No corresponding ticket database exception appeared in the public PHP log.
The migrated `mixer` price tier and checkout-signing configuration were intact.

## Repair and exact deployed scope

- Source repair: `7a236385d84f5ff1fa5b149b5fd7524f817122aa`, on
  `codex/mbsh-checkout-incident-20261022`, based on remote main `cb190b2`.
- Live application base remains `9d93db84796c6069833d9240f05330dadd1ca714`.
  **Only `.htaccess` changed in the public release.** The reconciled source-only
  additions on main were not deployed. `release.json` continues to identify the
  base; this receipt identifies the incident overlay.
- `frontend/.htaccess` now redirects HTTP apex/www and HTTPS www to
  `https://mbsh96reunion.com` with 308. It retains the path/query, preserves
  HTTP-01 challenges, and leaves other shared-root hosts alone.
- Before SHA-256: `8b0c2da72a2b7be83369be4c5e4f0be232eb57c5bd6705050971c761658fc62d`.
  After SHA-256: `17d4758c1dc121a28570fdb4ddefff07d230045941a0b913cc0e6eb5014aa501`.
- The new Let's Encrypt certificate covers apex and www, expires
  `2027-01-20T22:53:05Z`, and has SHA-256 fingerprint
  `A3:17:F9:DE:60:0E:A1:8D:E2:FC:37:AB:6C:E9:64:90:94:94:76:EB:E1:EA:89:FD:39:D1:88:BB:E8:F8:CF:D6`.
- The existing daily server ACME job now includes this domain. Its saved
  installer is `scripts/install-mbsh-tls.py`, copied outside the webroot to
  `/home/nineoo/.config/mbsh-tls/`. The existing cPanel credential was provisioned
  privately (directory 0700, credential/key 0600). The hook accepts only the two
  reunion certificate names and installs only the reunion vhost. Installation
  through authenticated, certificate-validated HTTPS UAPI succeeded. The local
  jailed-shell UAPI CLI was unavailable, so it is not used by this hook.
- Renewal is currently scheduled by ACME for `2026-12-21T19:18:48Z`; the hook
  executed successfully during installation. A future unattended run cannot yet
  be observed. No other cron entry was changed.

The date, venue, $30 final admission price, product 26, offer
`mbsh-mixer-2026-11-07-v1`, USD currency, and tax status `none` were verified
unchanged. WooCommerce remains financial authority. Legacy standalone payment
link cleanup was not part of this repair.

## Verification

Passed locally:

- Real disposable Apache HTTP/HTTPS integration using the actual `.htaccess`:
  apex/www entry, query retention, no canonical redirect loop, HTTP-01 access,
  shared-host isolation, HTTPS POST/retry, and foreign-origin denial.
- Disposable MariaDB/PHP mixer integration: preserved historical rows, server
  $30/$60 totals, retry deduplication, client-price tampering, invalid quantity,
  closed meal route, and no paid records.
- PHP security/offer/email-rendering checks, commerce contract, mixer content,
  public shell, creator-credit sync, WordPress static checks, and whitespace.
  Offer checks include purchaser/quantity tampering, stale/expired tokens and
  fee/total mismatch. Email rendering is offline; no email was sent.
- Independent fresh-clone repository identity validation. This managed worktree
  still triggers the inherited `common_directory_mismatch` because its older
  primary checkout lacks the matching manifest. The validator was not weakened
  and unrelated checkouts were not changed.

Verified live on October 22:

- Both TLS hostnames validate without bypasses. HTTP entry and HTTPS www return
  308 to secure apex. `tickets.html` then returns 200.
- An invalid ticket request returns the expected 400 validation response instead
  of the transport-origin rejection.
- At 390 × 844 with reduced motion, empty submission focuses the required name;
  Tab reaches email; changing quantity to two shows $60.
- One clearly labeled synthetic contact at `example.test` submitted the public
  form. Access logs show `/ticket-order.php` **200** at `23:56:40Z` and the Woo
  cart adapter **200** at `23:56:41Z`. The browser reached secure checkout and
  showed two $30 admissions, total $60. At 1280 × 900 the same total remained
  visible, no visible alert was present, and the document fit the viewport.
- A retry at `23:58:16Z` returned **200**, `replayed: true`, and the same
  diagnostic request. Exactly one request row remains, status `pending`.
  Its Woo attempt is `attempt`, `woo_order_id: null`; fixture email-job count is
  zero. The unpaid attempt is retained for audit and is not an admission.
- All files in the original live release manifest still match except the
  deliberately changed `.htaccess`. SHA-256 of the deployed file matches source.
- GitHub Actions remains disabled by owner policy. All checks ran locally.

See [sanitized retry response](retry-proof.json), [structured receipt](receipt.json),
and [mobile summary](mobile-checkout-summary.jpg). The image is verified JPEG,
390 × 580, a crop of a 390 × 844 viewport; payment/account details are excluded.
Its SHA-256 is `41fe9aba357b5b0b088f693ef523bc609abbd3a5b457e478d8b971712e23259b`.

Test-environment failures were corrected before deployment: Docker could not
bind the host temporary path, so the Apache fixture copies its inputs into the
container; a duplicate SSL listen declaration was removed. A read-only WP-CLI
check exceeded its default 128 MB; rerunning that process with 512 MB succeeded.
No production PHP memory setting changed.

## Capability evidence and owner acceptance

| Field | `mbsh.mixer.purchase` |
| --- | --- |
| Customer job / role | Attendee selects mixer quantity and reaches secure checkout. |
| Entry / action / input | HTTP or HTTPS ticket URL; required contact fields; quantity 1–10; Continue to Secure Payment. |
| Saved result | One private request-ledger row and Woo attempt/cart; no financial order attached to our diagnostic attempt. |
| Visibility / reversal | Public entry, private cart/request. No admission until Woo payment confirms. Close test checkout; retain diagnostic record for audit. |
| Evidence / revision | `hosted_verified` for entry → request → cart → $60 checkout; `local_tested` for failure/tamper/retry cases. Base `9d93db8` plus repair `7a23638`, dated above. |
| Provider state | `connected_unverified` for a completed transaction. Secure Stripe interface loaded; no card entered or payment action taken. |
| Owner acceptance | `pending`; no uncoached owner purchase observed. |
| Consumption | `not_applicable`; site-local repair, no shared skill or Studio import. |
| Gap / next owner | Fritz decides whether to authorize a separately controlled payment-through-ticket/receipt verification. |

Owner-task record: `mbsh.mixer.purchase.owner-1`, role owner/attendee, route
`https://mbsh96reunion.com/tickets.html#ticket-order`, device not observed.
Expected task: fresh-load secure entry, choose quantity, verify $30 each, reach
checkout; actual payment and wallet/receipt outcomes require separately
authorized verification. Result `pending`, release base plus repair as above.
No owner/admin controls or guide instructions changed; no new training guide is
required for this transport repair.

## Recovery and next decision

Private backup, raw logs, ACME/install logs and deployment timestamp:
`/home/nineoo/.releases/mbsh-reunion/incidents/20261022-checkout/` (0700).
The original `.htaccess` is `htaccess.before`; compare the expected current hash
before any rollback. Restoring that file would reintroduce the HTTP failure.
Keep the valid certificate installed; do not roll back to the expired one.

Affected visitors should close or refresh the old HTTP tab and open the secure
ticket link. An already-open HTTP page can retain its old origin after a POST
redirect; the tested recovery is a fresh secure page load.

No real charge, refund, Place Order action, test email, receipt, ticket issuance,
or owner acceptance is claimed. Full payment/receipt/entitlement testing remains
the next owner decision, not a reason to leave the confirmed entry bug unfixed.
The technical reference for the install hook is the
[official cPanel SSL installation API](https://api.docs.cpanel.net/guides/quickstart-development-guide/tutorial-call-uapis-ssl-install-ssl-function-in-custom-code).
