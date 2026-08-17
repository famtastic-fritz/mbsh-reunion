# Attendee Portal Data Migration Gate

Date: 2026-08-16
Decision: production attendee-data cutover is blocked until parity is proven.

## Route ownership

| Route | Audience | Destination |
|---|---|---|
| `/portal/` | Verified attendees | Private reunion workspace |
| `/portal/login` | Returning attendees | Branded attendee sign-in |
| `/portal/register` | New attendees | Account creation and verification |
| `/portal/admin/` | Committee Admin and Site Owner | Permission-filtered branded administration |
| `/wp-admin/` | Site Owner only | Full WordPress/WooCommerce control plane |
| `/committee/login`, `/admin/login.php` | Legacy bookmarks | Redirect to branded portal sign-in |

Attendees do not receive WordPress capabilities. Staff use the same verified
identity but switch into a separate Admin Portal. Committee members never use
full WordPress administration; Site Owner is the only supported WordPress role.

## Current data parity

| Existing dataset | Current portal behavior | Cutover state |
|---|---|---|
| RSVP | Registration links the newest normalized-email RSVP record | Read/write bridge still required |
| Dinner selections | Portal honestly disables editing | Read/write bridge still required |
| Legacy ticket reservations | Kept separately from the new ticket wallet/Woo orders | Reconciliation and compatibility import required |
| Surveys and imported contacts | Available only in the legacy committee reports | Identity reconciliation/archive policy required |
| Memories | Legacy memory records and new moderated uploads are separate | Moderation/publication migration required |
| Time capsules | Legacy scheduled worker remains authoritative | Portal display, edit/revoke, and delivery parity required |
| Sponsors | Legacy pending/approved records remain authoritative | WordPress sponsor migration and count parity required |
| In Memory | Legacy memorial data remains authoritative | Sensitive migration and permission review required |
| Chatbot questions and polls | Continue as legacy engagement records | Admin consolidation may follow core cutover |
| Accounts/preferences/uploads/suggestions/notifications | New portal tables and APIs | Implemented locally |
| Paid WooCommerce tickets | WooCommerce is intended financial authority | Test-provider lifecycle and legacy-order reconciliation required |

## Non-negotiable cutover test

Run:

```bash
php scripts/audit-portal-cutover.php
```

The command intentionally exits nonzero while a required dataset is missing or
not declared `migrated`/`bridged_read_write`. A production move requires:

1. A read-only source snapshot and row counts.
2. Normalized-email identity matching with duplicate/conflict review.
3. A durable link from each source record to one attendee or an explicit
   unmatched queue.
4. Portal read/write parity for RSVP and dinner.
5. WooCommerce order/ticket reconciliation without duplicate entitlements.
6. Private media copying with checksums and moderation state preserved.
7. Capsule schedule, attempts, delivery state, and opt-out preservation.
8. Before/after counts, sampled records, rollback rehearsal, and committee
   acceptance.

No source table is deleted during migration. The legacy system remains the
rollback path until the parity report passes.
