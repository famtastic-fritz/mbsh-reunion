# MUAPI Instagram-post skill test

**Run date:** 2026-08-20  
**Purpose:** Test the `muapi-instagram-post` recipe against the established
Hi-Tide Harry visual system, using reference edits rather than an unrelated
text-only character generation.  
**Provider/model:** MUAPI / `nano-banana-2-edit`  
**Format:** 4:5 portrait, 928 x 1152  
**Credential storage:** local OS keychain; no key is stored in this repository.

## Cost and run facts

- Account balance before: $17.77
- Account balance after: $17.65
- Observed test cost: **$0.12 total** for two reference edits
- Source imagery was uploaded to MUAPI solely to supply the two reference-edit
  jobs. The inputs and outputs are recorded below.

## Test A — registration red carpet

| Field | Value |
| --- | --- |
| Input | `frontend/assets/social/2026-reunion-hype/gemini-lite-social-kit-20260820/images/01-red-carpet-roll-call.jpg` |
| MUAPI reference URL | `https://cdn.muapi.ai/outputs/generated/1530f8c32c4f43128cabf7f31e2096b3.jpg` |
| Output | `red-carpet/6330a2a4d44e49a693a4588f7cc42794.jpg` |
| SHA-256 | `68b7e05d81b575c5171f3d5aea24dcfab6edbabebcc1429ae6912d725fa9b32f` |
| QA decision | **Pass for creative review** — character identity, elevated materials, and the shirt wordmark hold. Add deterministic copy before publishing. |

**Prompt (verbatim):**

> Create a premium 4:5 Instagram feed hero by refining the supplied Hi-Tide Harry red-carpet source image. Preserve the exact friendly red-skinned mascot identity, swept crimson forelock, bright warm eyes, silver cape, red trousers, black-and-red leather sneakers, and the clean two-line red Hi-Tide Harry shirt wordmark already present in the source. Make the red carpet feel like an exclusive thirty-year reunion premiere: deep true blacks, saturated scarlet velvet, rich wine-red shadows, burnished copper-gold practical lights, brushed-metal cape clasps, polished leather grain, sharp fabric weave, high-contrast cinematic grade, and restrained 35mm grain. Keep a calm, dark, uncluttered upper-left field for deterministic copy overlay. Full body, confident invitation gesture, sharp focus, premium social photography aesthetic. Do not add any visible words, dates, logos, badges, watermarks, or new characters; keep only the shirt wordmark.

**Deterministic overlay:** `THE CALL IS OUT.` — upper-left.  
**Caption:**

> THE CALL IS OUT. 🎬  
> Thirty years of story deserves a grand entrance.  
> MBSH ’96, your place on the roll call is waiting. Register through the link in bio.

**Hashtags:**

`#MBSH96 #MBSHReunion #ClassOf1996 #ReunionSeason #AlumniReunion #HiTideHarry #RollCall #ClassReunion #ReunionVibes #AlumniCommunity #BlackAlumni #ReunionReady #ComeHome #LegacyInMotion #ThirtyYears #RedCarpetMoment #ClassOf96 #CelebrateTogether`

## Test B — time-capsule nostalgia

| Field | Value |
| --- | --- |
| Input | `frontend/assets/social/2026-reunion-hype/gemini-lite-social-kit-20260820/images/05-time-capsule.jpg` |
| MUAPI reference URL | `https://cdn.muapi.ai/outputs/generated/68bd5fb184974a8aa8f0a7ba0d2891dc.jpg` |
| Output | `time-capsule/ce667aeb8e9f49a5ae2f68379dc920cc.jpg` |
| SHA-256 | `8de49181d4586bce416803f9b4634ff031661b708769d324abfefc9af9799141` |
| QA decision | **Fail for publishing** — the visual is strong, but it invented readable `alumni reunion` text on a prop despite the prompt’s exclusion. It needs a deterministic erase/repair pass before use. |

**Prompt (verbatim):**

> Create a premium 4:5 Instagram feed hero by refining the supplied Hi-Tide Harry time-capsule source image. Preserve the exact friendly red-skinned mascot identity, swept crimson forelock, bright warm eyes, silver cape, red trousers, black-and-red leather sneakers, and the clean two-line red Hi-Tide Harry shirt wordmark already present in the source. Turn the moment into an emotional alumni memory reveal: a brushed-silver time capsule, aged reunion photographs with no readable writing, a warm pool of copper light, deep black-to-wine-red background, tactile paper fibers, subtle dust motes, believable cape metal and shirt-knit detail, luxurious cinematic lighting, dense but elegant material depth, and restrained film grain. Compose the mascot on the lower-right and reserve an uncluttered shadowed upper-left field for deterministic social copy. Do not add visible copy, dates, school logos, badges, watermarks, extra characters, or malformed hands.

**Proposed overlay after repair:** `SOME MEMORIES NEVER LEFT.` — upper-left.  
**Caption:**

> Some memories never left. They just waited for the roll call.  
> What MBSH ’96 moment takes you right back?  
> Share it, then come home for the reunion.

**Hashtags:**

`#MBSH96 #MBSHReunion #ClassOf1996 #AlumniStories #MemoryLane #TimeCapsule #HiTideHarry #ClassReunion #AlumniCommunity #ThenAndNow #ThirtyYears #ReunionMemories #LegacyLives #ComeHome #RollCall #ShareYourStory #ReunionSeason #ClassOf96`

## What this proves

MUAPI can keep a supplied character fairly consistent while generating a
polished social-ready image quickly and at a low observed test cost. It is not
a substitute for deterministic copy or asset QA: all generated readable text,
including text on props, must be screened before publication. The robust lane
is: reference edit → visual/text QA → deterministic HTML or design-system
overlay → human approval → publishing.
