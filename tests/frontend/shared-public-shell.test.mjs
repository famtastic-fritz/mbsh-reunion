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
  assert.match(html, /cinematic-system\.css\?v=cinematic4/, `${relativePath} must load the shared shell styles`);
  assert.match(html, /cinematic-shell\.js\?v=cinematic4/, `${relativePath} must load the shared shell behavior`);
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
