# Hi-Tide Harry — Layered Character Assets

This directory holds the layered PNG assets for the Hi-Tide Harry avatar component.
Each layer is a transparent PNG designed to be stacked via absolute-position CSS.

## Asset List

| Filename                 | Size (recommended) | Purpose                                         |
|--------------------------|--------------------|-------------------------------------------------|
| `harry-body.png`         | 400 × 600 px       | Base character — full body, navy blazer, usher attire |
| `harry-ring-idle.png`    | 400 × 600 px       | Marquee bulb ring — dim/off state               |
| `harry-ring-glow.png`    | 400 × 600 px       | Marquee bulb ring — lit/amber-glowing state     |
| `harry-eyes-idle.png`    | 400 × 600 px       | Neutral forward-facing eyes                     |
| `harry-eyes-chase.png`   | 400 × 600 px       | Eyes shifted left/right (listening-chase state) |
| `harry-mouth-idle.png`   | 400 × 600 px       | Closed or slight smile — neutral                |
| `harry-mouth-speaking.png` | 400 × 600 px     | Open mouth — speaking/animated state            |

## Recommended Color Palette

- **Deep Navy**: `#0d1b2a` — Harry's blazer/uniform base
- **Warm Amber**: `#d97706` — Marquee bulbs (idle glow), pocket square accent
- **Champagne Gold**: `#c9a84c` — Bulb highlights, badge, buttons
- **Ivory / Cream**: `#f5efe6` — Shirt front, glove trim
- **Soft Burgundy**: `#7c2d2d` — Tie accent, MBSH branding detail
- **Ring Glow (lit)**: `#f59e0b` with a warm white center `#fffbf0`

## Layer Order (bottom → top in DOM)

1. `.harry-body` — base character
2. `.harry-ring` — bulb ring (idle PNG by default; glow PNG shown in speaking state)
3. `.harry-face` — face base (part of body PNG or separate face layer)
4. `.harry-eyes` — eye state layer (idle or chase)
5. `.harry-mouth` — mouth state layer (idle or speaking)

## Notes for Media Studio

- All PNGs must have a **transparent background** (alpha channel).
- The character should be positioned in the **lower-left** quadrant of the canvas so
  the ring halo extends behind the head area.
- Ring bulbs should be **individually glowing circles** arranged in a marquee arch
  above/around Harry's head — cinema theater marquee aesthetic.
- Recommended prompt direction: "Hi-Tide Harry, a friendly Black male usher character
  in a deep navy theater uniform, white gloves, champagne gold buttons, standing proud,
  35mm character-design illustration style, transparent background, layered composition."

## Generation Status

- [ ] harry-body.png — PENDING (Media Studio)
- [ ] harry-ring-idle.png — PENDING
- [ ] harry-ring-glow.png — PENDING
- [ ] harry-eyes-idle.png — PENDING
- [ ] harry-eyes-chase.png — PENDING
- [ ] harry-mouth-idle.png — PENDING
- [ ] harry-mouth-speaking.png — PENDING
