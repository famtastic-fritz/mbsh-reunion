import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';

const root = new URL('../../', import.meta.url);
const page = readFileSync(new URL('frontend/portal/committee/index.html', root), 'utf8');
const behavior = readFileSync(new URL('frontend/portal/js/admin-trivia.js', root), 'utf8');
const email = readFileSync(new URL('backend/lib/portal-email-templates.php', root), 'utf8');
const ticketStudio = readFileSync(new URL('frontend/portal/js/admin-trivia.js', root), 'utf8');

assert.match(page, /data-sample-ticket-form/, 'ticket workspace must expose the sample studio');
assert.match(page, /SAMPLE · NOT VALID FOR ENTRY/, 'sample must carry an unmistakable watermark');
assert.match(page, /NO QR CODE · NO LIVE CREDENTIAL/, 'sample must disclose that no credential exists');
assert.doesNotMatch(page.match(/<section class="sample-ticket-studio"[\s\S]*?<\/section>/)?.[0] || '', /credential_fingerprint|ticket_check_in|qr-pattern/i, 'sample studio must not contain a ticket credential');
assert.match(behavior, /window\.print\(\)/, 'sample must support a printable committee preview');
assert.match(ticketStudio, /FAMtastic Designs Starter Websites · \$199/, 'sample ticket must include the branded reverse-side offer');

for (const state of ['order_received', 'payment_confirmed', 'ticket_issued', 'payment_failed', 'order_cancelled', 'refunded']) {
  assert.match(email, new RegExp(`'${state}'`), `missing transactional email state ${state}`);
}
assert.match(email, /This is not a paid ticket or admission confirmation/, 'request email must not imply payment');
assert.match(email, /does not contain a check-in credential/, 'issued email must keep credentials out of email');
assert.match(email, /ACT I · CALLING THE HI-TIDES HOME/, 'email program must identify the current act');
assert.match(email, /Starter Website for \$199/, 'email footer must carry the FAMtastic offer');
assert.doesNotMatch(email, /09-confused\.png/, 'retired malformed Harry must never appear in email');
