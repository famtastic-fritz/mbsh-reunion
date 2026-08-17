import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..', 'frontend');
const origin = 'https://mbsh96reunion.com';
const socialImage = `${origin}/assets/social/2026-reunion-hype/01-hi-tide-harry-ultra-real-4x5.png`;
const event = {
  '@type': 'Event',
  '@id': `${origin}/#event`,
  name: 'Miami Beach Senior High Class of 1996 30th Reunion',
  startDate: '2026-11-07T18:30:00-05:00',
  eventStatus: 'https://schema.org/EventScheduled',
  eventAttendanceMode: 'https://schema.org/OfflineEventAttendanceMode',
  location: {'@type': 'Place', name: 'Miami Shores Country Club'},
  image: [socialImage],
  organizer: {'@type': 'Organization', name: 'MBSH Class of 1996 Reunion Committee', url: origin},
};

const pages = {
  'index.html': ['/', 'MBSH Class of 1996 30th Reunion | November 7, 2026', 'Come home for the Miami Beach Senior High Class of 1996 30th reunion. RSVP, review tickets, share memories, and get event updates.'],
  'rsvp.html': ['/rsvp.html', 'RSVP | MBSH Class of 1996 30th Reunion', 'Reserve your place for the MBSH Class of 1996 reunion and share the details the committee needs for reunion night.'],
  'tickets.html': ['/tickets.html', 'Reunion Tickets | MBSH Class of 1996', 'Review MBSH Class of 1996 reunion ticket options, reserve seats, and learn what happens before secure online payment opens.'],
  'menu.html': ['/menu/', 'Dinner Menu | MBSH Class of 1996 Reunion', 'Review the reunion dinner menu, select an entrée, and share dietary needs with the MBSH Class of 1996 reunion committee.'],
  'survey.html': ['/survey.html', 'Class Check-In | MBSH Class of 1996 Reunion', 'Help the reunion committee understand attendance, interests, and what would make the Class of 1996 reunion meaningful.'],
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
  html = html.replace(/<head>/i, `<head>\n  ${block}`);
  fs.writeFileSync(target, html);
}

console.log(`Updated ${Object.keys(pages).length} public pages with canonical SEO metadata and Event schema.`);
