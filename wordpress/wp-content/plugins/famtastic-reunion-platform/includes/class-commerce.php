<?php

declare(strict_types=1);

/**
 * WooCommerce adapter for the public reunion ticket reservation flow.
 *
 * Legacy ticket_orders remains preserved as a request/snapshot source. WooCommerce
 * owns the cart, order, payment, refund, and financial lifecycle. This class
 * records the bridge and its evidence without creating a second paid-order truth.
 */
final class Famtastic_Reunion_Commerce
{
    private const PRODUCT_ID = 26;
    private const EVENT_KEY = 'mbsh-1996-30th';
    private const SCHEMA_VERSION = '1';
    private const STATUSES = ['attempt', 'abandoned', 'processing', 'paid', 'failed', 'refunded'];
    private static bool $internal_cart_add = false;

    public static function register(): void
    {
        add_action('init', [self::class, 'maybe_upgrade'], 5);
        add_action('admin_init', [self::class, 'maybe_upgrade']);
        add_action('admin_menu', [self::class, 'admin_menu'], 45);

        add_action('wc_ajax_famtastic_ticket_add_to_cart', [self::class, 'add_to_cart']);
        add_action('wc_ajax_nopriv_famtastic_ticket_add_to_cart', [self::class, 'add_to_cart']);

        add_action('woocommerce_add_to_cart', [self::class, 'cart_added'], 10, 6);
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'require_reservation_for_ticket_product'], 10, 5);
        add_action('woocommerce_checkout_create_order_line_item', [self::class, 'copy_cart_item_to_order_item'], 10, 4);
        add_action('woocommerce_checkout_create_order', [self::class, 'copy_cart_to_order'], 10, 2);
        add_action('woocommerce_checkout_order_processed', [self::class, 'order_created'], 10, 3);
        add_action('woocommerce_payment_complete', [self::class, 'payment_complete'], 20);
        add_action('woocommerce_order_status_processing', [self::class, 'paid_status'], 20);
        add_action('woocommerce_order_status_completed', [self::class, 'paid_status'], 20);
        add_action('woocommerce_order_status_failed', [self::class, 'failed_status'], 20);
        add_action('woocommerce_order_status_cancelled', [self::class, 'failed_status'], 20);
        add_action('woocommerce_order_status_refunded', [self::class, 'refunded_status'], 20);
        add_action('famtastic_reunion_order_attempt_monitor', [self::class, 'monitor']);
    }

    public static function ensure_schema(): void
    {
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $attempts = self::attempts_table();
        $events = self::events_table();
        dbDelta("CREATE TABLE {$attempts} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reservation_code varchar(32) NOT NULL,
            email varchar(255) NOT NULL,
            email_hash char(64) NOT NULL,
            contact_name varchar(200) NOT NULL,
            quantity smallint(5) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'attempt',
            repeat_flag tinyint(1) unsigned NOT NULL DEFAULT 0,
            woo_order_id bigint(20) unsigned NULL,
            first_attempt_at datetime NOT NULL,
            last_attempt_at datetime NOT NULL,
            attempt_count int(10) unsigned NOT NULL DEFAULT 1,
            failure_reason varchar(255) NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY reservation_code (reservation_code),
            KEY purchaser (email_hash),
            KEY status_age (status,last_attempt_at),
            KEY woo_order (woo_order_id)
        ) {$charset};");
        dbDelta("CREATE TABLE {$events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            attempt_id bigint(20) unsigned NOT NULL,
            event_type varchar(40) NOT NULL,
            event_at datetime NOT NULL,
            details_json longtext NULL,
            PRIMARY KEY  (id),
            KEY attempt_events (attempt_id,event_at),
            KEY event_type (event_type,event_at)
        ) {$charset};");
        update_option('famtastic_reunion_commerce_schema', self::SCHEMA_VERSION, false);
    }

    public static function maybe_upgrade(): void
    {
        if ((string) get_option('famtastic_reunion_commerce_schema', '') !== self::SCHEMA_VERSION) {
            self::ensure_schema();
        }
        self::schedule_monitor();
    }

    public static function schedule_monitor(): void
    {
        if (function_exists('as_schedule_recurring_action') && function_exists('as_next_scheduled_action')) {
            if (!as_next_scheduled_action('famtastic_reunion_order_attempt_monitor', [], 'famtastic-reunion')) {
                as_schedule_recurring_action(time() + 300, 300, 'famtastic_reunion_order_attempt_monitor', [], 'famtastic-reunion', true);
            }
            return;
        }
        if (!wp_next_scheduled('famtastic_reunion_order_attempt_monitor')) {
            wp_schedule_event(time() + 300, 'hourly', 'famtastic_reunion_order_attempt_monitor');
        }
    }

    public static function add_to_cart(): void
    {
        if (!function_exists('WC') || !WC() || !WC()->cart) {
            self::json_error(503, 'checkout_unavailable', 'Secure checkout is temporarily unavailable.');
        }

        $code = strtoupper(sanitize_text_field(wp_unslash($_POST['mbsh_order_code'] ?? '')));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $checkout_token = sanitize_text_field(wp_unslash($_POST['checkout_token'] ?? ''));
        $name = sanitize_text_field(wp_unslash($_POST['contact_name'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $guest_names = sanitize_textarea_field(wp_unslash($_POST['guest_names'] ?? ''));
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));
        $quantity = absint($_POST['quantity'] ?? 0);

        if (!preg_match('/^MBSH-[A-F0-9]{6}$/', $code) || !is_email($email) || $name === '' || $quantity < 1 || $quantity > 10 || !self::valid_checkout_token($checkout_token, $code, $email, $quantity)) {
            self::json_error(400, 'invalid_reservation', 'The reservation details could not be verified. Please start again.');
        }

        $attempt_id = self::record_attempt($code, $email, $name, $quantity, 'form_submitted', [
            'phone_present' => $phone !== '',
            'guest_names_present' => $guest_names !== '',
        ]);

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (($cart_item['famtastic_reservation_code'] ?? '') === $code) {
                self::record_event($attempt_id, 'cart_replayed', ['cart_item_key' => $cart_item_key]);
                wp_send_json(['ok' => true, 'cart_item_key' => $cart_item_key, 'replayed' => true]);
            }
        }

        $product = wc_get_product(self::PRODUCT_ID);
        if (!$product || !$product->is_purchasable() || !$product->is_in_stock() || $product->get_meta('_famtastic_ticket_event') !== self::EVENT_KEY) {
            self::fail_attempt($attempt_id, 'ticket_product_unavailable');
            self::json_error(409, 'ticket_product_unavailable', 'Secure checkout is not ready for this ticket product. Please try again later.');
        }

        self::$internal_cart_add = true;
        try {
            $cart_item_key = WC()->cart->add_to_cart(self::PRODUCT_ID, $quantity, 0, [], [
                'famtastic_reservation_code' => $code,
                'famtastic_attempt_id' => $attempt_id,
                'famtastic_contact_name' => $name,
                'famtastic_email' => $email,
                'famtastic_phone' => $phone,
                'famtastic_guest_names' => $guest_names,
                'famtastic_notes' => $notes,
            ]);
        } finally {
            self::$internal_cart_add = false;
        }

        if (!$cart_item_key) {
            self::fail_attempt($attempt_id, 'woocommerce_add_to_cart_failed');
            self::json_error(409, 'cart_add_failed', 'Your reservation was saved, but secure checkout could not be opened.');
        }

        self::record_event($attempt_id, 'cart_added', ['cart_item_key' => $cart_item_key]);
        wp_send_json(['ok' => true, 'cart_item_key' => $cart_item_key, 'replayed' => false]);
    }

    public static function require_reservation_for_ticket_product(bool $passed, int $product_id, int $quantity, int $variation_id, array $variations): bool
    {
        if ($product_id === self::PRODUCT_ID && !self::$internal_cart_add) {
            wc_add_notice('Start at the reunion ticket form so your reservation can be linked to this checkout.', 'error');
            return false;
        }
        return $passed;
    }

    private static function valid_checkout_token(string $token, string $code, string $email, int $quantity): bool
    {
        if (!defined('FAMTASTIC_REUNION_CHECKOUT_SECRET') || trim((string) FAMTASTIC_REUNION_CHECKOUT_SECRET) === '') {
            return false;
        }
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return false;
        }
        $encoded = strtr($parts[0], '-_', '+/');
        $padding = strlen($encoded) % 4;
        if ($padding > 0) {
            $encoded .= str_repeat('=', 4 - $padding);
        }
        $payload = base64_decode($encoded, true);
        if (!is_string($payload)) {
            return false;
        }
        $values = explode('|', $payload, 4);
        if (count($values) !== 4 || (int) $values[3] < time()) {
            return false;
        }
        [$tokenCode, $tokenEmail, $tokenQuantity, $expires] = $values;
        $expected = hash_hmac('sha256', $payload, (string) FAMTASTIC_REUNION_CHECKOUT_SECRET);
        return hash_equals($expected, $parts[1])
            && hash_equals($tokenCode, $code)
            && hash_equals(strtolower($tokenEmail), strtolower($email))
            && (int) $tokenQuantity === $quantity;
    }

    public static function cart_added(string $cart_item_key, int $product_id, int $quantity, int $variation_id, array $variation, array $cart_item_data): void
    {
        if ($product_id !== self::PRODUCT_ID || empty($cart_item_data['famtastic_attempt_id'])) {
            return;
        }
        self::record_event((int) $cart_item_data['famtastic_attempt_id'], 'woocommerce_cart_added', [
            'cart_item_key' => $cart_item_key,
            'quantity' => $quantity,
        ]);
    }

    public static function copy_cart_item_to_order_item(WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order): void
    {
        if (empty($values['famtastic_reservation_code'])) {
            return;
        }
        $item->add_meta_data('_famtastic_reservation_code', sanitize_text_field((string) $values['famtastic_reservation_code']), true);
        $item->add_meta_data('_famtastic_attempt_id', absint($values['famtastic_attempt_id'] ?? 0), true);
    }

    public static function copy_cart_to_order(WC_Order $order, array $data): void
    {
        if (!function_exists('WC') || !WC() || !WC()->cart) {
            return;
        }
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (($cart_item['famtastic_reservation_code'] ?? '') === '') {
                continue;
            }
            $code = sanitize_text_field((string) $cart_item['famtastic_reservation_code']);
            $attempt_id = absint($cart_item['famtastic_attempt_id'] ?? 0);
            $order->update_meta_data('_famtastic_reservation_code', $code);
            $order->update_meta_data('_famtastic_attempt_id', $attempt_id);
            $order->update_meta_data('_famtastic_ticket_event', self::EVENT_KEY);
            $order->update_meta_data('_famtastic_payment_lifecycle', 'processing');
            if (empty($data['billing_first_name']) && empty($data['billing_last_name'])) {
                [$first, $last] = self::split_name((string) ($cart_item['famtastic_contact_name'] ?? ''));
                $order->set_billing_first_name($first);
                $order->set_billing_last_name($last);
            }
            if (empty($data['billing_email']) && !empty($cart_item['famtastic_email'])) {
                $order->set_billing_email(sanitize_email((string) $cart_item['famtastic_email']));
            }
            if (!empty($cart_item['famtastic_phone'])) {
                $order->set_billing_phone(sanitize_text_field((string) $cart_item['famtastic_phone']));
            }
            $order->update_meta_data('_famtastic_guest_names', sanitize_textarea_field((string) ($cart_item['famtastic_guest_names'] ?? '')));
            $order->update_meta_data('_famtastic_ticket_notes', sanitize_textarea_field((string) ($cart_item['famtastic_notes'] ?? '')));
            break;
        }
    }

    public static function order_created(int $order_id, array $posted_data, WC_Order $order): void
    {
        $attempt_id = absint($order->get_meta('_famtastic_attempt_id', true));
        if (!$attempt_id) {
            return;
        }
        self::link_attempt_to_order($attempt_id, $order_id);
        self::set_attempt_status($attempt_id, 'processing');
        self::record_event($attempt_id, 'woo_order_created', ['order_id' => $order_id, 'status' => $order->get_status()]);
    }

    public static function payment_complete(int $order_id): void
    {
        self::mark_paid($order_id);
    }

    public static function paid_status(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if ($order && $order->is_paid()) {
            self::mark_paid($order_id);
        }
    }

    public static function failed_status(int $order_id): void
    {
        self::mark_order_status($order_id, 'failed');
    }

    public static function refunded_status(int $order_id): void
    {
        self::mark_order_status($order_id, 'refunded');
    }

    public static function monitor(): void
    {
        global $wpdb;
        $table = self::attempts_table();
        $abandoned = $wpdb->query("UPDATE {$table} SET status='abandoned', failure_reason='checkout_timeout' WHERE status='attempt' AND last_attempt_at < UTC_TIMESTAMP() - INTERVAL 45 MINUTE");
        $paid_without_tickets = 0;
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders(['status' => ['processing', 'completed'], 'limit' => 100, 'return' => 'objects']);
            foreach ($orders as $order) {
                if (!$order instanceof WC_Order || !$order->is_paid() || $order->get_meta('_famtastic_tickets_issued', true)) {
                    continue;
                }
                if ((time() - $order->get_date_created()->getTimestamp()) > 300) {
                    $paid_without_tickets++;
                }
            }
        }
        update_option('famtastic_reunion_commerce_health', [
            'at' => current_time('mysql', true),
            'abandoned_marked' => (int) $abandoned,
            'paid_without_tickets' => $paid_without_tickets,
            'alert' => $paid_without_tickets > 0 ? 'paid_order_missing_tickets' : null,
            'last_run_ok' => true,
        ], false);
    }

    public static function admin_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            'Ticket order attempts',
            'Ticket attempts',
            'manage_woocommerce',
            'famtastic-order-attempts',
            [self::class, 'render_admin']
        );
    }

    public static function render_admin(): void
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('WooCommerce access required.', '', ['response' => 403]);
        }
        global $wpdb;
        $attempts = self::attempts_table();
        $events = self::events_table();
        $summary = $wpdb->get_row("SELECT COUNT(*) total, COUNT(DISTINCT email_hash) purchasers, SUM(repeat_flag) repeats, SUM(status='attempt') attempts, SUM(status='abandoned') abandoned, SUM(status='processing') processing, SUM(status='paid') paid, SUM(status='failed') failed, SUM(status='refunded') refunded FROM {$attempts}", ARRAY_A) ?: [];
        $rows = $wpdb->get_results("SELECT * FROM {$attempts} ORDER BY last_attempt_at DESC, id DESC LIMIT 200", ARRAY_A);
        $health = get_option('famtastic_reunion_commerce_health', []);
        echo '<div class="wrap"><h1>Ticket order attempt ledger</h1>';
        echo '<p>WooCommerce remains the financial authority. This ledger records public reservation and checkout bridge evidence; it does not mark legacy requests paid or issue admission.</p>';
        printf('<p><strong>Monitor:</strong> last run %s · abandoned marked %d · paid orders missing tickets %d%s</p>', esc_html((string) ($health['at'] ?? 'not run')), (int) ($health['abandoned_marked'] ?? 0), (int) ($health['paid_without_tickets'] ?? 0), !empty($health['alert']) ? ' · <span class="notice notice-error">' . esc_html((string) $health['alert']) . '</span>' : '');
        echo '<div class="famtastic-integration-grid">';
        foreach ([['Unique purchasers', $summary['purchasers'] ?? 0], ['All attempts', $summary['total'] ?? 0], ['Repeat flags', $summary['repeats'] ?? 0], ['Attempt', $summary['attempts'] ?? 0], ['Abandoned', $summary['abandoned'] ?? 0], ['Processing', $summary['processing'] ?? 0], ['Paid', $summary['paid'] ?? 0], ['Failed', $summary['failed'] ?? 0], ['Refunded', $summary['refunded'] ?? 0]] as [$label, $value]) {
            printf('<article><p>%s</p><h2>%s</h2></article>', esc_html($label), esc_html((string) $value));
        }
        echo '</div><table class="widefat striped"><thead><tr><th>Reservation</th><th>Purchaser</th><th>Qty</th><th>Status</th><th>Repeat</th><th>Woo order</th><th>All attempt timestamps</th><th>Last reason</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $event_rows = $wpdb->get_results($wpdb->prepare("SELECT event_type,event_at FROM {$events} WHERE attempt_id=%d ORDER BY event_at ASC, id ASC", (int) $row['id']), ARRAY_A);
            $timeline = array_map(static fn (array $event): string => esc_html($event['event_type'] . ' · ' . $event['event_at']), $event_rows);
            printf('<tr><td><code>%s</code></td><td>%s<br><small>%s</small></td><td>%d</td><td><strong>%s</strong></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', esc_html($row['reservation_code']), esc_html($row['contact_name']), esc_html(self::mask_email($row['email'])), (int) $row['quantity'], esc_html($row['status']), ((int) $row['repeat_flag'] === 1 ? 'Yes' : 'No'), esc_html((string) ($row['woo_order_id'] ?: '—')), implode('<br>', $timeline) ?: '—', esc_html((string) ($row['failure_reason'] ?: '—')));
        }
        echo '</tbody></table></div>';
    }

    private static function mark_paid(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order || !$order->is_paid() || !$order->get_meta('_famtastic_reservation_code', true) || !absint($order->get_meta('_famtastic_attempt_id', true))) {
            return;
        }
        self::mark_order_status($order_id, 'paid');
        if (class_exists('Famtastic_Reunion_Tickets')) {
            Famtastic_Reunion_Tickets::issue_for_order($order_id);
        }
        if (!$order->get_meta('_famtastic_paid_notice_queued', true)) {
            $order->update_meta_data('_famtastic_paid_notice_queued', current_time('mysql', true));
            $order->save();
            if (function_exists('as_enqueue_async_action')) {
                as_enqueue_async_action('famtastic_reunion_send_paid_order_notice', ['order_id' => $order_id], 'famtastic-reunion', true);
            } else {
                self::send_paid_order_notice($order_id);
            }
        }
    }

    public static function send_paid_order_notice(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order || !$order->is_paid() || $order->get_meta('_famtastic_paid_notice_sent', true)) {
            return;
        }
        $email = sanitize_email($order->get_billing_email());
        if (!is_email($email)) {
            $order->add_order_note('Paid-order notice was not queued: billing email is missing.');
            return;
        }
        $code = esc_html((string) $order->get_meta('_famtastic_reservation_code', true));
        $wallet = esc_url(home_url('/portal/#wallet'));
        $sent = wp_mail($email, 'MBSH reunion admission confirmed', '<p>Your MBSH Class of 1996 reunion payment is confirmed.</p><p>Reservation: <strong>' . $code . '</strong></p><p>Your admission is available in your reunion wallet after account verification: <a href="' . $wallet . '">' . $wallet . '</a></p>', ['Content-Type: text/html; charset=UTF-8']);
        if ($sent) {
            $order->update_meta_data('_famtastic_paid_notice_sent', current_time('mysql', true));
            $order->delete_meta_data('_famtastic_paid_notice_last_error');
        } else {
            $order->update_meta_data('_famtastic_paid_notice_last_error', current_time('mysql', true));
        }
        $order->save();
    }

    private static function mark_order_status(int $order_id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $attempt_id = absint($order->get_meta('_famtastic_attempt_id', true));
        if (!$attempt_id) {
            $code = sanitize_text_field((string) $order->get_meta('_famtastic_reservation_code', true));
            $attempt_id = self::find_attempt_by_code($code);
        }
        if (!$attempt_id) {
            return;
        }
        self::link_attempt_to_order($attempt_id, $order_id);
        self::set_attempt_status($attempt_id, $status);
        self::record_event($attempt_id, 'order_' . $status, ['order_id' => $order_id, 'woo_status' => $order->get_status()]);
        $order->update_meta_data('_famtastic_payment_lifecycle', $status);
        $order->save();
    }

    private static function record_attempt(string $code, string $email, string $name, int $quantity, string $event, array $details = []): int
    {
        global $wpdb;
        self::ensure_schema();
        $table = self::attempts_table();
        $email = strtolower(trim($email));
        $hash = hash('sha256', $email);
        $now = current_time('mysql', true);
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE reservation_code=%s", $code), ARRAY_A);
        if ($existing) {
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET last_attempt_at=%s, attempt_count=attempt_count+1 WHERE id=%d", $now, (int) $existing['id']));
            self::record_event((int) $existing['id'], 'repeat_' . $event, $details);
            return (int) $existing['id'];
        }
        $repeat = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE email_hash=%s", $hash)) > 0 ? 1 : 0;
        $wpdb->insert($table, [
            'reservation_code' => $code,
            'email' => $email,
            'email_hash' => $hash,
            'contact_name' => $name,
            'quantity' => $quantity,
            'status' => 'attempt',
            'repeat_flag' => $repeat,
            'first_attempt_at' => $now,
            'last_attempt_at' => $now,
            'attempt_count' => 1,
        ], ['%s','%s','%s','%s','%d','%s','%d','%s','%s','%d']);
        $id = (int) $wpdb->insert_id;
        self::record_event($id, $event, $details);
        return $id;
    }

    private static function record_event(int $attempt_id, string $event, array $details = []): void
    {
        global $wpdb;
        if ($attempt_id <= 0) {
            return;
        }
        $wpdb->insert(self::events_table(), [
            'attempt_id' => $attempt_id,
            'event_type' => sanitize_key($event),
            'event_at' => current_time('mysql', true),
            'details_json' => wp_json_encode($details),
        ], ['%d','%s','%s','%s']);
    }

    private static function fail_attempt(int $attempt_id, string $reason): void
    {
        global $wpdb;
        $wpdb->update(self::attempts_table(), ['status' => 'abandoned', 'failure_reason' => sanitize_text_field($reason), 'last_attempt_at' => current_time('mysql', true)], ['id' => $attempt_id], ['%s','%s','%s'], ['%d']);
        self::record_event($attempt_id, 'abandoned', ['reason' => $reason]);
    }

    private static function set_attempt_status(int $attempt_id, string $status): void
    {
        global $wpdb;
        if ($attempt_id > 0 && in_array($status, self::STATUSES, true)) {
            $wpdb->update(self::attempts_table(), ['status' => $status, 'last_attempt_at' => current_time('mysql', true)], ['id' => $attempt_id], ['%s','%s'], ['%d']);
        }
    }

    private static function link_attempt_to_order(int $attempt_id, int $order_id): void
    {
        global $wpdb;
        if ($attempt_id > 0 && $order_id > 0) {
            $wpdb->update(self::attempts_table(), ['woo_order_id' => $order_id], ['id' => $attempt_id], ['%d'], ['%d']);
        }
    }

    private static function find_attempt_by_code(string $code): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . self::attempts_table() . ' WHERE reservation_code=%s', $code));
    }

    private static function split_name(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);
        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private static function mask_email(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return ($local === '' ? '—' : substr($local, 0, 1) . '***') . ($domain !== '' ? '@' . $domain : '');
    }

    private static function attempts_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'famtastic_order_attempts';
    }

    private static function events_table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'famtastic_order_attempt_events';
    }

    private static function json_error(int $status, string $code, string $message): never
    {
        status_header($status);
        wp_send_json(['error' => true, 'code' => $code, 'message' => $message], $status);
    }
}

add_action('famtastic_reunion_send_paid_order_notice', ['Famtastic_Reunion_Commerce', 'send_paid_order_notice']);
