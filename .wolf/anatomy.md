# MBSH Reunion Anatomy

## 2026-05-14 — RSVP Phase 2 hero assets

Added RSVP-only hero assets under `frontend/assets/heroes/rsvp/`:

- `frontend/assets/heroes/rsvp/01-environment.png` — 1536×1024 RGB background plate for the vintage theater RSVP check-in scene.
- `frontend/assets/heroes/rsvp/02-harry-usher-transparent.png` — 1024×1536 RGBA Tier 1 Hi-Tide Harry usher cutout with verified alpha range `(0,255)`.
- `frontend/assets/heroes/rsvp/03-scene-marker.svg` — hand-authored scalable scene marker plaque reading `SCENE II · INT. AUDITORIUM — NIGHT` and `LOCK YOUR SEAT`.

RSVP composition touchpoints:

- `frontend/js/page-sequence.js` supplies the RSVP hero metadata/copy/asset paths.
- `frontend/js/premiere.js` assembles the layered RSVP hero, skips the RSVP-only old note-panel injection, and wires the bottom chevron to `#rsvp-form`.
- `frontend/css/premiere.css` positions the RSVP environment, Harry, marker, chevron, motion, and bleed-to-form rules.
- `frontend/rsvp.html` now places `.rsvp-form-wrap` immediately after the hero; the countdown follows the form.

## 2026-05-14 — RSVP Phase 2 revB preview assets

Added/updated RSVP revB preview-only assets under `frontend/assets/heroes/rsvp/`:

- `frontend/assets/heroes/rsvp/01-environment.png` — 1536×1024 RGB revB theater check-in background plate with visible red carpet, ropes/stanchions, podium/ledger, warm lobby architecture, and left-side negative space.
- `frontend/assets/heroes/rsvp/02a-harry-f1-mascot-transparent.png` — 1024×1536 RGBA Variant A Harry cutout for the F1/live-action-costumed-mascot comparison, alpha range `(0,255)`.
- `frontend/assets/heroes/rsvp/02b-harry-3d-render-transparent.png` — 1024×1536 RGBA Variant B Harry cutout for the 3D-rendered photoreal comparison, alpha range `(0,255)`.
- `frontend/assets/heroes/rsvp/03-scene-marker.svg` — revB hand-authored marker plaque with smaller SVG text to prevent clipping at composition size.
- `frontend/assets/heroes/rsvp/PREVIEW-A-f1-mascot-composed.png` — 1440×900 composed preview using Variant A.
- `frontend/assets/heroes/rsvp/PREVIEW-B-3d-render-composed.png` — 1440×900 composed preview using Variant B.
- `frontend/assets/heroes/rsvp/preview-revb.html` — preview-only composition harness for side-by-side rendering; not wired into the live RSVP page.

Archived first-draft revA canonical environment/marker assets under `frontend/assets/heroes/rsvp/archive-revA-20260514-145813/` before writing revB canonical paths.
