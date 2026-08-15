# MBSH Class of 1996 Reunion — Cinematic Facelift Blueprint

**Assessment date:** August 14, 2026
**Scope:** Public frontend, public forms, shared navigation, responsive behavior, cinematic media, and the existing PHP-backed interaction logic.
**Goal:** Preserve what each page does while rebuilding the presentation as a reusable, mobile-first component system featuring cinematic motion and a consistent ultra-real Hi-Tide Harry.

## Executive direction

The current site has a valuable creative idea: the reunion is presented as a premiere, each page is a scene, the medallion is the navigation compass, and Harry is the usher. The facelift should retain that identity.

The opportunity is to make the experience feel like one continuous production instead of a collection of separately styled pages. The revised site should behave like a cinematic reunion companion:

1. Harry welcomes the class and guides the next action.
2. Every page opens with a short, relevant visual moment—not an unrelated decorative video.
3. Content is organized into reusable containers with predictable spacing and mobile behavior.
4. Registration, ticketing, meal selection, memories, surveys, and music form one understandable attendee journey.
5. Motion communicates progress and emotion without delaying tasks or overwhelming visitors.
6. Static Harry illustrations are replaced selectively with one canonical ultra-real character system. The older cartoon poses remain available for small utility states where photoreal imagery would be excessive.

## Current-site diagnosis

### What should be preserved

- Black, crimson, silver, and warm-gold premiere palette.
- The 1996–2026 / 30-years / 100-years medallion.
- “Let us be known for our deeds.”
- Scene, reel, usher, marquee, curtain, ticket, and soundtrack vocabulary.
- Existing forms and their backend responsibilities.
- Progressive disclosure in RSVP.
- Committee review gates for memories and sponsors.
- Privacy language, honeypots, timing checks, upload validation, and confirmation emails.
- Compass concept and Harry reunion assistant.

### What currently weakens the experience

- Pages contain extreme vertical dead space caused by full-scene sequencing and minimum-height treatments.
- Important actions can sit several screens below the promise that introduced them.
- Repeated navigation, chatbot, header, footer, note, and card markup make visual consistency expensive to maintain.
- Harry changes between cartoon poses and newer ultra-real media without a defined visual hierarchy.
- Forms appear as bright white documents placed on top of the cinematic interface rather than belonging to it.
- Survey and RSVP collect overlapping attendance data without clearly explaining their different purposes.
- Ticket ordering stops before payment and therefore feels less complete than the surrounding production.
- Some pages have deep content; others feel unfinished or empty when live data is unavailable.
- The existing 404 audit captured the host's plain “404 Not Found” rather than the designed Harry 404, indicating a routing/deployment gap.
- Navigation is memorable but not immediately obvious to every visitor; the mobile path needs a conventional menu affordance alongside the medallion.
- Full-page desktop compositions become very long, low-density mobile experiences.

## Component architecture

Each page becomes an ordered stack of content containers. Containers may contain components, but they should not own unrelated page logic.

### Global shell

| Component | Responsibility |
|---|---|
| `SiteShell` | Global background, page theme, focus handling, reduced-motion behavior, analytics hooks |
| `ReunionHeader` | Medallion/home link, page title, concise context, ticket/RSVP status |
| `HamburgerMenu` | Extendable mobile-first navigation; medallion remains its visual trigger or companion |
| `DesktopSceneNav` | Clear desktop navigation with current-page state and primary RSVP/ticket CTA |
| `HarryAssistant` | FAQ, page-aware help, form assistance, committee escalation; not a decorative duplicate |
| `ReunionFooter` | Committee details, policies, resources, FAMtastic credit, concise route directory |
| `ActionDock` | Optional mobile sticky action: RSVP, continue form, order tickets, or save selection |
| `ToastAndStatusRegion` | Success, error, retry, upload, and email-confirmation feedback |

### Reusable story containers

| Component | Responsibility |
|---|---|
| `CinematicHero` | Poster-first media, muted inline video, headline, one primary CTA, one secondary action |
| `HarryScene` | Canonical Harry image/video plus a short page-specific message |
| `MedallionTransition` | Small, reusable transition between story phases; never a full empty viewport |
| `SectionIntro` | Eyebrow, heading, lead, optional supporting media |
| `FeatureCardGrid` | Program, expectations, menu steps, sponsorship benefits, soundtrack themes |
| `TimelineRail` | Scrollable eras, milestones, year markers, photos, and memories |
| `MediaStage` | Video, image, audio, gallery, captions, poster, mute/play controls |
| `DataEmptyState` | Useful fallback when attendee, memorial, sponsor, or media records are empty |
| `NextScene` | One relevant next action instead of a repeated mini-site directory |

### Reusable transactional containers

| Component | Responsibility |
|---|---|
| `JourneyProgress` | Shows where a visitor is: Interest → RSVP → Ticket → Meal → Reunion-ready |
| `FormScene` | Cinematic frame around a task-oriented, accessible form |
| `FormStep` | One coherent group of fields with progress and validation summary |
| `ChoiceCard` | Large touch-friendly radio/checkbox card for attending, tickets, entrées, tiers |
| `OrderSummary` | Quantity, price, guests, deadline, payment state, and next step |
| `UploadDropzone` | Photo/logo preview, MIME/size guidance, progress, rejection reason |
| `SuccessScene` | Confirmation number, email status, next action, calendar/share controls |
| `ReturningGuestLookup` | Email/order lookup where permitted, avoiding duplicate submissions |

## Motion and media doctrine

### Where cinematic motion adds value

- Heroes, transitions between major chapters, emotional reveals, countdowns, timeline progress, success moments, and social proof.
- Harry entering, presenting the medallion, pointing toward an action, opening a curtain, sealing the capsule, cueing music, or respectfully leading a tribute.
- Subtle environmental loops: curtain movement, theater dust, marquee bulbs, ocean reflections, yearbook pages, confetti, and record rotation.

### Where motion should not be used

- Behind long forms, memorial names, legal/privacy copy, detailed pricing, dietary fields, or any area requiring sustained reading.
- As an autoplaying audio experience.
- As a forced intro before a user can RSVP or buy.
- As a large download on slow mobile connections when a poster frame communicates the same meaning.

### Technical rules

- Poster image loads first; video is an enhancement.
- Muted, inline, looped decorative clips only. Sound requires explicit user action.
- Respect `prefers-reduced-motion` and provide equivalent static frames.
- Lazy-load below-the-fold video and pause it when offscreen.
- Keep hero clips approximately 5–10 seconds and section accents approximately 2–4 seconds.
- Text is rendered in HTML, not baked into generative video.
- Maintain one character reference packet for Harry: front, three-quarter, side, full-body, medallion, shirt, cape, shoes, facial proportions, and approved color values.
- Review every generated asset for extra limbs, altered logos, unreadable medallion text, costume drift, and disrespectful tone.

## Page-by-page blueprint

### 1. Home — the premiere lobby

**Current purpose:** Welcome the class, communicate date and venue, and route visitors to every major experience.

**Current containers:** curtain hero; usher note; program grid; director strip; venue map; repeated next-reel navigation; footer.

**Proposed container sequence:**

1. `CinematicHero`: use the new Harry movie as the visual foundation, with HTML overlays: “30 years later. 100 years strong.”, date, venue, RSVP CTA, and watch-with-sound control.
2. `ReunionStatusStrip`: countdown, ticket phase, RSVP count when approved, and deadline.
3. `HarryScene`: Harry emerges from the lobby holding the medallion and says one concise welcome.
4. `JourneyChooser`: three primary tasks—Tell us you're coming, secure tickets, explore the reunion—with secondary links below.
5. `LegacyFeature`: split-screen 1996 → 2026 preview leading to Through the Years.
6. `EventEssentials`: date, time, dress, venue, map, accessibility/contact.
7. `SocialProofStage`: approved attendee/sponsor/memory preview, with a useful empty state.
8. `NextScene`: RSVP or tickets depending on the visitor's known state.

**Harry/media plan:** full cinematic hero; realistic lobby portrait; medallion micro-animation; no repeated cartoon Harry at three different scroll positions.

**Logic preserved:** countdown, config bindings, map, all route links, chatbot.

### 2. RSVP — reserve your place

**Current purpose:** Record attendance intention and reveal additional fields for attending guests.

**Current containers:** page header; countdown; usher note; progressive RSVP form; success; what-to-expect cards; reunion meaning; footer.

**Proposed container sequence:**

1. `TaskHero`: Harry at a theater podium with clipboard; countdown and “takes about two minutes.”
2. `JourneyProgress`: Interest → RSVP → Ticket → Meal.
3. `FormScene` with steps:
   - Identity: first name, last/maiden name, email.
   - Attendance: yes/maybe/no.
   - If yes: phone, city/state, guest count/names, dietary, public listing, volunteer interest.
   - Review and consent.
4. `SuccessScene`: personalized confirmation, email status, calendar add, tickets CTA, time-capsule CTA.
5. `NightPreview`: arrival, tribute, dance floor—compact and optional.

**Harry/media plan:** short clipboard movement in the hero; subtle confirmation salute after success; never animate beside active typing fields.

**Logic preserved:** conditional fields, consent, public-display opt-out, confirmation email, anti-spam fields, validation, confetti—refined into a restrained success burst.

### 3. Class Survey — early interest and planning

**Current purpose:** Collect a quick headcount and contact information while event details are still firming up.

**Issue to resolve:** It currently overlaps heavily with RSVP and can create uncertainty about which form is authoritative.

**Decision blueprint:**

- Rename to **Class Check-In** when it is a preliminary interest form.
- If formal RSVP is open, redirect or transform the page into the RSVP experience rather than collecting a second attendance record.
- If historical responses must remain, preserve them in the backend and label the state clearly.

**Proposed containers:** compact Harry check-in hero; seven-question `FormScene`; “what happens next” card; success with RSVP conversion when applicable.

**Harry/media plan:** realistic Harry using a head-count clicker; small loop only.

**Logic preserved:** seven fields, confirmation email, real-time committee visibility, existing survey endpoint.

### 4. Tickets and Sponsorship — secure the night

**Current purpose:** Explain ticket pricing, collect a ticket order, and collect sponsor inquiries.

**Proposed container sequence:**

1. `CinematicHero`: red carpet, marquee, Harry presenting tickets.
2. `TicketPhaseBanner`: active price, deadline, availability, and whether payment is immediate or follows later.
3. `TicketChoiceCards`: early/regular pricing with one clear current selection.
4. `TicketOrderFlow`: contact → quantity/guests → review → payment/instructions.
5. `OrderSummary`: persistent on desktop, sticky bottom summary on mobile.
6. `SuccessScene`: order number, email, exact next step, add-to-wallet/calendar later.
7. `SponsorshipStory`: why sponsorship matters, audience, benefits, approved sponsor wall.
8. `SponsorTierCards`: tier, deliverables, deadlines, approval conditions.
9. `SponsorInquiryFlow`: company, contact, tier/custom amount, logo, message, consent.

**Harry/media plan:** realistic red-carpet usher; ticket reveal animation; sponsor spotlight portrait; avoid motion behind prices and order totals.

**Logic preserved:** current pricing config, up-to-ten quantity, guest names, committee notes, sponsor tiers/custom amount, logo upload, approval workflow, notifications.

**Future-ready logic:** payment status becomes an explicit state machine—`inquiry`, `order_reserved`, `payment_pending`, `paid`, `refunded`—instead of hiding payment readiness in explanatory copy.

### 5. Dinner Preferences — choose your plate

**Current purpose:** Present the finalized menu, capture one entrée, and collect dietary restrictions.

**Proposed container sequence:**

1. `TaskHero`: elegant banquet-table scene with Harry as maître d'.
2. `MenuStory`: hors d'oeuvres, salads, and sides in compact course cards.
3. `GuestLookup`: email/order/RSVP match before showing selection where technically allowed.
4. `ChoiceCardGroup`: three entrée cards with clean food imagery and text-first accessibility.
5. `DietaryPanel`: allergy/special-request field with privacy guidance.
6. `SelectionSummary`: guest, entrée, dietary note, editable confirmation.
7. `SuccessScene`: email sent, selection shown, update instructions.

**Harry/media plan:** cinematic banquet hero and a subtle presenting gesture; food photography should remain the primary visual evidence.

**Logic preserved:** three finalized entrées, included courses, dietary notes, confirmation email, committee coordination.

### 6. Through the Years — the living archive

**Current purpose:** Present the school's five eras and collect approved class memories.

**Current weakness:** the timeline is still a coming-soon poster, so the page promises a story it does not yet deliver.

**Proposed container sequence:**

1. `CinematicHero`: Harry opens a vault/yearbook; 1926 transforms into 2026.
2. `TimelineRail`: five eras, each its own reusable `EraChapter` with image, short history, artifact, and memory.
3. `ClassOf96Feature`: a richer chapter for senior year, music, halls, yearbook, and approved contributions.
4. `ThenAndNow`: paired photos or recreations.
5. `MemoryGallery`: moderated public memories with filters.
6. `MemorySubmissionFlow`: name, email, story, photo preview, public-display consent, approval explanation.
7. `SuccessScene`: moderation status and share/invite prompt.

**Harry/media plan:** five historically styled but identity-consistent scenes; Harry's costume/environment may reflect an era, but his core character cannot drift.

**Logic preserved:** five era definitions, moderated memory endpoint, photo restrictions, removal rights, committee approval.

### 7. In Memory — the tribute room

**Current purpose:** Respectfully list classmates who have passed and explain the reunion tribute.

**Tone rule:** This page must be quieter than the rest of the site. No heroic Harry, confetti, promotional CTA, or autoplay music.

**Proposed container sequence:**

1. `ReverentHero`: candlelight, subtle water reflection, medallion, “Forever Hi-Tides.”
2. `MemorialNames`: accessible name list, optional approved portrait/tribute, gentle reveal.
3. `TributeContext`: how names will be honored at the reunion.
4. `SubmitAName`: structured request form rather than a mailto-only path; verification and family sensitivity language.
5. `SupportContact`: private committee contact and correction process.

**Harry/media plan:** if present, Harry appears once in a respectful still—head bowed, hat off, hand over heart. No character video is required.

**Logic preserved:** dynamic memorial feed, empty state, manual committee verification, name-reading promise.

### 8. Time Capsule — a letter across thirty years

**Current purpose:** Capture private reflections and email them back on reunion day.

**Proposed container sequence:**

1. `CinematicHero`: Harry seals a silver-and-crimson envelope; wax and medallion macro shot.
2. `PrivacyPromise`: committee does not read/share responses; delivery date and deletion route.
3. `CapsuleForm`: email, song, person, memory; optional “message to future me” framing.
4. `SealInteraction`: tactile but accessible seal animation after successful storage.
5. `SuccessScene`: delivery date, confirmation email, return-home link.
6. `PromptCards`: inspiration moved below or into expandable help so it does not interrupt submission.

**Harry/media plan:** 3–5 second sealing animation and matching still; no constant animation behind the letter.

**Logic preserved:** queued delivery, private content promise, deletion request, send date, anti-spam controls.

### 9. Soundtrack — the class record

**Current purpose:** Present the reunion playlist and collect song suggestions.

**Proposed container sequence:**

1. `CinematicHero`: record drops, neon equalizer, Harry at a DJ booth.
2. `NowPlayingStage`: Spotify embed or approved playlist with consent-aware activation.
3. `TrackChapters`: senior year, slow set, South Florida sound.
4. `SuggestionFlow`: track autocomplete/plain text, why it matters, name, optional dedication.
5. `CommunityQueue`: approved suggestions and voting later if desired.
6. `SuccessScene`: track received, share the playlist.

**Harry/media plan:** short DJ motion loop, animated record/equalizer using CSS where possible, one hero film—not several heavy videos.

**Logic preserved:** playlist activation, song suggestion endpoint, optional name/reason.

### 10. 404 — lost at sea

**Current purpose:** Recover visitors from an invalid route.

**Proposed containers:** full-height but lightweight Harry scene; “Lost at sea?”; Home, RSVP, and Tickets actions; optional search/menu.

**Harry/media plan:** realistic confused Harry holding a reversed program or looking at a compass; static WebP is sufficient.

**Critical implementation item:** configure Netlify/server routing so the custom 404 is actually served. The August 11 proof showed the host's plain 404 response.

## Form and attendee-journey unification

The forms should no longer feel unrelated. The experience should communicate one lifecycle:

```text
Class Check-In (optional early interest)
        ↓
RSVP (attendance record)
        ↓
Ticket order / payment
        ↓
Meal preference
        ↓
Reunion-ready confirmation
        ↓
Memories · playlist · capsule · referrals
```

### Required experience rules

- Email is the durable cross-form matching key, but the frontend must never expose another attendee's data.
- Visitors should understand whether they are expressing interest, formally RSVPing, reserving tickets, or paying.
- A returning guest should not need to retype basic identity information on every page where a secure lookup/session can be provided.
- Success screens must state what was saved, whether an email was sent, and the next required action.
- Forms autosave locally where safe; private data should not persist indefinitely in browser storage.
- Every submission has loading, success, validation, network failure, duplicate, and retry states.
- Mobile input types, autocomplete attributes, touch targets, keyboard order, and error summaries are treated as functional requirements.

## Harry character and asset blueprint

### Canonical character layers

1. **Cinematic Harry:** OpenArt character reference plus approved ultra-real images and Kling video. Used for heroes and story moments.
2. **Editorial Harry:** clean realistic stills with negative space for layouts, emails, social posts, and cards.
3. **Utility Harry:** lightweight transparent images or legacy cartoon poses for help, empty states, and tiny confirmations.

### New image set

- Lobby welcome with medallion.
- Clipboard/RSVP podium.
- Ticket/red-carpet presentation.
- Banquet maître d'.
- Yearbook/archive vault.
- Respectful memorial pose.
- Wax-sealing capsule.
- DJ/soundtrack booth.
- Confused compass/404.
- Success salute and “you are reunion-ready.”

### New motion set

- 9–15 second homepage trailer derived from the approved OpenArt cut.
- 3–5 second RSVP clipboard loop.
- 3–5 second ticket reveal.
- 3–5 second medallion/era transition.
- 3–5 second wax-seal animation.
- 3–5 second DJ/record loop.
- One 15–30 second shareable reunion campaign trailer assembled from the strongest shots in Remotion with branded HTML-safe title treatments.

## Mobile blueprint

- Hamburger navigation is the primary mobile navigation; the medallion becomes its branded control.
- Keep the current action in a thumb-reachable `ActionDock` when appropriate.
- Heroes use 9:16 or 3:4 crops with protected text-safe regions.
- No content container uses a viewport-height minimum unless it is the opening hero.
- Forms are single column; choice cards become large stacked buttons.
- Order/selection summary is a collapsible sticky sheet.
- Timeline uses horizontal snap only when keyboard and screen-reader alternatives remain available.
- Video defaults to poster on constrained connections and reduced-data environments.
- Avoid scroll-jacking; cinematic snap behavior becomes an enhancement for large screens only.

## Delivery architecture

The current static HTML can be improved incrementally, but the final system should have one source for global components and content configuration. Two safe paths are available:

1. **Incremental componentization:** shared HTML fragments/templates plus modular CSS and JS, preserving the current PHP endpoints and deployment topology.
2. **Frontend application migration:** a small component-based frontend that emits static pages, while the PHP API remains the backend.

Recommended path: start with incremental componentization to avoid breaking live forms, then decide whether the proven components should graduate into FAMtastic Site Studio.

## Implementation sequence

### Phase 0 — protect the working site

- Capture current production URLs, form endpoints, configuration, and visual baselines.
- Add end-to-end synthetic submissions for RSVP, survey, tickets, menu, memory, capsule, playlist, and sponsor inquiry.
- Verify emails, database records, uploads, duplicate behavior, and failure states.

### Phase 1 — design system and global shell

- Build tokens, spacing scale, type system, surfaces, form styles, buttons, motion rules, header, hamburger/medallion navigation, footer, status region, and reduced-motion mode.
- Eliminate repeated global markup and the full-page dead-space pattern.

### Phase 2 — transactional journey

- Rebuild RSVP, ticket order, meal selection, and survey/check-in first.
- Clarify lifecycle states and next actions.
- Preserve endpoints while introducing shared `FormScene`, `ChoiceCard`, `JourneyProgress`, and `SuccessScene` components.

### Phase 3 — cinematic homepage

- Integrate the approved Harry movie as a poster-first hero.
- Add essential event information, journey routing, legacy preview, and dynamic proof.

### Phase 4 — story and community pages

- Through the Years, Soundtrack, Time Capsule, Memorial, and their submission experiences.
- Populate the timeline before presenting it as complete.

### Phase 5 — character/media production

- Produce the canonical Harry still set and short motion set.
- Run character-consistency, brand, accessibility, performance, and mobile crop QA.

### Phase 6 — proof and rollout

- Desktop/mobile browser QA.
- Reduced-motion, keyboard, screen-reader, slow-network, empty-state, API-failure, and upload-rejection QA.
- Staging committee review, analytics validation, rollback proof, then production.

## Acceptance criteria

- Every public route is built from the defined shared container system.
- No unintentional desktop or mobile dead zones exceed the content's visual purpose.
- Every form works with JavaScript success, validation failure, network failure, and duplicate submission behavior tested.
- Survey/check-in and RSVP have unambiguous roles.
- Ticket status accurately distinguishes reservation, payment pending, and paid.
- The designed 404 is served in production.
- Harry's face, crest, shirt, cape, proportions, medallion, and footwear remain consistent across approved realistic assets.
- Critical information and forms work without video.
- Motion respects reduced-motion and does not obstruct reading or interaction.
- Mobile navigation is conventional, extendable, and thumb-friendly.
- All dynamic lists have meaningful loading, empty, error, and populated states.
- Page performance budgets are met after adding video and high-resolution imagery.

## Immediate build recommendation

Build a vertical slice before converting the entire site:

1. Global shell and hamburger/medallion navigation.
2. Homepage cinematic hero using the completed OpenArt movie.
3. Rebuilt RSVP journey with the new form components and success scene.
4. Mobile and reduced-motion QA.

That slice proves the visual system, Harry media strategy, component architecture, form migration technique, and performance approach. Once approved, the remaining pages become disciplined component assembly rather than independent redesigns.
