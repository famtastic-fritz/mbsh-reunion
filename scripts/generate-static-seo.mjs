import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..', 'frontend');
const origin = 'https://mbsh96reunion.com';
const config = JSON.parse(fs.readFileSync(path.resolve(root, '../config/site-config.json'), 'utf8'));
const socialImage = `${origin}/assets/mixer-2026/social-preview.jpg`;
const event = {
  '@type': 'Event',
  '@id': `${origin}/#event`,
  name: config.REUNION_NAME,
  startDate: config.REUNION_START_AT,
  endDate: config.REUNION_END_AT,
  description: 'Reunion mixer. $30 admission per person. Food on your own.',
  offers: {'@type': 'Offer', price: config.TICKET_PRICE, priceCurrency: config.TICKET_CURRENCY, url: `${origin}/tickets.html`},
  eventStatus: 'https://schema.org/EventScheduled',
  eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
  location: {'@type': 'Place', name: config.REUNION_VENUE, address: {'@type': 'PostalAddress', streetAddress: '723 Lincoln Ln N', addressLocality: 'Miami Beach', addressRegion: 'FL', postalCode: '33139', addressCountry: 'US'}},
  image: [socialImage],
  organizer: {'@type': 'Organization', name: 'MBSH Class of 1996 Reunion Committee', url: origin},
};

const pages = {
  'index.html': ['/', 'MBSH Reunion Mixer | Twisted Tonic | November 7, 2026', 'Join the MBSH Class of 1996 Reunion Mixer at Twisted Tonic on November 7, 2026, 7–10 PM. Tickets are $30 per person. Food on your own.'],
  'tickets.html': ['/tickets.html', '$30 Mixer Tickets | MBSH Class of 1996', 'Get $30 tickets for the MBSH Class of 1996 Reunion Mixer at Twisted Tonic, November 7, 2026, 7–10 PM. Food is on your own.'],
  'through-years.html': ['/through-years.html', 'Through the Years | MBSH Class of 1996 Memories', 'Explore the developing Class of 1996 archive and submit a photograph or story for committee review and the reunion memory reel.'],
  'memorial.html': ['/memorial.html', 'In Memory | MBSH Class of 1996 Reunion', 'Remember classmates with care and learn how to submit a name or tribute for the MBSH Class of 1996 reunion memorial.'],
  'capsule.html': ['/capsule.html', '1996 Time Capsule | MBSH Reunion', 'Write a private message to your 1996 self and receive it on the morning of the Class of 1996 reunion.'],
  'playlist.html': ['/playlist.html', 'Class of 1996 Playlist | MBSH Reunion', 'Revisit the soundtrack of 1996 and suggest a meaningful song for the Miami Beach Senior High reunion playlist.'],
};

for (const [file, [urlPath, title, description]] of Object.entries(pages)) {
  const target = path.join(root, file);
  let html = fs.readFileSync(target, 'utf8');
  const canonical = `${origin}${urlPath}`;
  const graph = {
    '@context': 'https://schema.org',
    '@graph': [
      event,
      {'@type': 'WebPage', '@id': `${canonical}#webpage`, url: canonical, name: title, description, isPartOf: {'@id': `${origin}/#website`}, about: {'@id': `${origin}/#event`}},
      {'@type': 'WebSite', '@id': `${origin}/#website`, url: origin, name: 'MBSH Class of 1996 Reunion'},
    ],
  };
  const block = `<!-- famtastic-seo:start -->\n  <title>${title}</title>\n  <meta name="description" content="${description}">\n  <link rel="canonical" href="${canonical}">\n  <meta property="og:type" content="website">\n  <meta property="og:site_name" content="MBSH Class of 1996 Reunion">\n  <meta property="og:title" content="${title}">\n  <meta property="og:description" content="${description}">\n  <meta property="og:url" content="${canonical}">\n  <meta property="og:image" content="${socialImage}">\n  <meta name="twitter:card" content="summary_large_image">\n  <meta name="twitter:title" content="${title}">\n  <meta name="twitter:description" content="${description}">\n  <meta name="twitter:image" content="${socialImage}">\n  <script type="application/ld+json">${JSON.stringify(graph).replaceAll('<', '\\u003c')}</script>\n  <!-- famtastic-seo:end -->`;
  html = html.replace(/\s*<!-- famtastic-seo:start -->[\s\S]*?<!-- famtastic-seo:end -->\s*/g, '\n');
  html = html.replace(/\s*<title>[\s\S]*?<\/title>\s*/i, '\n');
  html = html.replace(/\s*<meta\s+name="description"[^>]*>/gi, '');
  html = html.replace(/\s*<meta\s+(?:property="og:[^"]*"|name="twitter:[^"]*")[^>]*>/gi, '');
  html = html.replace(/\s*<link\s+rel="canonical"[^>]*>/gi, '');
  html = html.replace(/\s*<script\s+type="application\/ld\+json">[\s\S]*?<\/script>/gi, '');
  html = html.replace(/<head>/i, `<head>\n  ${block}`);
  fs.writeFileSync(target, html);
}

console.log(`Updated ${Object.keys(pages).length} public pages with canonical SEO metadata and Event schema.`);
