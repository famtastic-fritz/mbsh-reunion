# Build DNA — Hi-Tide Harry OpenArt Ad Set

## Provenance

| Layer | Source of truth |
| --- | --- |
| Character movie source | `frontend/assets/social/2026-reunion-hype/openart-video/hi-tide-harry-movie-trilogy-20260820/BUILD-DNA.md` |
| Character/video provider | OpenArt — Kling 3 Omni, `element2video` mode |
| Movie inputs | One approved Hi-Tide Harry character reference plus three prompt-defined scenes |
| Movie output | Three 720×1280 H.264/AAC clips, 5.041667 seconds each |
| Overlay treatment | Deterministic HTML/CSS/GSAP (HyperFrames) and React/Remotion markup |
| Creative authority | `docs/creative/HI_TIDE_HARRY_CAMPAIGN_CREATIVE_RECIPE_2026-08-17.md` |

## Ad contract

| Ad | Source movie | Hook | CTA |
| --- | --- | --- | --- |
| Grand Return | `01-grand-return.mp4` | The moment has arrived. | Join the roll call |
| Roll Call Walk | `02-roll-call-walk.mp4` | The roll call is open. | Register today |
| Memory Opens | `03-memory-opens.mp4` | The memories never left. | Come home to ’96 |

The controlled event strip is `MBSH ’96 · NOV 07 2026 · MBSH96REUNION.COM`.
It does not rely on generated imagery for any factual claim.

## Engineering choices

- **HyperFrames:** source media is referenced through project-local relative
  links in `hyperframes/assets/`; video is muted and the same files are mapped
  to dedicated audio tracks, per framework requirements.
- **Remotion:** three source files are deliberately vendored in `public/assets/`.
  A symlink was rejected because the Remotion bundle server returned a 404 for
  the linked source; local copies make the package portable and renderable.
- **Copy:** exact on-screen text and social captions are in `CAMPAIGN-COPY.md`.
- **No final MP4 yet:** final output remains a review-gated action. No campaign
  was published, scheduled, deployed, or sent by this build.

## Verification record

| Check | Result |
| --- | --- |
| HyperFrames structural/runtime/layout/motion/contrast gate | PASS — 0 errors, 0 warnings; 21/21 sampled text checks pass WCAG AA |
| HyperFrames midpoint review | PASS — frames captured at 2.5 s, 7.5 s, 12.5 s |
| Remotion source lint + TypeScript | PASS |
| Remotion still smoke renders | PASS — all three ads plus the master trailer rendered from actual local media |

Review imagery is retained under `hyperframes/snapshots/` and `remotion/review/`.
