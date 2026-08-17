# WordPress Architecture QA — 2026-08-17

## Verdict

The correct architecture is a governed hybrid. WordPress is the owner/committee content and growth workspace; it is not the attendee identity system and it is not the public rendering layer. WooCommerce remains the only financial authority. The attendee portal remains the authority for accounts, verification, preferences, private media, messages, RSVP state, and ticket wallet access.

## Installed operations stack

| Capability | Tool | Authority and boundary |
|---|---|---|
| Permission-based CRM and campaigns | FluentCRM | Receives only verified portal accounts with explicit promotional opt-in. It never infers consent from RSVP, purchase, registration, or transactional preferences. |
| Public forms, polls, volunteer interest, and surveys | Fluent Forms | May create committee-managed public forms. It must not replace account registration, portal preferences, private media intake, Woo checkout, or ticket issuance. |
| WordPress transactional delivery | WP Mail SMTP using the native Resend mailer | Uses the existing reunion Resend API key from the protected server configuration. Secrets are not stored in Git. |
| Scheduled-work inspection | WP Crontrol | Owner-only visibility into WordPress cron. System cron remains the reliable trigger because built-in visitor-driven WP-Cron is disabled. |
| Owner authentication hardening | Two-Factor | Available to the site owner for TOTP/backup-code enrollment. Not forced until recovery codes are saved and login is proven. |
| Products, orders, refunds, ticket fulfillment | WooCommerce + Stripe extension | Financial source of truth; gateway remains gated until its acceptance matrix passes. |

## Data ownership

1. Public frontend: cinematic layout, accessibility, page routing, search metadata, structured data, and public media rendering.
2. WordPress: event content, program pages, FAQs/Harry knowledge, sponsors, approved archive collections, public forms, and campaigns.
3. Attendee portal: identity, verification, sessions, RSVP/menu state, private submissions, support conversations, preferences, roles, notifications, and wallet.
4. WooCommerce: catalog, checkout, payment state, refunds, and payment-backed ticket issuance.
5. Resend: email delivery only. Transactional and promotional classifications remain separate.

## QA findings and corrections

- **Fixed:** no CRM or campaign manager existed. FluentCRM is installed with a narrow consent bridge.
- **Fixed:** WordPress mail used the host's default `mail()` transport. WP Mail SMTP now uses the protected reunion Resend credential.
- **Fixed:** no committee-friendly general form builder existed. Fluent Forms is installed with explicit exclusions for authoritative portal/payment workflows.
- **Fixed:** scheduled jobs existed but had no inspection UI. WP Crontrol is available to the owner.
- **Fixed:** WordPress owner login had no second-factor facility. Two-Factor is installed; enrollment remains an owner action to prevent lockout.
- **Fixed:** integrations were scattered across plugin screens. The custom **Growth & Delivery** screen explains status, ownership, consent, and next actions.
- **Preserved:** WordPress remains `noindex`; the public cinematic site remains the search authority, preventing duplicate indexed copies.
- **Preserved:** committee users cannot install plugins, change themes, administer users, or enter full WordPress settings.

## Launch gates

- Do not send a campaign until the sender identity, footer, unsubscribe link, audience count, and test message are reviewed.
- Do not bulk-import classmates into FluentCRM. Registration is not promotional consent.
- Do not enable a Stripe live gateway or paid ticket CTA until decline, 3DS, refund, replay, webhook retry, and ticket revocation tests pass.
- Do not force two-factor login until the owner has enrolled, stored recovery codes, logged out, and logged back in successfully.
- Do not use Fluent Forms for private yearbook or memory uploads; those require the moderated portal media pipeline.

## Repeatable acceptance

1. `wp plugin list` shows all five operations plugins active.
2. `WPMS_MAILER` is `resend`, and the key resolves from `/home/nineoo/.config/mbsh-config.php`.
3. A controlled `wp_mail()` to the owner returns success.
4. Manual consent synchronization creates the canonical list/tag but adds no contact without `promotional_email=1`.
5. Cron lists `famtastic_reunion_marketing_sync` and the existing health/reconciliation jobs.
6. CMS robots remain noindex and its sitemap remains disabled.
7. Committee users remain redirected to `/portal/admin/`; only the site owner can access plugin and integration settings.

