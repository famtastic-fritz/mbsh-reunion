import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(import.meta.dirname, '../..');
const backend = fs.readFileSync(path.join(root, 'backend/ticket-order.php'), 'utf8');
const browser = fs.readFileSync(path.join(root, 'frontend/js/ticket-order.js'), 'utf8');
const commerce = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-commerce.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/famtastic-reunion-platform.php'), 'utf8');
const tickets = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-tickets.php'), 'utf8');
const operations = fs.readFileSync(path.join(root, 'wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-operations.php'), 'utf8');
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
assert.match(commerce, /woocommerce_store_api_checkout_update_order_meta/, 'Checkout Blocks must copy reservation metadata to the Store API order');
assert.match(commerce, /woocommerce_store_api_checkout_order_processed/, 'Checkout Blocks must link the Store API order to the saved attempt');
assert.match(commerce, /copy_store_api_cart_to_order/, 'Store API checkout must use the reservation-aware cart bridge');
assert.match(commerce, /store_api_order_created/, 'Store API checkout must record the created order lifecycle');
assert.match(commerce, /woocommerce_payment_complete/, 'tickets and notices must be downstream of payment confirmation');
assert.match(commerce, /int \$variation_id = 0, array \$variations = \[\]/, 'Woo add-to-cart validation must tolerate three-argument callers');
assert.match(commerce, /Famtastic_Reunion_Tickets::issue_for_order/, 'ticket issuance must be invoked only from the paid path');
assert.match(tickets, /_famtastic_reservation_code/, 'MBSH ticket issuance must be linked to the reservation-aware order');
assert.match(tickets, /_famtastic_ticket_is_test/, 'test tickets must be explicitly marked as non-admission records');
assert.match(tickets, /test_ticket_not_admission/, 'check-in must reject test-ticket credentials');
assert.match(operations, /_famtastic_tickets_issued/, 'reconciliation must inspect the ticket issuer marker');
assert.doesNotMatch(operations, /_famtastic_portal_tickets_issued/, 'reconciliation must not inspect a marker the issuer never writes');
assert.match(commerce, /woocommerce_add_to_cart_validation/, 'ticket product must reject unlinked direct cart adds');
assert.match(plugin, /class-commerce\.php/, 'commerce adapter must be loaded by the platform plugin');
assert.match(theme, /the_content\(\)/, 'WordPress pages must render their content');
assert.match(theme, /woocommerce_content\(\)/, 'cart and checkout must render WooCommerce content');
assert.match(theme, /\$isWooUtilityPage && have_posts\(\)/, 'cart and checkout utility pages must render their block or shortcode content');

console.log('PASS commerce incident contract');
