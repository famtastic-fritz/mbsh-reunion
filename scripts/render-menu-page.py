#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FRONTEND = ROOT / 'frontend'
TEMPLATE = FRONTEND / 'templates' / 'simple-page-template.html'
CONTENT = FRONTEND / 'page-content' / 'menu.content.html'
OUTPUTS = [
    FRONTEND / 'menu' / 'index.html',
]

replacements = {
    '{{PAGE_DESCRIPTION}}': "Food and location information for the MBSH Class of '96 Reunion Mixer at Twisted Tonic.",
    '{{PAGE_TITLE}}': "Food & Location — MBSH Reunion Mixer",
    '{{OPTIONAL_PAGE_CSS}}': '  <link rel="stylesheet" href="/css/mixer.css?v=mixer1">',
    '{{PAGE_KEY}}': 'menu',
    '{{OPTIONAL_HEADER_MODIFIER}}': '',
    '{{PAGE_HEADING}}': 'Food & Location',
    '{{OPTIONAL_TITLE_MODIFIER}}': '',
    '{{PAGE_SUBHEAD}}': 'Twisted Tonic · November 7, 2026 · 7–10 PM · Food on your own.',
    '{{OPTIONAL_SUB_MODIFIER}}': '',
    '{{PAGE_CONTENT}}': CONTENT.read_text(),
    '{{OPTIONAL_PAGE_JS}}': '  <script src="/js/page-sequence.js?event=mixer20261016" defer></script>\n  <script src="/js/premiere.js?event=mixer20261016" defer></script>',
}

html = TEMPLATE.read_text()
for key, value in replacements.items():
    html = html.replace(key, value)

missing = [key for key in replacements if key in html]
if missing:
    raise SystemExit(f'Unreplaced template tokens remain: {missing}')

for output in OUTPUTS:
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(html)
    print(f'rendered {output}')
