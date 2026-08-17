# WordPress + portal functionality map — 2026-08-16

## Product decision

The branded portal is the attendee and committee application. WordPress is the content/commerce/operations engine behind it, not the attendee interface. Committee members use the same portal identity as attendees and receive additional capability-scoped screens. Only the Site Owner uses WordPress administration for platform maintenance, products, orders, editorial structure, and exceptional operations.

The current implementation is not yet an efficient single system. It has three data planes:

1. production legacy PHP/MySQL workflows;
2. new attendee portal identity and operations tables;
3. WordPress/WooCommerce content and financial records.

The correct migration is staged adapters with one declared authority per record—not copying everything into all three systems.

## Authority map

| Capability | Current production authority | Target authority | Committee experience | Implementation decision |
| --- | --- | --- | --- | --- |
| Accounts, verification, recovery, preferences | Portal service | FAMtastic identity bridge | Branded portal | Custom plugin/service required |
| Event pages, announcements, FAQs | Static HTML | WordPress | Portal/public React components consume REST | Use WordPress core CPT/block editing |
| Products, coupons, orders, refunds | None/legacy reservation | WooCommerce | Branded checkout and portal wallet | Keep WooCommerce + Stripe |
| Paid ticket entitlement and QR check-in | Prototype portal/Woo hooks | Woo order + FAMtastic ticket service | Portal wallet and Committee Desk scanner | Keep custom FAMtastic plugin; optional commercial ticket plugin only after comparison proof |
| RSVP | Legacy `rsvps` | Portal identity-linked event response | Attendee form + committee read/edit | Custom bridge required; not a generic post type |
| Dinner choice/dietary data | Legacy `menu_selections` | Portal identity-linked response | Attendee form + restricted committee view | Custom bridge required because fields are sensitive |
| Memories/media | Legacy tables + portal submissions + WP Memory CPT | WordPress metadata after secure moderation | Portal upload/review; public approved collection | Custom quarantine/moderation bridge required |
| Harry questions and messages | `chatbot_questions` + portal suggestions | Unified conversation/thread service | Committee inbox and attendee thread | Custom plugin/service required |
| Transactional email | Existing Resend adapter/outbox | Resend + durable outbox/Action Scheduler | Delivery health and retries in Committee Desk | Keep custom adapter; do not add a second mail authority |
| Surveys | Legacy `surveys` | Read-only historical archive | Committee reports/search | Import/link; do not create attendee accounts automatically |
| Time capsules | Legacy table/cron | Custom private scheduled record + worker | Attendee ownership + restricted committee operations | Custom plugin/service required |
| Sponsors | Legacy pending/approved + WP content | WordPress Sponsor CPT after moderation | Committee review; public approved wall | Add CPT/workflow to FAMtastic plugin |
| Memorials | Legacy `in_memory` | WordPress curated Tribute CPT | Sensitive committee moderation | Add separate CPT/consent workflow |
| Polls | Legacy poll tables | FAMtastic plugin tables or WP CPT + vote table | Portal voting and committee management | Custom plugin required for one-vote and audit semantics |
| Worker health | WP-Cron prototype + portal outbox | Action Scheduler + system cron | Committee health; owner failures | Extend FAMtastic plugin |

## Plugin evaluation

### Already appropriate

- **WooCommerce:** financial source of truth for products, orders, coupons, refunds, stock, and customer billing.
- **WooCommerce Stripe:** payment gateway and webhook boundary.
- **FAMtastic Reunion Platform:** necessary custom domain plugin for identity linkage, private fields, moderation, ticket issuance, staff capabilities, adapters, and branded REST contracts.
- **Action Scheduler (bundled with WooCommerce):** durable, inspectable async jobs. Replace the plugin's single hourly heartbeat with grouped email, reminder, capsule, and reconciliation jobs.

### Evaluate, but do not install blindly

- **WooCommerce Events & Ticketing Manager / Box Office / Event Tickets Plus:** can supply ticket-product, attendee fields, PDF/QR, and check-in features. They overlap the already-built virtual-ticket service. Install only if a sandbox comparison proves they reduce code without creating a second ticket authority or breaking the branded portal.
- **Resend WordPress plugins:** community plugins exist, but the project already has a Resend outbox with retries, provider IDs, and dead-letter behavior. Adding one would create two delivery/logging paths. Continue the custom adapter and route WordPress `wp_mail()` through it instead.

### Custom functionality still required

- production legacy snapshot/adapter service;
- explicit portal↔WordPress user mapping;
- RSVP/menu identity reconciliation and audited write-through;
- one conversation model for Harry, attendee support, and email replies;
- media quarantine and moderation publishing adapter;
- sensitive capsule and memorial workflows;
- owner-managed committee memberships;
- source-labelled reconciliation dashboard.

## Local proof completed

The Committee Desk now reads the isolated production snapshot without merging or editing it:

- 10 production RSVPs;
- 21 dinner selections;
- 88 historical surveys;
- 8 unanswered Harry questions shown in the inbox;
- 7 time capsules retained privately in the snapshot.

Every response includes `data_context.mode=production_snapshot`, `read_only=true`, and the UI displays **Production Snapshot · read only**. Production RSVP records appear in protected People search with a source label. No production emails or writes are possible through this adapter.

## Next implementation sequence

1. Build reconciliation report: normalized email matching, duplicates, unmatched RSVP/menu/survey records, and explicit human merge decisions.
2. Create immutable external-source links rather than copying production rows into portal identities.
3. Add read-only RSVP and menu details to the committee person record with sensitive-field access audit.
4. Create unified conversation tables and import/link Harry questions; add assignment, reply, status, and Resend delivery.
5. Add owner-only committee membership management inside the portal.
6. Connect WordPress CPTs for announcements, sponsors, approved memories, tributes, FAQs, and collections.
7. Make WooCommerce the only source for paid tickets; reconcile legacy reservations separately.
8. Move scheduled work to Action Scheduler/system cron with retries, dead-letter visibility, and heartbeat alerts.
9. Prove each adapter against snapshot counts before production write-through.
10. Retire each legacy admin screen only after corresponding portal parity passes.

## Research references

- WooCommerce Events & Ticketing Manager: https://woocommerce.com/document/event-and-ticketing-manager/
- WooCommerce Box Office: https://woocommerce.com/document/woocommerce-box-office/
- WordPress Event Tickets: https://wordpress.org/plugins/event-tickets/
- Action Scheduler API: https://actionscheduler.org/api/
- Mail via Resend plugin listing: https://wordpress.org/plugins/mail-via-resend/
