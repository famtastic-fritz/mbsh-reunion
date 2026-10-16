import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const publicPages = [
  'frontend/index.html',
  'frontend/tickets.html',
  'frontend/through-years.html',
  'frontend/memorial.html',
  'frontend/capsule.html',
  'frontend/playlist.html',
  'frontend/manual/index.html',
];

for (const relativePath of publicPages) {
  const html = fs.readFileSync(path.join(root, relativePath), 'utf8');
  assert.match(html, /data-page="[^"]+"/, `${relativePath} must identify its page for Harry and navigation`);
  assert.match(html, /cinematic-system\.css\?v=cinematic6/, `${relativePath} must load the shared shell styles`);
  assert.match(html, /cinematic-shell\.js\?v=cinematic7/, `${relativePath} must load the current shared shell behavior`);
  assert.match(html, /analytics\.js\?v=ga1/, `${relativePath} must load the shared analytics contract`);
  assert.doesNotMatch(html, /compass-nav|compass\.css|compass\.js/, `${relativePath} must not ship the retired compass shell`);
  assert.doesNotMatch(html, /page-header__back/, `${relativePath} must not ship a second branded home control`);
  assert.match(html, /<footer class="footer"[^>]*><\/footer>/, `${relativePath} must use the shared footer mount`);
}

const functionalContracts = {
  'frontend/tickets.html': [/id="ticket-order-form"/, /js\/ticket-order\.js/, /mailto:committee@mbsh96reunion\.com/],
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
assert.match(shell, /cinema-alumni-login/, 'shared header must keep the Alumni Login action beside the menu');
assert.match(shell, /\/portal\/login/, 'shared Alumni Login action must lead to the real portal login');
assert.doesNotMatch(shell, /mountAlumniInvite|alumni-invite/, 'mixer purchase must not be interrupted by a sign-up campaign');
for (const route of [
  '/index.html', '/tickets.html',
  '/through-years.html', '/memorial.html', '/capsule.html', '/playlist.html', '/manual/', '/portal/login',
]) {
  assert.ok(shell.includes(`'${route}'`), `shared navigation is missing ${route}`);
}

const home = fs.readFileSync(path.join(root, 'frontend/index.html'), 'utf8');
assert.doesNotMatch(home, /alumni-account-campaign|program-bulletin|href="(?:rsvp.html|\/menu\/|survey.html)"/, 'home must keep a direct mixer purchase flow');
assert.match(home, /id="venue"/, 'directions must be inline');
assert.match(home, /href="tickets.html#ticket-order"/, 'home must lead directly to ticket purchase');
for (const route of ['/rsvp.html', '/menu/', '/survey.html']) assert.ok(!shell.includes(`'${route}'`), `retired route in navigation: ${route}`);

console.log(`PASS shared public shell contract (${publicPages.length} pages)`);
