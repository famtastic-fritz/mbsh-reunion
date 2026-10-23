# Unlock the Night: three approved campaign sends

Fritz approved the three actual owner proofs on October 22, 2026, and explicitly confirmed that the committee's permission for this existing reunion list covers these emails with the FAMtastic commercial. This is scoped campaign authority, not a change to individual global promotional flags or a claim about historical consent dates.

| Wave | America/New_York | UTC | Approved subject |
|---|---|---|---|
| Friday | October 23, 6 PM | 2026-10-23T22:00:00Z | Unlock the Night, Class of ’96 |
| Sunday | October 25, 11 AM | 2026-10-25T15:00:00Z | Think You Remember ’96? |
| Monday | October 26, 10 AM | 2026-10-26T14:00:00Z | Your Story Belongs Here |

All three are nonbuyer acquisition messages from Hi-Tide Harry. The approved content includes the exact commercial referenced by the current public Connect Card (`https://famtasticdesigns.com/connect/commercial.mp4`), its poster, the FAMtastic website CTA and compact creator logo. Both commercial assets returned HTTPS 200. Sunday's trivia question was checked against the Academy's [1996 ceremony record](https://www.oscars.org/oscars/ceremonies/1996). Trivia is described as planned, not currently open. Memory submissions require committee review. No prize, automatic publication or Tuesday send is promised.

## Audience and execution

The initial inventory reported 103 contacts. The detailed campaign audit found two further test identities (a smoke fixture and a form submission explicitly named as a test), leaving 101 real candidate addresses. This correction preserves all database records. The original 78 distinct survey contacts were already imported; this campaign does not import or globally subscribe anyone.

A private manifest freezes this pool. Before every submission and retry the runner checks WooCommerce current-mixer paid orders, including verified explicit account links; it does not merge people by name or guess Gmail aliases. It refreshes purchase authority again immediately before submission. Historical paid buyers remain excluded after refunds conservatively. Abandoned requests do not count as purchases. A purchase racing an already accepted provider request cannot recall that email.

The runner also checks provider suppressions and contact unsubscribes, WooCommerce unsubscribe records, disabled accounts, portal preferences, direct withdrawal messages and historical Phone/Mail preferences. Untouched imported defaults are distinct from a later explicit save. The existing authenticated/CSRF-protected preference endpoint now records a save timestamp even when an attendee saves unchanged “off” values, enabling the next campaign check to honor that choice. Account preferences and reply-to-committee unsubscribe instructions remain in the accepted email; a mailto List-Unsubscribe header supports email clients. No one-click unsubscribe endpoint is claimed.

Production execution uses the existing host cron service, independent of the desktop app. Three dated manifest jobs share a locked worker. Durable state is written before provider submission, with an unchanged per-wave/per-recipient provider idempotency key and payload on retries. Five attempts with backoff lead to a visible dead-letter state. A one-hour send window prevents a late runner from compressing the cadence. An uncertain provider outcome after that window requires manual investigation, never a blind resend. Provider acceptance and eventual delivery are separate states.

## Operation and reversal

Private runtime/evidence directory: `/home/nineoo/.releases/mbsh-reunion/campaigns/unlock-the-night-20261023`.

- Status: `php runtime/campaigns/run-unlock-the-night.php status <private-directory>`.
- Read-only current audience preview: replace `status` with `preview`.
- Pause: under `worker.lock`, atomically set `manifest.json` activation to `held`. Preserve state and provider IDs.
- Runtime logs, heartbeat, per-recipient skip/retry/dead-letter records and accepted provider IDs remain private. Do not commit addresses, payloads or account timestamps.
- The private deployment record includes original cron, endpoint backup and file hashes. Reversal removes only this campaign's cron line and restores the preference endpoint/library; it never deletes orders, contacts, tickets, campaign receipts or changes other cron entries.

## Validation and acceptance

Local tests cover exact due time, no early sends, replay, purchase between waves, purchase before retry, new suppression, crash recovery, identical retry payload/key, retry exhaustion, late-run refusal, recipient tampering, current paid authority, verified aliases, refunded buyers and unchanged-off preference saves with account isolation. Shared public shell and existing PHP suite are checked separately.

The managed worktree repository verifier rejects its common-directory ownership (`common_directory_mismatch`). The fresh independent source clone at the same runtime revision passes repository verification and the complete local backend suite; the managed-worktree failure remains recorded. No hosted GitHub Actions are used.

| capability_id | Customer job and entry | Saved result / visibility / reversal | Evidence | Provider | Owner acceptance | Consumption / gap |
|---|---|---|---|---|---|---|
| campaign.unlock-night.content | Fritz reviews three actual Gmail proofs | Three owner-only provider receipts; private; cannot recall delivery | Delivered and independently visible in Gmail INBOX at 23:14 EDT Oct22; exact proofs preserved | Resend verified for these three proofs | observed_pass: Fritz said the emails look good | Production proof consumption; attendee delivery not yet due |
| campaign.unlock-night.schedule | Run three approved dates to eligible nonbuyers | Private manifest and durable item state; CLI status and logs; hold manifest to pause | Local tests passed; production manifest active at 23:34:22 EDT; automatic cron heartbeat 23:35:01 EDT | Existing Resend connection; scheduled attendee delivery remains future | Content/scope approved; future execution not yet observed | Production worker receipt governs activation claim |
| campaign.unlock-night.preferences | Attendee saves optional-message choice in portal Notifications | Existing preferences row and explicit save time; own account only; may change later | Local database test; production source hash receipt | not_applicable | pending: no uncoached owner task observed for this internal correction | Supports campaign exclusion; no global preference changes performed |

Owner task record: `unlock-night-proof-review-20261022`, owner Fritz, Gmail, the three explicitly requested nonbuyer proofs. Preconditions: delivered once with PROOF/date labels and exact commercial. Action: read the three emails. Expected: approved layout/copy. Observed: “Okay, the emails look good.” Content revision is the SHA-256 recorded in the private proof receipts; production manifest pins content and existing email-layout hashes. This acceptance does not imply attendee delivery, conversion or future event activity acceptance.

## Production closeout

Classification: **deployed**. Runtime source `06e583d48cbb0676ee17f484e220062be5cba738`. The three jobs are active; the existing host cron ran the worker automatically at 23:35:01 EDT on October 22. The private manifest exactly matches the accepted proof content and pins both shared email-layout files. One new cron entry preserves every pre-existing entry. No attendee email was submitted tonight.

The read-only production preview is **70 eligible**, **9 paid mixer buyers excluded**, **21 historical Phone/Mail preferences excluded**, and **1 provider bounce excluded**, from **101** corrected candidates. These are disjoint preview categories. One current nonbuyer has explicit global promotional opt-in; the other allowed recipients rely on Fritz's current committee-scope confirmation for this campaign, without changing global flags. Zero explicit unsubscribe requests were found in the checked Woo/provider/portal-message records; source coverage is recorded privately, not a claim about every possible external conversation.

[Sanitized release receipt](receipt.json) records the exact source hashes, dated job identities and evidence boundaries. Rollback is prepared and syntax-checked, not executed against the active release. The public preference endpoint still rejects unauthenticated GET/PATCH requests; the unchanged-off save behavior is database-tested locally. Owner proof review passed; future campaign delivery and independent owner operation remain unobserved.
