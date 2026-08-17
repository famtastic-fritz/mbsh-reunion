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
    '{{PAGE_DESCRIPTION}}': "Gold menu dinner preference survey for the MBSH Class of '96 30th Reunion.",
    '{{PAGE_TITLE}}': "Gold Menu — Dinner Preferences — MBSH Class of ’96",
    '{{OPTIONAL_PAGE_CSS}}': '  <link rel="stylesheet" href="/css/menu.css">',
    '{{PAGE_KEY}}': 'menu',
    '{{OPTIONAL_HEADER_MODIFIER}}': '',
    '{{PAGE_HEADING}}': 'Dinner Preferences',
    '{{OPTIONAL_TITLE_MODIFIER}}': '',
    '{{PAGE_SUBHEAD}}': 'Review the finalized dinner menu and choose your entrée for the evening.',
    '{{OPTIONAL_SUB_MODIFIER}}': '',
    '{{PAGE_CONTENT}}': CONTENT.read_text(),
    '{{OPTIONAL_PAGE_JS}}': '  <script src="/js/menu.js" defer></script>\n  <script src="/js/page-sequence.js" defer></script>\n  <script src="/js/premiere.js" defer></script>',
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
