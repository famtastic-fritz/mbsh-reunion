import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');
const page = read('frontend/manual/index.html');
const script = read('frontend/js/reunion-manual.js');
const atlas = read('docs/operations/MBSH_REUNION_FEATURE_ATLAS_V1.md');

assert.match(page, /data-page="manual"/, 'manual must participate in the shared public shell');
assert.match(page, /data-manual-private="member"/, 'attendee manual must have a protected chapter');
assert.match(page, /data-manual-private="committee"/, 'committee manual must have a protected chapter');
assert.match(page, /data-manual-private="owner"/, 'owner manual must have a protected chapter');
assert.match(page, /data-manual-present/, 'manual must include presentation controls');
assert.match(page, /noindex,nofollow,noarchive/, 'manual should remain shareable but excluded from search indexing');
assert.match(script, /fetch\('\/portal\/session\.php'/, 'private chapters must use the real session authority');
assert.match(script, /staff\.authorized/, 'committee chapter must require server-authorized staff');
assert.match(script, /staff\.role === 'site_owner'/, 'owner chapter must require the site-owner role');
assert.match(script, /Tickets & sponsorship/, 'public ticket capability must be represented');
assert.match(script, /This is not live checkout or payment collection/, 'manual must not imply live checkout');
assert.match(script, /Originals stay private/, 'manual must preserve media-moderation boundary');
assert.doesNotMatch(page, /wp-admin|cms\/wp-admin/i, 'public manual markup must not advertise the owner CMS');
assert.doesNotMatch(script, /cms\/wp-admin/i, 'public manual script must not expose the owner CMS destination');
assert.match(atlas, /it never includes passwords/i, 'atlas must state the sensitive-data boundary');

console.log('PASS reunion manual contract');
