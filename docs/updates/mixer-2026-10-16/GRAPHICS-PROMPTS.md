# Mixer graphic provenance

Generated October 16, 2026 with the built-in `image_gen` tool. The tool did not expose its selected model identifier; do not invent one. No CLI fallback or third-party image provider was used. The generated masters are illustrative Miami scenes, not venue photographs or a claim about the booked room. Final typography and the exact existing seal/creator logo are deterministic overlays in `scripts/render-mixer-graphics.mjs` using Sharp 0.35.5.

The approved reference is `frontend/assets/mascot/01-wave-hello.png`. The original seal is `frontend/assets/brand-mark/brand-mark.png`; it is placed unchanged with a circular display mask. Originals and previous artwork remain intact.

Applied specialist reference: `design/design-visual-storyteller`, pinned upstream revision `765be42358100bf89d2faa567668a94c602f9a26`. Guidance applied directly; no specialist agent or profile approval is claimed.

## Landscape master — exact submitted prompt

Use case: ads-marketing. Create a polished cinematic landscape website background, wide 16:9 composition, for a Miami Beach high school reunion mixer. The attached image is the exact Hi-Tide Harry character reference, not a target to overwrite. Preserve the recognizable red skin, swept red forelock, pointed ears, friendly big eyes, white shirt with existing Hi-Tide Harry lettering, red trousers, black shoes and silver cape. Render this same friendly character as a premium tactile 3D practical mascot, relaxed and welcoming, standing in the RIGHT third of the image, fully visible with room around head and hands. South Beach evening terrace atmosphere with warm gold string lights, subtle palm silhouettes, soft coastal-night reflections, refined dark red and charcoal architecture, beautifully cinematic amber lighting, inviting and nostalgic rather than a nightclub rave. Keep the LEFT half dark and quiet with negative space for separately typeset headline. The setting is an illustrative Miami evening, NOT a photograph of Twisted Tonic and NOT a claim of an exact booked area. Do not add a logo or medallion, event text, signage, drinks, tuxedo, black-tie clothing, people, duplicates, confetti, or watermarks. Create only the background scene with Harry; editorial typography and the original reunion seal will be applied separately. Exact character identity, premium photographic materials, warm smile.

Output: `frontend/assets/mixer-2026/source/miami-evening-harry-wide-v1.png`. Generation file: `exec-9beea3ac-c156-49db-ac36-aa7da12d4410.png`.

## Portrait master — exact submitted prompt

Create a separately composed PORTRAIT 9:16 mobile website hero background based on this approved landscape reunion-mixer scene. Preserve this exact Hi-Tide Harry face, red forelock and ears, white Hi-Tide Harry shirt, silver cape, red trousers and black shoes. Preserve the warm cinematic Miami evening palette, string lights, palms and coastal reflections. Recompose rather than simply cropping: put Harry in the LOWER half, slightly right of center, head around halfway down the canvas, feet safely above the lower edge; keep the upper 40 percent mostly dark charcoal with subtle warm light and plenty of quiet space for separately typeset mobile heading. Harry fully visible, face and waving hand unobstructed. No tuxedo, drinks, event lettering, logos, medallions, other people or watermarks. Illustrative Miami scene, not an exact photo of a booked venue. Premium tactile 3D mascot consistent with input.

Output: `frontend/assets/mixer-2026/source/miami-evening-harry-mobile-v1.png`. Generation file: `exec-131657c9-e3d8-448a-b2ee-77c2fec0d4d3.png`. Reference: the landscape master above.

## Export and review

Run `npm ci`, then `node scripts/render-mixer-graphics.mjs`. Eight graphic exports plus an optimized WebP copy of the wallet artwork. Master PNGs are versioned but excluded from production packaging. The first CSS phone crop clipped Harry; the corrected phone composition places the copy and controls above the full-width scene. Announcement overlays were refined to gradients to remove abrupt panel edges. Neither iteration is owner visual acceptance.
