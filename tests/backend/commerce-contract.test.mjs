import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const backend = fs.readFileSync(path.join(root, 'backend/ticket-order.php'), 'utf8');
const browser = fs.readFileSync(path.join(root, 'frontend/js/ticket-order.js'), 'utf8');
const commerce = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-commerce.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/famtastic-reunion-platform.php'), 'utf8');
const tickets = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-tickets.php'), 'utf8');
const theme = fs.readFileSync(path.join(root, 'wordpress/wp-content/themes/famtastic-event-cinema/index.php'), 'utf8');

assert.doesNotMatch(backend, /fam_send_email|require_once __DIR__ \. '\/lib\/resend\.php'/, 'reservation endpoint must not send pre-payment mail');
assert.match(backend, /beginTransaction\(\)/, 'same-purchaser reservation writes must be serialized');
assert.match(backend, /replayed/, 'identical recent submissions must replay the existing reservation');
assert.match(backend, /created_at >= \(NOW\(\) - INTERVAL 30 MINUTE\)/, 'retry window must use the same database clock as created_at');
assert.match(backend, /price_tier, notes\) VALUES/, 'reservation inserts must retain notes used by replay matching');
assert.match(browser, /wc-ajax=famtastic_ticket_add_to_cart/, 'public form must use the reservation-aware Woo adapter');
assert.doesNotMatch(browser, /wc-ajax=add_to_cart/, 'public form must not call generic Woo add-to-cart');
assert.match(commerce, /famtastic_order_attempts/, 'WordPress must maintain an order-attempt ledger');
assert.match(commerce, /COUNT\(DISTINCT email_hash\)/, 'ledger must report unique purchasers');
for (const status of ['attempt', 'abandoned', 'processing', 'paid', 'failed', 'refunded']) {
  assert.match(commerce, new RegExp(`['"]${status}['"]`), `ledger must represent ${status}`);
}
assert.match(commerce, /_famtastic_reservation_code/, 'Woo order must carry the reservation code');
assert.match(commerce, /woocommerce_payment_complete/, 'tickets and notices must be downstream of payment confirmation');
assert.match(commerce, /Famtastic_Reunion_Tickets::issue_for_order/, 'ticket issuance must be invoked only from the paid path');
assert.match(tickets, /_famtastic_reservation_code/, 'MBSH ticket issuance must be linked to the reservation-aware order');
assert.match(commerce, /woocommerce_add_to_cart_validation/, 'ticket product must reject unlinked direct cart adds');
assert.match(plugin, /class-commerce\.php/, 'commerce adapter must be loaded by the platform plugin');
assert.match(theme, /the_content\(\)/, 'WordPress pages must render their content');
assert.match(theme, /woocommerce_content\(\)/, 'cart and checkout must render WooCommerce content');

console.log('PASS commerce incident contract');
