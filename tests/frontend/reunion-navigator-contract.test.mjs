import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';

const root = resolve(import.meta.dirname, '../..');
const navigator = readFileSync(resolve(root, 'frontend/js/reunion-navigator.js'), 'utf8');
const shell = readFileSync(resolve(root, 'frontend/js/cinematic-shell.js'), 'utf8');
const attendee = readFileSync(resolve(root, 'frontend/portal/index.html'), 'utf8');
const attendeeApp = readFileSync(resolve(root, 'frontend/portal/js/portal.js'), 'utf8');
const committee = readFileSync(resolve(root, 'frontend/portal/committee/index.html'), 'utf8');
const committeeApp = readFileSync(resolve(root, 'frontend/portal/js/committee-workspace.js'), 'utf8');
const css = readFileSync(resolve(root, 'frontend/css/reunion-navigator.css'), 'utf8');

for (const role of ['public', 'member', 'committee']) {
  assert.match(navigator, new RegExp(`${role}:\\s*\\{`), `missing ${role} guide`);
}
for (const marker of ['view_roster', 'moderate_media', 'view_inbox', 'manage_tickets']) {
  assert.match(navigator, new RegExp(`capability: '${marker}'`), `committee guide must capability-filter ${marker}`);
}
assert.match(navigator, /never submits, sends, approves, or changes anything for you/i, 'guide must state its non-mutating boundary');
assert.doesNotMatch(navigator, /localStorage|sessionStorage/, 'portal guidance must not put training state in browser storage');
assert.match(navigator, /mbsh:portal-ready/, 'attendee guide must mount after a real session');
assert.match(navigator, /mbsh:committee-ready/, 'committee guide must mount after real staff authorization');
assert.match(shell, /mountReunionNavigator/, 'public shell must load the guide as progressive enhancement');
assert.match(attendee, /data-reunion-guide-open/, 'attendee portal needs a visible guide entry point');
assert.match(attendee, /reunion-navigator\.js/, 'attendee portal must load navigator code');
assert.match(attendeeApp, /mbsh:portal-ready/, 'portal must emit readiness only after its session is established');
assert.match(committee, /Start tour/, 'committee portal needs a visible tour entry point');
assert.match(committee, /Start this desk tour/, 'committee Harry briefing must retain a tour entry point');
assert.match(committeeApp, /mbsh:committee-ready/, 'committee tour must wait for capability authorization');
assert.match(css, /prefers-reduced-motion/, 'guide must explicitly support reduced motion');
assert.match(css, /alumni-invite-open/, 'guide must avoid the timed alumni invitation');

console.log('PASS reunion navigator contract');
