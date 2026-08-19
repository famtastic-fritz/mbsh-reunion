# MBSH production analytics setup

Date: 2026-08-18

## Architecture

- Google Analytics account: `FAMtastic` (`63753322`), renamed from the obsolete `Dexis` label.
- Existing FAMtastic Designs property remains unchanged.
- MBSH property: `MBSH Class of 1996 Reunion` (`550753044`).
- Production web stream: `MBSH Reunion Production` (`15460993512`).
- Measurement ID: `G-L6359T51HR`.
- The public event site and the WordPress editorial surface use the same MBSH property so reporting is unified.
- The public static site owns its lightweight `gtag` integration. WordPress uses Google Site Kit for its connection and administrator dashboard.

## Public events

- `page_view`
- `cta_clicked`
- `rsvp_submitted`
- `ticket_order_submitted`
- `menu_selection_submitted`
- `survey_submitted`
- `memory_submitted`
- `time_capsule_submitted`
- `playlist_suggestion_submitted`
- `sponsor_inquiry_submitted`

Outcome events fire only after the corresponding endpoint confirms success. Event payloads exclude names, email addresses, free-text answers, dietary details, order codes, tokens, and other personal data. Limited non-identifying operational dimensions such as attendance status, ticket quantity/value, sponsor tier, and whether a memory includes a photo are retained. All URL query parameters are removed before page locations are sent. Advertising storage, advertising user data, and advertising personalization are denied by default.

## WordPress dashboard

Google Site Kit `1.185.0` is installed, active, and connected. Its server-side settings select account `63753322`, property `550753044`, stream `15460993512`, and measurement ID `G-L6359T51HR`. Site Kit renders Google tag `GT-TNC3982T`, whose Analytics destination is the selected MBSH stream. The production WordPress instance has one user, `fritz`, with administrator access. Analytics dashboard sharing is not configured; the only stored sharing entry is PageSpeed Insights with an empty `sharedRoles` list.

## Production safety

The public HTML on production is the `c247f5e` presentation generation, while the repository has a later shell revision. Analytics deployment must patch the exact live HTML files surgically and must not replace their visual markup. Feature controllers may be deployed only after confirming their production baselines match the repository. Production backups must be captured before both the WordPress and public-site changes.

## Rollback and proof

- WordPress pre-install backup: `/home/nineoo/backups/mbsh-analytics-20260819T020000Z`.
- Public-site backup: `/home/nineoo/backups/mbsh-analytics-public-20260819T015033Z`.
- Completed proof: all nine public routes returned 200 and loaded the static analytics integration once; GA4 showed one active U.S. user in Realtime; Site Kit initial setup completed; its saved IDs match the MBSH property and stream; `/cms/` renders one Google tag; and Analytics dashboard sharing is absent for the sole `fritz` administrator.
