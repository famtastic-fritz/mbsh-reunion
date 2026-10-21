# Hi-Tide Harry — Memory, Roll Call, Return

**Skill:** `muapi-character-story-video`  
**Run date:** 2026-08-20  
**Current state:** `scene_preview_ready_human_animation_approval_required`

## Character and continuity contract

- Established character source:
  `../muapi-instagram-post-test-20260820/red-carpet/6330a2a4d44e49a693a4588f7cc42794.jpg`
- Uploaded MUAPI reference:
  `https://cdn.muapi.ai/outputs/generated/017a385fb7ac43e0ad1cc75bf7884b06.jpg`
- Scene generator: `nano-banana-2-edit`
- Scene format: 4:5, 928 x 1152
- Scene-generation cost: $0.18 total; balance after scene generation: $17.47
- All three scenes were visually screened for character continuity, malformed
  anatomy, and unintended readable text. All pass the scene-preview gate.

## Part 1 — Memory

Harry kneels beside a silver memory case and discovers the photograph that
starts the return.

- Output: `scene-01-memory/7c2783d2ca0943329835b9ff7a478242.jpg`
- SHA-256: `837a249e403f6563af86b382c01fbad05b00f7bcece25ec3cc04b5f47c4264e4`
- Animation instruction: `Cinematic animation of the scene. Harry studies the
  photograph, warm spotlight dust slowly drifts, and the camera makes a subtle
  intimate push-in. Preserve the established 3D character identity and no new
  text.`

## Part 2 — Roll Call

Harry carries the megaphone down the memory-lined corridor; the reunion call is
building.

- Output: `scene-02-roll-call/96fed978d8824d8d826492984f1b505f.jpg`
- SHA-256: `3ac399ce74604222d11acfa9acdb07feaa5a4666338b382bbe56e42c945bb780`
- Animation instruction: `Cinematic animation of the scene. Harry takes two
  confident steps forward, the silver cape shifts naturally, hallway lights
  warm in sequence, and the camera tracks backward smoothly. Preserve the
  established 3D character identity and no new text.`

## Part 3 — Return

Harry reaches the red-carpet theater entrance and personally welcomes the
viewer back.

- Output: `scene-03-return/18952de3c9c84bf2810fbaec6ef804a3.jpg`
- SHA-256: `21630f4600c8f485063d10379b5077e7474f49f63bc2f4f33627ebe6ffa466d8`
- Animation instruction: `Cinematic animation of the scene. Harry gives a
  warm open-hand welcome, his cape moves in a soft breeze, marquee bulbs bloom
  gently, and the camera settles into a proud final frame. Preserve the
  established 3D character identity and no new text.`

## Approval boundary

The next step is three image-to-video jobs through
`kling-v3.0-pro-image-to-video`, five seconds each. The model supports optional
generated audio. For this first story test, use visual-only clips and lay in the
approved reunion audio bed through HyperFrames after output QA; that protects
voice/music quality and keeps the audio system interchangeable. Do not submit
the animation jobs until a human approves these exact three scene frames.
