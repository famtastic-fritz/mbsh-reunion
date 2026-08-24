# MBSH Reunion Navigator v1

Status: implemented in the front end; no server-side training-progress ledger
is claimed by this version.

## Purpose

The Navigator is a small, optional, role-aware orientation layer that explains
the actual MBSH reunion experience and sends a person to the appropriate real
screen. It is not a chatbot replacement, a fake demo, or an automation agent.

One shared guide registry renders three versions:

| Audience | Entry point | Goal | Route authority |
|---|---|---|---|
| Public visitor | `How it works` launcher | Understand RSVP, event discovery, private account, and memory submission | Public cinema routes only |
| Verified attendee | `Guide me` in My Reunion | Find and complete a real personal reunion task | Attendee portal and its existing authenticated APIs |
| Committee member or lead | `Start tour` in Admin Portal and Harry briefing | Reach a capability-permitted operational workspace | Server-issued staff capabilities and Admin Portal routes |

## Interaction contract

- The guide presents at most five short scenes, a progress indicator, Back,
  Next, Finish, and a `Take me there` action.
- `Take me there` may follow a canonical route or activate an existing
  navigation control. It never submits a form, changes data, opens a private
  record, exposes a ticket credential, sends a message, approves media, or
  initiates payment.
- The public guide never auto-opens. It coexists with the existing timed alumni
  invitation; while that invitation is visible, the guide hides itself rather
  than competing for the visitor's attention.
- The attendee guide mounts only after the portal session reports an
  authenticated attendee. The committee guide mounts only after the separate
  Admin Portal receives `staff.authorized` and filters its scenes by the
  server-returned capability set.
- A missing or hidden target yields a truthful unavailable message. It does not
  substitute a pretend action or record.

## Privacy, access, and analytics

Version 1 deliberately has no persisted training completion state. It stores
no browser state, account ID, staff ID, email address, message text, ticket
data, or URL query/hash. Re-entering the guide starts its short path again.

Its optional analytics events use only guide/role/step/viewport/motion and
availability metadata through the existing privacy-safe public analytics hook.
Actual RSVP, upload, message, and ticket outcomes remain measured by their
underlying workflows—not by guide completion.

## Accessibility and motion

- The panel is a non-modal labeled dialog; it does not trap the page or block
  RSVP, login, payments, uploads, or operational controls.
- Escape closes it and returns focus to the launcher.
- All controls meet the 44px touch target baseline. At phone widths actions
  stack rather than covering the current page CTA.
- Reduced-motion and data-saver users get text plus a static focus outline,
  with no animated or forced-scroll spotlight.
- Colour is never the only progress cue, and the current scene is represented
  as text and a native progress element.

## Future, only if needed

Cross-device resume or completion reporting requires a separate, narrow
server-owned training record keyed to the authenticated account. It must accept
only `guide_id`, `version`, `status`, and `last_step_key`; derive the account
and role from the server session; audit writes; and include CSRF, cross-account,
role-revocation, replay, and expiry tests. Do not put portal training state in
localStorage or use it as a proxy for actual task completion.
