---
workflow: general-video
flow: automation
storyboard: no
message: "The MBSH reunion is an invitation to come back into the story."
destination: "Instagram Reels, TikTok, Facebook Reels, and vertical Stories"
aspect: portrait
language: en
audience: "MBSH Class of 1996 alumni and the wider Hi-Tide alumni community"
length: "Three short sound-on promotional cuts, approximately 12 to 18 seconds each"
angle: "Premium red-carpet reunion invitation led by Hi-Tide Harry"
---

## Intent

Create a reusable MBSH social campaign kit around the approved Hi-Tide Harry
identity. The visual promise is deep black, rich red, silver, and warm gold;
the emotional promise is a personal invitation to return, reconnect, and be
counted in the roll call.

## Assets

- Nine Gemini Lite 2K social stills, generated from the approved character
  identity reference and high-fidelity wordmark/material reference.
- Two retained comparison candidates for future web use: a GPT Image 2
  invitation pose and a Gemini Lite invitation pose with the exact shirt
  wordmark.
- Authoritative image package:
  `../../../frontend/assets/social/2026-reunion-hype/gemini-lite-social-kit-20260820/`.
- The current draft audio uses an existing owned reunion-teaser bed; no new
  music, voiceover, or sound effects are staged yet.

## Customizations

- Use deterministic HTML typography for campaign copy; do not ask image models
  to generate post captions, dates, calls to action, or logos.
- Every Harry image keeps the red two-line `Hi-Tide Harry` shirt wordmark.
- The three video cuts use the same asset family but must have different arcs:
  invitation, memory, and final roll call.

## Inferred run decisions

- The user requested creation without a storyboard review, so this is an
  automation run with a final preview gate before rendering.
- The posts are designed with protected upper-left copy space so the same still
  can feed organic social, paid social, and on-site campaign modules.
- Audio is a required final layer. HyperFrames authentication currently reports
  no HeyGen session and its offline voice/music engines are not installed;
  therefore no new voice or music should be synthesized until an authenticated
  or locally provisioned audio provider is available. The first three cuts use
  the existing owned reunion-teaser audio bed, copied into this project and
  recorded in the media manifest; it is not represented as newly generated
  music or narration.
