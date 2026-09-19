# MBSH Class of 1996 Reunion: design contract

This versioned contract records the actual selected design inputs. It is not automatic owner approval. Preserve authored design choices during rebuilds; record a versioned change when direction changes.

```json
{
  "schema_version": 1,
  "source": "existing_authored_design",
  "references": [
    "docs/architecture/EVENT_CINEMA_ARCHITECTURE.md",
    "frontend/css/base.css"
  ],
  "source_revision": "5ed9a49314aac4195354094b56c1e1abe2603d4d",
  "preserve": "Keep authored page shell, brand tokens, responsive composition, backend and privacy boundaries. This foundation adds source governance, not a redesign or new owner approval."
}
```

## Responsive and accessibility acceptance

The last normal-flow row is the centered creator credit from
`frontend/templates/creator-credit.html`: exact approved PNG, 160px wide,
responsive max-width, 44px minimum link target, neutral obsidian background.
It is additional to the shared event footer, not a replacement for it; it remains
present without JavaScript. Preserve all reunion design, content and commerce.
Only an explicit owner exception removes it. Link attribution uses the public
site slug, without cookies/scripts or personally identifiable parameters.

September 19 refinement: this is a compact signature, not a second footer.
Default row padding is16px top/24px bottom. MBSH alone reserves160px below
600px on pages containing #chatbot for fixed Harry/help controls; pages without
Harry retain compact spacing. That exception is not a fleet default.
Preserve the44px link target and verify the visible greeting cannot cover it.

Verify 390, 768 and 1280 pixel layouts, keyboard navigation, readable contrast, reduced motion and form error states. Preserve asset rights and provenance.
