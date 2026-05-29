# MBSH Premiere Experience — Hero Asset Specification

> **Status**: Placeholder spec — all hero images are PENDING Media Studio generation.
> All `assets/heroes/*.jpg` paths are wired into HTML with `data-hero-asset` attributes
> so Media Studio can locate and swap them in a single generation wave.

---

## Generation Notes — 35mm Cinema Look

Every hero image should evoke a **1990s–2026 Black Miami Beach reunion** through the lens of:

- **Grain**: Subtle Kodak 400 / Fuji Superia film grain overlay (~8–12%)
- **Shadows**: Warm amber shadows, deep blacks, no cold-blue shadow tones
- **Selective focus**: Subject sharp, environment with slight lens-fall-off / vignette
- **Lighting direction**: Golden-hour beach light OR warm ballroom chandeliers OR practical neon
- **Color grade**: Warm, slightly faded (like a well-kept print), saturation ~85%
- **Aspect**: 16:9 desktop master (`1920×1080`); provide a `9:16` crop path for mobile hero
- **Subject**: Black alumni, Miami Beach Senior High school / ballroom / Miami Beach locales, class of 1996 aesthetic

---

## Page Hero Specs

### 1. Home (`index.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-home.jpg` |
| Mobile crop        | `assets/heroes/hero-home-mobile.jpg` |
| Alt text           | "MBSH Class of 1996 — 30th Reunion Premiere. Miami Beach, October 2026." |
| Scene              | SCENE I — The Arrival |
| Mood               | Grand, cinematic arrival. Red-carpet energy. |
| Prompt direction   | "Exterior of a grand 1920s Miami Beach hotel at dusk, warm amber streetlamps, red carpet leading to glowing double doors, film grain, Kodak 400 aesthetic, 35mm cinema still, wide establishing shot, no text" |
| Dimensions         | 1920×1080 (desktop) / 1080×1920 (mobile) |

---

### 2. RSVP (`rsvp.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-rsvp.jpg` |
| Mobile crop        | `assets/heroes/hero-rsvp-mobile.jpg` |
| Alt text           | "A handwritten RSVP envelope sealed in gold wax on a dark mahogany table." |
| Scene              | SCENE II — The Invitation |
| Mood               | Intimate, formal, anticipatory |
| Prompt direction   | "Close-up of a cream-colored formal envelope, gold wax seal stamped with a wave crest, fountain pen resting beside it, dark mahogany surface, candle light, film grain, 35mm shallow depth of field, warm tones" |
| Dimensions         | 1920×1080 / 1080×1920 |
| Note               | rsvp.html already has layered hero environment images (`assets/heroes/rsvp/`). This spec path is for a unified cinematic fallback. |

---

### 3. Tickets (`tickets.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-tickets.jpg` |
| Mobile crop        | `assets/heroes/hero-tickets-mobile.jpg` |
| Alt text           | "Vintage movie-premiere ticket stubs fanned out against a velvet champagne background." |
| Scene              | SCENE III — The Ticket Booth |
| Mood               | Exciting, premium, theatre-premiere energy |
| Prompt direction   | "Fan of vintage theater ticket stubs with gold foil lettering on champagne velvet, soft spotlight from above, film grain, 35mm macro still, Art Deco border motif, no digital elements" |
| Dimensions         | 1920×1080 / 1080×1920 |

---

### 4. In Memory (`memorial.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-memorial.jpg` |
| Mobile crop        | `assets/heroes/hero-memorial-mobile.jpg` |
| Alt text           | "A single white candle glowing in the dark with a bouquet of flowers — a memorial scene." |
| Scene              | SCENE VI — In Memoriam |
| Mood               | Quiet, reverent, warm grief — not cold or mournful |
| Prompt direction   | "A single white candle burning steadily on a dark stone surface, warm amber glow, soft bokeh flower petals, film grain, 35mm cinema still, deep blacks, no text, shallow depth of field, one small pool of golden light" |
| Dimensions         | 1920×1080 / 1080×1920 |

---

### 5. Playlist (`playlist.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-playlist.jpg` |
| Mobile crop        | `assets/heroes/hero-playlist-mobile.jpg` |
| Alt text           | "A vintage 1990s boombox and vinyl records on a Miami rooftop at sunset." |
| Scene              | SCENE IV — The Soundtrack |
| Mood               | Nostalgic, energetic, Black 90s music culture |
| Prompt direction   | "A vintage 1990s boombox and stacked vinyl records on a Miami Beach rooftop, golden sunset, city skyline bokeh behind, warm orange-pink sky, film grain, 35mm street photography aesthetic, no text, Black cultural aesthetic" |
| Dimensions         | 1920×1080 / 1080×1920 |

---

### 6. Time Capsule (`capsule.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-capsule.jpg` |
| Mobile crop        | `assets/heroes/hero-capsule-mobile.jpg` |
| Alt text           | "A weathered metal time capsule box partially unearthed from sandy Miami soil." |
| Scene              | SCENE V — The Capsule |
| Mood               | Mysterious, nostalgic, treasure-reveal energy |
| Prompt direction   | "A worn metal time capsule box with a brass latch, partially dug up from sandy soil, warm afternoon light catching the surface, letters and photos spilling from inside, 35mm film aesthetic, Kodak grain, warm earth tones" |
| Dimensions         | 1920×1080 / 1080×1920 |

---

### 7. Through the Years (`through-years.html`)

| Field              | Value |
|--------------------|-------|
| Asset path         | `assets/heroes/hero-through-years.jpg` |
| Mobile crop        | `assets/heroes/hero-through-years-mobile.jpg` |
| Alt text           | "A wall of MBSH yearbook photos spanning 1926 to 2026 — the sweep of history." |
| Scene              | SCENE VII — The Years |
| Mood               | Epic, sweeping, emotional — the full 100-year arc |
| Prompt direction   | "A collage wall of Black-and-white and color school portraits spanning 1926 to 2026, fading from sepia to Kodachrome to digital, Miami Beach Senior High hallway perspective, film grain, warm amber light, 35mm wide angle, deep emotional weight" |
| Dimensions         | 1920×1080 / 1080×1920 |

---

## Asset Status Tracker

| Page           | Desktop Asset                          | Mobile Asset                                   | Status   |
|----------------|----------------------------------------|------------------------------------------------|----------|
| Home           | `assets/heroes/hero-home.jpg`          | `assets/heroes/hero-home-mobile.jpg`           | PENDING  |
| RSVP           | `assets/heroes/hero-rsvp.jpg`          | `assets/heroes/hero-rsvp-mobile.jpg`           | PENDING* |
| Tickets        | `assets/heroes/hero-tickets.jpg`       | `assets/heroes/hero-tickets-mobile.jpg`        | PENDING  |
| In Memory      | `assets/heroes/hero-memorial.jpg`      | `assets/heroes/hero-memorial-mobile.jpg`       | PENDING  |
| Playlist       | `assets/heroes/hero-playlist.jpg`      | `assets/heroes/hero-playlist-mobile.jpg`       | PENDING  |
| Time Capsule   | `assets/heroes/hero-capsule.jpg`       | `assets/heroes/hero-capsule-mobile.jpg`        | PENDING  |
| Through Years  | `assets/heroes/hero-through-years.jpg` | `assets/heroes/hero-through-years-mobile.jpg`  | PENDING  |

`*` RSVP has partial layered hero images at `assets/heroes/rsvp/` — needs cinematic master hero.

---

## Media Studio Routing

When triggering Media Studio generation:
1. Use each page's prompt direction above verbatim as the base prompt.
2. Append to every prompt: `"photorealistic, 35mm cinema film still, Kodak 400 grain, warm shadows, Black cultural aesthetic, Miami Beach, 2026 reunion, no text overlay, no watermark"`
3. Generate desktop (1920×1080) first, crop to mobile (1080×1920) centered on subject.
4. Review contact sheet before wiring into HTML.
5. Run a `data-hero-asset` grep to auto-locate all placeholder injection points.

```bash
grep -r "data-hero-asset" frontend/ --include="*.html"
```
