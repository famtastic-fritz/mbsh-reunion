import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const pages = ['index.html', 'rsvp.html', 'tickets.html', 'menu.html', 'survey.html', 'through-years.html', 'memorial.html', 'capsule.html', 'playlist.html'];
const failures = [];
const canonicals = [];

for (const page of pages) {
  const html = fs.readFileSync(path.join(root, 'frontend', page), 'utf8');
  const required = [
    ['title', /<title>[^<]{10,}[^<]*<\/title>/i],
    ['description', /<meta\s+name="description"\s+content="[^"]{50,}"/i],
    ['canonical', /<link\s+rel="canonical"\s+href="https:\/\/mbsh96reunion\.com\/[^"]*"/i],
    ['open graph title', /property="og:title"/i],
    ['Twitter card', /name="twitter:card"/i],
    ['JSON-LD', /<script\s+type="application\/ld\+json">/i],
  ];
  for (const [label, pattern] of required) if (!pattern.test(html)) failures.push(`${page}: missing ${label}`);
  const canonical = html.match(/<link\s+rel="canonical"\s+href="([^"]+)"/i)?.[1];
  if (canonical) canonicals.push(canonical);
}

const sitemap = fs.readFileSync(path.join(root, 'frontend', 'sitemap.xml'), 'utf8');
for (const canonical of canonicals) if (!sitemap.includes(`<loc>${canonical}</loc>`)) failures.push(`sitemap: missing ${canonical}`);

const robots = fs.readFileSync(path.join(root, 'frontend', 'robots.txt'), 'utf8');
if (!robots.includes('Disallow: /portal/')) failures.push('robots.txt: attendee portal must be excluded');
if (!robots.includes('Sitemap: https://mbsh96reunion.com/sitemap.xml')) failures.push('robots.txt: sitemap declaration missing');

const portal = fs.readFileSync(path.join(root, 'frontend', 'portal', 'index.html'), 'utf8');
if (!/name="robots"\s+content="noindex,nofollow,noarchive"/i.test(portal)) failures.push('portal/index.html: noindex missing');

for (const file of fs.readdirSync(path.join(root, 'frontend', 'portal', 'auth')).filter((name) => name.endsWith('.html'))) {
  const html = fs.readFileSync(path.join(root, 'frontend', 'portal', 'auth', file), 'utf8');
  if (!/name="robots"\s+content="noindex,nofollow,noarchive"/i.test(html)) failures.push(`portal/auth/${file}: noindex missing`);
}

if (failures.length) {
  console.error(failures.join('\n'));
  process.exit(1);
}
console.log(`SEO contract PASS: ${pages.length} public routes, ${canonicals.length} sitemap URLs, private portal excluded.`);
