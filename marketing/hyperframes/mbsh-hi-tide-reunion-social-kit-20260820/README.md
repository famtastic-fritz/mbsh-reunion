# MBSH Hi-Tide Harry social kit

This is the reusable media-production lane for the MBSH 1996 reunion campaign.
It separates creative generation from deterministic copy, motion, QA, and
delivery so a still or video can be reproduced without asking an image model to
spell campaign information.

## What is here

- Nine 2K Gemini Flash Lite campaign stills in `assets/images/`.
- Three 9-second, 1080 x 1920, sound-on HyperFrames compositions:
  - `index.html` — *The Invitation*
  - `compositions/memory.html` — *The Memory*
  - `compositions/roll-call.html` — *The Final Call*
- `assets/audio/mbsh-existing-teaser-bed.m4a` — the existing owned teaser bed,
  reused for the review cuts. It is not newly generated music or narration.
- `MEDIA-MANIFEST.json` — source lineage, expected output, status, and approval
  boundary for this campaign.

## The operating contract

1. Generate the raw campaign stills through the named image lane. The prompt,
   identity references, hashes, duration, token telemetry, and model are kept
   in the authoritative receipt at
   `../../../frontend/assets/social/2026-reunion-hype/gemini-lite-social-kit-20260820/provenance/`.
2. Put all public-facing captions, dates, and calls to action in deterministic
   HTML/CSS. Generated images contain no campaign copy other than the approved
   shirt wordmark.
3. Build a HyperFrames composition from the stills, audio, and deterministic
   typography. Each composition has a motion-spec sidecar.
4. Run `npm run check -- --snapshots --at 1.4,4.45,6.1,8.2` and inspect its
   frames. This verifies runtime, layout, motion, contrast, and mid-cut
   captures.
5. Open the local Studio preview with `npm run dev -- --background --no-open`.
   A human must approve the preview before a final MP4 is rendered.
6. Render the approved composition, record its output hash/duration in the
   manifest, then copy only approved assets into the site or social scheduler.

## Why HyperFrames belongs here

Gemini Lite owns only source imagery. HyperFrames owns the repeatable motion
layer: timing, text correctness, copy layout, audio tracks, snapshot QA, and
the final vertical deliverable. That lets the same nine-source family feed an
on-site campaign module, a one-off static post, and multiple social cuts while
preserving one visual system and its Build DNA.

## Audio capability boundary

The local HyperFrames installation can produce the current sound-on drafts
using the existing owned audio bed. It is not signed into HeyGen, and its
optional local Kokoro/MusicGen engines are not installed, so this kit has no
new generated narration, music, or sound effects yet. A later authenticated
HeyGen or other approved audio-provider pass can replace the bed without
changing imagery, copy, timing, or the QA contract.
