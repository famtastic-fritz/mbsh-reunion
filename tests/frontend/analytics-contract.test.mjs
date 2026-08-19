import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const analytics = fs.readFileSync(path.join(root, 'frontend/js/analytics.js'), 'utf8');

assert.match(analytics, /G-L6359T51HR/, 'MBSH production measurement ID must remain explicit');
assert.match(analytics, /send_page_view:\s*false/, 'automatic page views must be disabled to prevent duplicates');
assert.match(analytics, /analytics\.track\('page_view'/, 'one explicit page view must be recorded');
assert.match(analytics, /ad_storage:\s*'denied'/, 'advertising storage must default to denied');
assert.match(analytics, /url\.search\s*=\s*''/, 'all query parameters must be removed from analytics page locations');
assert.doesNotMatch(analytics, /document\.cookie|localStorage|sessionStorage/, 'analytics must not store custom browser identifiers');

const outcomeContracts = {
  'frontend/js/rsvp.js': 'rsvp_submitted',
  'frontend/js/ticket-order.js': 'ticket_order_submitted',
  'frontend/js/menu.js': 'menu_selection_submitted',
  'frontend/js/memory.js': 'memory_submitted',
  'frontend/js/time-capsule.js': 'time_capsule_submitted',
  'frontend/js/playlist.js': 'playlist_suggestion_submitted',
  'frontend/js/sponsor.js': 'sponsor_inquiry_submitted',
  'frontend/survey.html': 'survey_submitted',
};

for (const [relativePath, eventName] of Object.entries(outcomeContracts)) {
  const source = fs.readFileSync(path.join(root, relativePath), 'utf8');
  assert.ok(source.includes(`'${eventName}'`), `${relativePath} must emit ${eventName} only after success`);
}

for (const forbidden of ['first_name', 'last_name', 'data.email', 'payload.dietary', 'data.order_code']) {
  assert.doesNotMatch(analytics, new RegExp(forbidden.replace('.', '\\.')), `analytics core must not collect ${forbidden}`);
}

assert.doesNotMatch(
  fs.readFileSync(path.join(root, 'frontend/js/menu.js'), 'utf8'),
  /has_dietary_note/,
  'dietary preference state must never be sent to analytics',
);

console.log(`PASS analytics contract (${Object.keys(outcomeContracts).length} confirmed outcomes)`);
