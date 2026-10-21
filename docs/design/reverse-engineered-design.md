# MBSH Event Cinema — Reverse-Engineered Design Contract

**Contract version:** `mbsh-event-cinema.design.v1`
**Status:** draft extraction for reuse and parity work
**Reviewed:** 2026-09-09
**Source repository:** `site-mbsh-reunion-event-cinema`

This document extracts the design rules that are actually implemented in the
MBSH reunion build. It is intended to make the quality bar portable to Site
Studio, future customer repositories, and later pages. It is not a replacement
for the approved brief or the runtime authority map.

## Authority and confidence

| Area | Authoritative source | Confidence |
|---|---|---|
| Product intent, audience, tone, must-have routes, prohibited patterns | `spec.json` (`design_brief`) | High |
| Public composition and navigation ownership | `docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`, `frontend/js/cinematic-shell.js` | High |
| Runtime color, type, spacing, motion variables | `frontend/css/base.css`, `frontend/css/cinematic-system.css`, `frontend/css/premiere.css` | High |
| Page-specific visual and transactional behavior | `frontend/*.html`, `frontend/css/*.css`, `frontend/js/*.js` | High |
| CMS, attendee, committee, and payment boundaries | `docs/architecture/EVENT_CINEMA_ARCHITECTURE.md`, `docs/HEADLESS_WORDPRESS_OPERATIONS.md` | High |
| Asset role, rights, and provenance | `frontend/assets/story/RIGHTS-MANIFEST.md`, `docs/operations/PRIVATE_ARCHIVE_SOURCE_MANIFEST.md`, asset paths | High where recorded |
| Intended future component names and page sequence | `docs/MBSH_CINEMATIC_FACELIFT_BLUEPRINT_2026-08-14.md` | Medium: blueprint, not a guarantee of current runtime |
| Social-kit presentation variant | `marketing/hyperframes/mbsh-hi-tide-reunion-social-kit-20260820/DESIGN.md` | Medium: campaign-specific variant, not the public-site contract |

Where the runtime and blueprint differ, the runtime is the parity target and
the blueprint is a proposal for future work. Where `spec.json` still names an
older hosting provider, deployment configuration must be treated as a separate
operational contract rather than a visual rule.

## Product character

- **Experience:** a cinematic reunion companion, not a generic event template.
- **Audience:** MBSH Class of 1996 alumni, mobile-first, with a secondary
  audience evaluating FAMtastic's build quality.
- **Tone:** cinematic, elegant, black-tie, warm but restrained, reverent when
  the subject requires it.
- **Narrative vocabulary:** scene, reel, usher, marquee, curtain, ticket,
  soundtrack, premiere, encore, and homecoming.
- **Primary promise:** make the reunion feel like a production while keeping
  RSVP, tickets, food, memories, and account actions understandable.

## Token contract

The existing CSS has two compatible token layers. The base layer is the
historical V1 brief; the cinematic layer is the newer shared shell and vertical
slice. New pages should consume these variables rather than inventing local
values.

### Color

```json
{
  "ink": "#0A0A0A",
  "ink_soft": "#1A1A1A",
  "cinema_ink": "#070607",
  "panel": "rgba(18, 12, 14, 0.86)",
  "red": "#C8102E",
  "red_deep": "#A00C24",
  "red_bright": "#EF2848",
  "white": "#FFFFFF",
  "cream": "#F8F4EC",
  "cinema_cream": "#F5EFE5",
  "silver": "#C0C0C0",
  "silver_light": "#E8E8E8",
  "silver_dark": "#8A8A8A",
  "gold": "#D8B86A",
  "line": "rgba(216, 184, 106, 0.32)"
}
```

Rules:

1. Never use pure `#000` for the page background.
2. Silver is a material treatment, not a flat gray surface; use the existing
   `--grad-silver` or `--grad-silver-bezel` when rendering metallic surfaces.
3. Red is reserved for decisive actions, active states, and premiere energy.
4. Gold is a guide/metadata accent, not a replacement for the primary CTA.
5. Light cream surfaces are task surfaces for forms and readable content; do
   not place long forms over moving media.

### Typography

| Role | Family | Use |
|---|---|---|
| Display | Playfair Display, 400/700/900 | hero and section headlines, milestones |
| Editorial | Cormorant Garamond, 400/500/700 italic | leads, captions, emotional copy |
| Overlay script | Allura | short overlay statements only |
| Tagline script | Great Vibes | short taglines only |
| Interface | Inter, 400/500/600/700 | navigation, buttons, labels, controls |
| Data | JetBrains Mono, 400 | countdowns, codes, ticket/operational values |

Maximum: five font families on a page, despite the six-family repository
system. Keep generated imagery free of important text; render copy in HTML.

### Layout and shape

```json
{
  "content_max": "76rem",
  "content_narrow": "60rem",
  "body_content": "48rem",
  "radius_cinematic": "1.35rem",
  "radius_control": "999px",
  "spacing": ["0.25rem", "0.5rem", "1rem", "1.5rem", "2.5rem", "4rem", "6rem"],
  "shadow_cinematic": "0 24px 80px rgba(0,0,0,0.48)",
  "touch_target_minimum": "44px"
}
```

The public site uses full-bleed media for a hero, constrained content rails for
reading, compact status strips, three-up journey choices on wide screens, and
one-column cards on small screens. A page-specific composition may be
cinematic, but it must remain useful when media is unavailable.

## Composition contract

Every public route is:

```text
SharedShell(Header + Navigation + Footer)
  + PageContent(ordered sections)
  + PageAwareHarry (optional, contextual)
```

`frontend/js/cinematic-shell.js` owns the visible header, drawer, footer,
root-absolute route registry, skip link, page identity, progressive video,
alumni invitation, and shell-level guidance. A page must not copy or replace
global navigation. The body declares one `data-page` value.

### Shared shell

- Fixed translucent header with brand mark, page-independent alumni login, and
  conventional Explore/Menu control.
- Viewport-bound drawer with focus loop, Escape handling, backdrop, background
  scroll lock, and `aria-current` route state.
- Root-absolute routes so nested pages cannot break navigation.
- Footer with commemorative seal, committee contact, route directory, attendee
  portal, official MBSH site, sponsor/memory actions, and FAMtastic credit.
- Skip link and a main landmark identified by `#main-content`.

### Page primitives

- `CinematicHero`: poster-first background media, HTML headline stack, one
  primary CTA, optional secondary CTA.
- `CinemaStatus`: compact date/destination/attire or lifecycle summary.
- `CinemaSectionHeading`: kicker, display heading, supporting lead.
- `JourneyCard`: one clear next task; never a decorative card that behaves like
  a button without a destination.
- `PageNote`/`Billboard`: short usher guidance and a finite, controllable
  presentation unit.
- `FormScene`: readable task surface with labels, validation, retry, duplicate,
  network failure, and success states.
- `SuccessScene`: what was saved, email state, confirmation/reference, and the
  next action.
- `NextScene`: a single relevant next step rather than a second full sitemap.
- `HarryAssistant`: page-aware guidance and escalation; optional enhancement,
  never the only route to a core task.

## Route design matrix

| Route | Job | Dominant composition | Authority / action |
|---|---|---|---|
| Home | orient and route | cinematic hero, status strip, journey cards, venue | public frontend; links to real workflows |
| RSVP | record attendance | task hero, progress, progressive form, confirmation | portal/backend RSVP authority |
| Tickets | sell or request tickets and sponsorship | phase banner, choice cards, order summary, payment/inquiry | WooCommerce/payment authority |
| Dinner (`/menu/`) | capture meal choice | menu story, guest lookup, choice cards, confirmation | portal/backend preference authority |
| Class Check-In | early interest when formal RSVP is not the source of truth | compact form scene | survey endpoint; must not duplicate formal RSVP silently |
| Through the Years | present eras and moderated memories | timeline rail, archive chapters, submission | approved archive + moderation authority |
| In Memory | respectful tribute | quiet hero, verified names, correction path | memorial records and committee review |
| Time Capsule | private delayed reflection | envelope/capsule scene, privacy promise, form | private queue and scheduled worker |
| Soundtrack | play and collect songs | player stage, chapters, suggestion form | playlist provider plus suggestion endpoint |
| Manual | teach the site | field guide / contextual help | static documentation, not authorization |
| Attendee Portal | account-owned actions | branded portal shell | verified portal session |

## Motion and media contract

- Hero video is muted, inline, looped, and progressive; poster loads first.
- `prefers-reduced-motion` and `Save-Data` suppress video and reduce effects.
- Motion is reserved for orientation, chapter transitions, emotional reveals,
  success, and contextual Harry guidance—not long forms, privacy copy, prices,
  memorial names, or checkout totals.
- Use IntersectionObserver/lazy loading for below-fold media and pause media
  outside the viewport.
- Every cinematic interaction has a static semantic fallback.
- Harry has three asset tiers: cinematic hero, editorial still, and utility
  illustration. Never mix tiers without a documented reason.

## Asset and rights rules

- Public assets are approved derivatives, not raw archival sources.
- Rights and provenance live beside the asset or in a rights manifest.
- Do not publish diploma names, signatures, yearbook faces, or source scans by
  default; do not run automatic face identification.
- Generated Harry assets must preserve core character identity, approved colors,
  costume, medallion, proportions, and respectful tone.
- Important text belongs in HTML; images and video supply atmosphere and
  evidence, not authoritative event facts.

## Responsive and accessibility contract

- Design and verify at 390, 768, and 1280 widths; also test 320 where the
  portal or auth flow requires it.
- No horizontal overflow; controls have at least a 44px touch target.
- Keyboard access includes skip link, drawer focus trap, Escape close, visible
  focus, and form error summaries.
- Core navigation, forms, checkout, archive, and tickets work with motion and
  video disabled.
- Contrast and light-form text are explicit; do not rely on inherited body
  color in a light card.
- Empty, loading, success, duplicate, unauthorized, network-failure, and
  retry states are designed—not left to browser defaults.

## Quality and parity gates

An approved design is portable only when the receiving build can prove:

1. The same route list and `data-page` identities.
2. The same token values, typography roles, content widths, responsive rules,
   and component states.
3. The same approved artifact/assets or content-equivalent derivatives with
   rights receipts.
4. Screenshot parity at 390, 768, and 1280 for the selected fixture.
5. No console errors, broken internal links, horizontal overflow, or missing
   required controls.
6. Reduced-motion and keyboard checks pass.
7. Form behavior, auth, payment, moderation, and portal boundaries remain
   owned by their declared authority; visual parity alone is not functional
   parity.

## What is not yet authoritative or portable

The following are gaps to close before claiming a one-click design contract:

- The older `spec.json` still names Netlify as the deploy provider while the
  current operational direction is FAMtastic Inc.; deployment belongs in a
  separate target contract and must be resolved before delivery.
- `frontend/css/premiere.css` contains historical and newer overlapping visual
  treatments. A future token extraction should map or retire duplicates instead
  of copying the entire stylesheet into every new site.
- Some page markup is hand-authored and some shell/content is injected by
  JavaScript. Site Studio needs an explicit generated component manifest so
  injection order cannot silently change the design.
- The social-kit `DESIGN.md` uses Archivo Black/EB Garamond and a campaign
  palette variant. It should remain a campaign recipe, not overwrite the
  public-site token contract.
- CMS/portal behavior is documented and tested separately from the public
  visual shell. A `design.md` file cannot by itself recreate DB schemas,
  authentication, moderation, payment, email, or deployment receipts.
- The MBSH build contains site-specific copy, event facts, media rights, and
  route semantics. Reuse should promote recipes and tokens, not copy private
  data or imply that every client needs the event platform.

## Recommended handoff shape

Future Site Studio delivery packets should carry:

```text
design.md                         human-readable doctrine (this shape)
.famtastic/design-contract.json   versioned tokens + components + breakpoints
.famtastic/site-manifest.json    routes, authority map, capabilities
.famtastic/assets-manifest.json  approved asset hashes + rights status
.famtastic/content-manifest.json route copy and structured content references
.famtastic/quality-gates.json    parity, accessibility, behavior, and receipts
```

The receiving repo may materialize the approved artifact, but future pages and
edits must inherit the contract rather than re-interpret a screenshot. This is
the important boundary: `design.md` keeps the system consistent; it does not
replace the backend capability recipes or an authoritative deployment receipt.

## Verification references

- `node tests/frontend/shared-public-shell.test.mjs`
- `docs/qa/SHARED_SHELL_MIGRATION_QA_2026-08-17.md`
- `docs/implementation/EVENT_CINEMA_BUILD_RECORD_2026-08-16.md`
- `docs/architecture/EVENT_CINEMA_ARCHITECTURE.md`
- `docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`
- `site-studio/recipe/HEADLESS_EVENT_PLATFORM_RECIPE.md`
- `site-studio/recipe/capability-manifest.json`
