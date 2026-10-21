# Hi-Tide Harry — OpenArt Movie Trilogy

Status: `locally QA-passed` · Created: 2026-08-20 · Owner: MBSH 96 Reunion social campaign

## Output contract

Three vertical, social-ready character movies. Each is an OpenArt result—not a still frame or a mock animation—and includes native AAC audio.

| # | File | Story beat | Technical proof | OpenArt history ID |
| --- | --- | --- | --- | --- |
| 1 | `01-grand-return.mp4` | Grand Return | 720×1280 · 24 fps · 5.041667 s · AAC audio · SHA-256 `6f4c22a343ff013819a093872be909b64bbe8b65c8befd502fb34f05a29e9c66` | `3cqUoujPCHABl63OcY3D` |
| 2 | `02-roll-call-walk.mp4` | Roll Call Walk | 720×1280 · 24 fps · 5.041667 s · AAC audio · SHA-256 `dabfcfca68b3d3332cb8407eb6db05342656011915e6c3f873f241faa62513ee` | `lF4Dl8bVSJ8OubNEdBa5` |
| 3 | `03-memory-opens.mp4` | Memory Opens | 720×1280 · 24 fps · 5.041667 s · AAC audio · SHA-256 `81aab5e0b6abf5618184282f1d066d2eca79947f49aec7aeda49cf4bb6b37f62` | `Gq8T7alonjrDDxH1lNXA` |

The companion `.jpg` files are midpoint review frames, extracted at 2.5 seconds. They are inspection evidence only; use the MP4s for publishing.

## Build DNA

| Field | Value |
| --- | --- |
| Provider | OpenArt |
| Model | `kling-3-omni` (Kling 3 Omni) |
| Mode | `element2video` — character/identity reference, new scene generation |
| Reference | OpenArt upload `01-hi-tide-harry-ultra-real-4x5.png` (1122×1402 PNG) |
| Configuration | `generateSound: true`, `resolution: std`, `aspectRatio: 9:16`, `duration: 5`, `multiShot: false`, `videoCount: 1`, `creationMode: element` |
| Cost | 175 OpenArt credits per video; 525 credits for this completed three-video set |
| Run issue | The first parallel attempt exceeded OpenArt’s two-render concurrency limit and did not create a billable result. It was submitted after one slot cleared. |
| Verification | `ffprobe` confirmed H.264 video + AAC audio in every asset; individual SHA-256 values recorded above; frame review performed at 2.5 seconds. |

## Shared identity prompt

> Use the provided image only as the identity reference. Preserve Hi-Tide Harry exactly: friendly red skin, swept flame-red forelock, pointed ears, oversized warm expressive eyes, compact proportions, silver cape with metal clasps, white shirt with the existing red two-line Hi-Tide Harry mark, red trousers, and black-and-red leather sneakers. Premium cinematic 3D character animation with rich wine red, black, antique gold, and silver materials; physically believable cape and cloth movement. No added words, captions, signage, logos, watermarks, other characters, or dialogue.

## Scene prompts

### 01 — Grand Return

> Scene: Harry strides through deep red theater curtains onto a glossy red-carpet entrance under a grand but unmarked marquee light. Low angle camera rises slightly as he stops center frame, gives a welcoming wave, and his silver cape makes one graceful billow. Refined crowd silhouettes stay fully out of focus and non-identifiable behind velvet ropes; no readable signs. Native sound: distant appreciative applause, cape movement, and one triumphant orchestral finish; no speech.

### 02 — Roll Call Walk

> Scene: Harry confidently walks toward camera down a luxurious wine-red theater hallway with unmarked glowing frames, black-and-silver decorative rails, and warm pools of light. The camera tracks backward smoothly at his pace; his silver cape settles naturally, he gives one warm inviting hand gesture at the end. The setting is clearly a cinematic reunion invitation, never a branded campus. Native sound: elegant heel taps, a quiet rhythmic clap pulse, and warm orchestral percussion; no speech.

### 03 — Memory Opens

> Scene: in an elegant, unbranded midnight theater lounge, Harry kneels beside a brushed-silver memory case on a small velvet pedestal. He gently lifts a worn blank photo toward a warm copper spotlight, looks at it, then smiles toward camera. Slow cinematic push-in from medium-wide to intimate medium shot; floating dust motes, polished dark floor reflections, soft depth of field. Native sound: a subtle old projector tick, soft cape rustle, then a restrained instrumental swell; no spoken words.

## Publishing boundary

This package is a campaign-asset source set. It has not been deployed, attached to the live MBSH site, scheduled to social platforms, or pushed to the remote repository in this run.
