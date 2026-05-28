# MBSH Reunion Cerebrum

## 2026-05-14 — RSVP hero Phase 2 learning

RSVP’s accepted hero grammar is a movie-premiere check-in desk, not a generic theater header. The visual story must communicate attendance confirmation before copy is read: guest list, velvet rope, RSVP card, seat planning, and Harry actively inviting the visitor to confirm their seat.

Implementation learning: scroll targets nested inside section wrappers should not use `offsetTop`; use `target.getBoundingClientRect().top + window.pageYOffset` plus a small sticky-nav offset so click landings are reliable. Using `offsetTop` landed too early on `#rsvp-form`, which kept `.rsvp-form-wrap.premiere-snap-in` below the IntersectionObserver threshold and made the form look missing.

Composition learning: if a hero is meant to bridge directly into a form, do not inject interstitial note/countdown panels between the hero and form. RSVP now skips the old note-panel injection and moves countdown after the form so the red-carpet bridge lands on the actual RSVP action.

## 2026-05-14 — RSVP hero revB dual-variant learning

RSVP revB reframed the hero output as a preview-only style decision before wiring: one shared background plate, one shared scene-marker plaque, and two transparent Harry variants composed side by side. This pattern protects the live site from premature art-direction choices while still testing the full `cinematic-interior-hero` recipe at composition size.

Generation learning: BiRefNet model loading hung in this local environment, so the skill-approved `isnet-general-use` fallback was used with the same alpha-matting flags (`foreground_threshold=240`, `background_threshold=10`, `erode_size=2`). The fallback produced valid RGBA alpha ranges for both revB Harry variants.

Mobile composition learning: the desktop `cinematic-interior-hero` layout does not automatically preserve readable copy at narrow widths. The revB preview keeps Harry and the marker readable, preserves the chevron/bleed grammar, and hides the support line in the mobile preview so the title does not clip.
