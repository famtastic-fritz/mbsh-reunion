import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const publicPages = [
  'frontend/index.html',
  'frontend/rsvp.html',
  'frontend/tickets.html',
  'frontend/menu/index.html',
  'frontend/survey.html',
  'frontend/through-years.html',
  'frontend/memorial.html',
  'frontend/capsule.html',
  'frontend/playlist.html',
];

for (const relativePath of publicPages) {
  const html = fs.readFileSync(path.join(root, relativePath), 'utf8');
  assert.match(html, /data-page="[^"]+"/, `${relativePath} must identify its page for Harry and navigation`);
  assert.match(html, /cinematic-system\.css\?v=cinematic5/, `${relativePath} must load the shared shell styles`);
  assert.match(html, /cinematic-shell\.js\?v=cinematic5/, `${relativePath} must load the shared shell behavior`);
  assert.match(html, /analytics\.js\?v=ga1/, `${relativePath} must load the shared analytics contract`);
  assert.doesNotMatch(html, /compass-nav|compass\.css|compass\.js/, `${relativePath} must not ship the retired compass shell`);
  assert.doesNotMatch(html, /page-header__back/, `${relativePath} must not ship a second branded home control`);
  assert.match(html, /<footer class="footer"[^>]*><\/footer>/, `${relativePath} must use the shared footer mount`);
}

const functionalContracts = {
  'frontend/rsvp.html': [/id="rsvp-form"/, /js\/rsvp\.js/],
  'frontend/tickets.html': [/id="ticket-order-form"/, /js\/ticket-order\.js/, /id="sponsor-form"/, /js\/sponsor\.js/],
  'frontend/menu/index.html': [/id="menu-form"/, /js\/menu\.js/],
  'frontend/survey.html': [/id="survey-form"/, /fetch\('\/survey2\.php'/],
  'frontend/through-years.html': [/id="memory-form"/, /js\/memory\.js/],
  'frontend/memorial.html': [/id="memorial-list"/, /js\/memorial\.js/],
  'frontend/capsule.html': [/id="capsule-form"/, /js\/time-capsule\.js/],
  'frontend/playlist.html': [/id="playlist-suggest-form"/, /js\/playlist\.js/],
};

for (const [relativePath, contracts] of Object.entries(functionalContracts)) {
  const html = fs.readFileSync(path.join(root, relativePath), 'utf8');
  for (const contract of contracts) assert.match(html, contract, `${relativePath} lost a functional contract during shell migration`);
}

const shell = fs.readFileSync(path.join(root, 'frontend/js/cinematic-shell.js'), 'utf8');
for (const route of [
  '/index.html', '/rsvp.html', '/tickets.html', '/menu/', '/survey.html',
  '/through-years.html', '/memorial.html', '/capsule.html', '/playlist.html', '/portal/login',
]) {
  assert.ok(shell.includes(`'${route}'`), `shared navigation is missing ${route}`);
}

const rsvp = fs.readFileSync(path.join(root, 'frontend/rsvp.html'), 'utf8');
assert.doesNotMatch(rsvp, /journey-progress/, 'RSVP must not restore the competing legacy progress strip');
assert.doesNotMatch(rsvp, /premiere-seat-row/, 'RSVP must not restore the unexplained seat-button decoration');
assert.match(rsvp, /diploma-rsvp-stage/, 'RSVP must retain the approved diploma experience');

console.log(`PASS shared public shell contract (${publicPages.length} pages)`);
