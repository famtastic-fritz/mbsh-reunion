<?php

declare(strict_types=1);

final class Famtastic_Reunion_Tickets
{
    public static function register(): void
    {
        add_action('woocommerce_order_status_processing', [self::class, 'issue_for_order']);
        add_action('woocommerce_order_status_completed', [self::class, 'issue_for_order']);
        add_action('woocommerce_order_status_refunded', [self::class, 'revoke_for_order']);
        add_action('woocommerce_order_status_cancelled', [self::class, 'revoke_for_order']);
        add_action('woocommerce_order_status_failed', [self::class, 'revoke_for_order']);
        add_action('woocommerce_order_refunded', [self::class, 'revoke_for_refund'], 10, 2);
        add_action('woocommerce_product_options_general_product_data', [self::class, 'ticket_product_field']);
        add_action('woocommerce_process_product_meta', [self::class, 'save_ticket_product_field']);
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'limit_test_ticket_quantity'], 20, 5);
        add_action('template_redirect', [self::class, 'start_test_checkout']);
        add_action('woocommerce_email_after_order_table', [self::class, 'render_test_ticket_notice'], 20, 4);
        add_action('woocommerce_thankyou', [self::class, 'render_test_ticket_thankyou'], 20);
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route('famtastic/v1', '/tickets', [
            'methods' => 'GET',
            'callback' => [self::class, 'my_tickets'],
            'permission_callback' => [Famtastic_Reunion_REST::class, 'verified_user'],
        ]);
        register_rest_route('famtastic/v1', '/tickets/(?P<code>[A-Fa-f0-9]{32}\.[A-Fa-f0-9]{64})/check-in', [
            'methods' => 'POST',
            'callback' => [self::class, 'check_in'],
            'permission_callback' => static fn (): bool => current_user_can('edit_shop_orders'),
        ]);
    }

    public static function ticket_product_field(): void
    {
        if (!function_exists('woocommerce_wp_text_input')) {
            return;
        }
        woocommerce_wp_text_input([
            'id' => '_famtastic_ticket_event',
            'label' => 'FAMtastic ticket event key',
            'description' => 'Issue one virtual admission per paid quantity, e.g. mbsh-1996-30th.',
            'desc_tip' => true,
        ]);
    }

    public static function save_ticket_product_field(int $product_id): void
    {
        if (!current_user_can('edit_post', $product_id)) {
            return;
        }
        $value = isset($_POST['_famtastic_ticket_event'])
            ? sanitize_key(wp_unslash($_POST['_famtastic_ticket_event']))
            : '';
        update_post_meta($product_id, '_famtastic_ticket_event', $value);
    }

    public static function issue_for_order(int $order_id): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order || !$order->is_paid() || $order->get_meta('_famtastic_tickets_issued', true)) {
            return;
        }
        // The MBSH ticket product must have come through the reservation-aware
        // checkout bridge. Other event products may retain their own policy.
        $reservationCode = (string) $order->get_meta('_famtastic_reservation_code', true);
        $issuedAny = false;
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product || $product->get_meta('_famtastic_ticket_event') === '') {
                continue;
            }
            if ($product->get_meta('_famtastic_ticket_event') === 'mbsh-1996-30th' && $reservationCode === '') {
                continue;
            }
            $isTest = $product->get_meta('_famtastic_ticket_test') === 'yes';
            for ($i = 1; $i <= $item->get_quantity(); $i++) {
                if (self::issue((int) $order->get_user_id(), $order_id, (int) $item_id, $i, $isTest)) {
                    $issuedAny = true;
                }
            }
        }
        if (!$issuedAny) {
            return;
        }
        $order->update_meta_data('_famtastic_tickets_issued', current_time('mysql', true));
        $order->save();
    }

    public static function revoke_for_order(int $order_id): void
    {
        $tickets = get_posts([
            'post_type' => 'reunion_ticket',
            'post_status' => 'publish',
            'meta_key' => '_famtastic_order_id',
            'meta_value' => $order_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        foreach ($tickets as $ticket_id) {
            self::transition_to_revoked((int) $ticket_id, 'order_status', $order_id);
        }
    }

    public static function revoke_for_refund(int $order_id, int $refund_id): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }
        $lock_key = 'famtastic_refund_lock_' . $refund_id;
        if (!add_option($lock_key, time(), '', false)) {
            return;
        }
        $refund = wc_get_order($refund_id);
        if (!$refund) {
            delete_option($lock_key);
            return;
        }
        try {
            foreach ($refund->get_items('line_item') as $refund_item) {
                $original_item_id = (int) $refund_item->get_meta('_refunded_item_id', true);
                $quantity = abs((int) $refund_item->get_quantity());
                if ($original_item_id <= 0 || $quantity <= 0) {
                    continue;
                }
                $already_revoked = count(get_posts([
                    'post_type' => 'reunion_ticket',
                    'post_status' => 'publish',
                    'meta_query' => [
                        'relation' => 'AND',
                        ['key' => '_famtastic_order_id', 'value' => $order_id],
                        ['key' => '_famtastic_order_item_id', 'value' => $original_item_id],
                        ['key' => '_famtastic_ticket_revoke_source_id', 'value' => $refund_id],
                    ],
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ]));
                $remaining = max(0, $quantity - $already_revoked);
                if ($remaining === 0) {
                    continue;
                }
                $tickets = get_posts([
                    'post_type' => 'reunion_ticket',
                    'post_status' => 'publish',
                    'meta_query' => [
                        'relation' => 'AND',
                        ['key' => '_famtastic_order_id', 'value' => $order_id],
                        ['key' => '_famtastic_order_item_id', 'value' => $original_item_id],
                        ['key' => '_famtastic_ticket_status', 'value' => 'valid'],
                    ],
                    'posts_per_page' => $remaining,
                    'orderby' => 'ID',
                    'order' => 'DESC',
                    'fields' => 'ids',
                ]);
                foreach ($tickets as $ticket_id) {
                    self::transition_to_revoked((int) $ticket_id, 'refund', $refund_id);
                }
            }
        } finally {
            delete_option($lock_key);
        }
    }

    private static function issue(int $user_id, int $order_id, int $item_id, int $sequence, bool $is_test = false): bool
    {
        $public_id = bin2hex(random_bytes(16));
        $id = wp_insert_post([
            'post_type' => 'reunion_ticket',
            'post_status' => 'publish',
            'post_title' => $is_test
                ? sprintf('TEST · Order %d · Ticket %d · NOT VALID FOR ADMISSION', $order_id, $sequence)
                : sprintf('Order %d · Admission %d', $order_id, $sequence),
            'post_author' => $user_id,
        ], true);
        if (is_wp_error($id)) {
            return false;
        }
        update_post_meta($id, '_famtastic_ticket_public_id', $public_id);
        update_post_meta($id, '_famtastic_order_id', $order_id);
        update_post_meta($id, '_famtastic_order_item_id', $item_id);
        update_post_meta($id, '_famtastic_ticket_status', $is_test ? 'test' : 'valid');
        update_post_meta($id, '_famtastic_ticket_is_test', $is_test ? 'yes' : 'no');
        update_post_meta($id, '_famtastic_ticket_issued_at', current_time('mysql', true));
        do_action('famtastic_reunion_ticket_issued', $id, self::signed_code($public_id));
        return true;
    }

    public static function my_tickets(WP_REST_Request $request): WP_REST_Response
    {
        $tickets = get_posts([
            'post_type' => 'reunion_ticket',
            'post_status' => 'publish',
            'author' => get_current_user_id(),
            'posts_per_page' => 100,
        ]);
        return rest_ensure_response(array_map(static fn (WP_Post $ticket): array => [
            'id' => $ticket->ID,
            'label' => $ticket->post_title,
            'status' => get_post_meta($ticket->ID, '_famtastic_ticket_status', true),
            'issuedAt' => get_post_meta($ticket->ID, '_famtastic_ticket_issued_at', true),
            'checkedInAt' => get_post_meta($ticket->ID, '_famtastic_ticket_checked_in_at', true) ?: null,
            'code' => self::signed_code((string) get_post_meta($ticket->ID, '_famtastic_ticket_public_id', true)),
        ], $tickets));
    }

    public static function check_in(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $code = (string) $request['code'];
        $parts = explode('.', $code, 2);
        if (count($parts) !== 2 || !hash_equals(self::signature($parts[0]), $parts[1])) {
            return new WP_Error('ticket_not_found', 'Ticket is not valid.', ['status' => 404]);
        }
        $tickets = get_posts([
            'post_type' => 'reunion_ticket',
            'post_status' => 'publish',
            'meta_key' => '_famtastic_ticket_public_id',
            'meta_value' => sanitize_text_field($parts[0]),
            'posts_per_page' => 1,
        ]);
        if (!$tickets) {
            return new WP_Error('ticket_not_found', 'Ticket is not valid.', ['status' => 404]);
        }
        $id = $tickets[0]->ID;
        if (get_post_meta($id, '_famtastic_ticket_is_test', true) === 'yes') {
            return new WP_Error('test_ticket_not_admission', 'This is a test ticket and is not valid for reunion admission.', ['status' => 409]);
        }
        if (!self::atomic_status_transition($id, 'valid', 'checked_in')) {
            return new WP_Error('ticket_unavailable', 'Ticket has already been used or revoked.', ['status' => 409]);
        }
        update_post_meta($id, '_famtastic_ticket_checked_in_at', current_time('mysql', true));
        update_post_meta($id, '_famtastic_ticket_checked_in_by', get_current_user_id());
        self::append_audit($id, 'checked_in', 'staff_user', get_current_user_id());
        return rest_ensure_response(['id' => $id, 'status' => 'checked_in']);
    }

    public static function limit_test_ticket_quantity(bool $passed, int $product_id, int $quantity, int $variation_id = 0, array $variations = []): bool
    {
        if (get_post_meta($product_id, '_famtastic_ticket_test', true) === 'yes' && ($quantity < 1 || $quantity > 2)) {
            wc_add_notice('The checkout QA product is limited to two test tickets.', 'error');
            return false;
        }
        return $passed;
    }

    /**
     * Start a repeatable two-ticket QA checkout without accumulating cart items.
     *
     * The private token is stored only in WordPress. After the cart is reset, the
     * browser is redirected to the clean checkout URL so refreshing checkout does
     * not add another pair of test products.
     */
    public static function start_test_checkout(): void
    {
        if (!isset($_GET['famtastic_test_checkout']) || !function_exists('WC')) {
            return;
        }

        $expected = (string) get_option('famtastic_test_checkout_token', '');
        $provided = sanitize_text_field(wp_unslash((string) $_GET['famtastic_test_checkout']));
        if ($expected === '' || !hash_equals($expected, $provided)) {
            wp_die('This private checkout test link is not valid.', 'Invalid test link', ['response' => 403]);
        }

        if (!WC()->cart) {
            wc_load_cart();
        }
        if (!WC()->cart) {
            wp_die('The checkout cart could not be initialized.', 'Checkout unavailable', ['response' => 503]);
        }

        WC()->cart->empty_cart();
        $added = WC()->cart->add_to_cart(28, 2);
        if (!$added) {
            wp_die('The two-ticket checkout test could not be prepared.', 'Checkout unavailable', ['response' => 503]);
        }

        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

    public static function render_test_ticket_notice(WC_Order $order, bool $sent_to_admin, bool $plain_text, WC_Email $email): void
    {
        if ($sent_to_admin || !self::order_has_test_product($order)) {
            return;
        }
        $count = self::test_ticket_count((int) $order->get_id());
        $message = sprintf('TEST PURCHASE ONLY — %d non-admission test ticket(s) were created. These cannot be used for reunion entry.', $count);
        echo $plain_text ? "\n" . $message . "\n" : '<div style="margin:18px 0;padding:16px;border:3px solid #b00020;background:#fff4f4;color:#6b0013;font-weight:700">' . esc_html($message) . '</div>';
    }

    public static function render_test_ticket_thankyou(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (!$order || !self::order_has_test_product($order)) {
            return;
        }
        printf('<div style="margin:20px 0;padding:18px;border:4px solid #b00020;background:#fff4f4"><strong>TEST — NOT VALID FOR ADMISSION</strong><p>%d non-admission test ticket(s) were created for this QA purchase.</p></div>', self::test_ticket_count($order_id));
    }

    private static function order_has_test_product(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->get_meta('_famtastic_ticket_test') === 'yes') {
                return true;
            }
        }
        return false;
    }

    private static function test_ticket_count(int $order_id): int
    {
        return count(get_posts([
            'post_type' => 'reunion_ticket',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_famtastic_order_id', 'value' => $order_id],
                ['key' => '_famtastic_ticket_is_test', 'value' => 'yes'],
            ],
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]));
    }

    private static function transition_to_revoked(int $ticket_id, string $reason, int $source_id): bool
    {
        if (!self::atomic_status_transition($ticket_id, 'valid', 'revoked')) {
            return false;
        }
        update_post_meta($ticket_id, '_famtastic_ticket_revoked_at', current_time('mysql', true));
        update_post_meta($ticket_id, '_famtastic_ticket_revoke_reason', sanitize_key($reason));
        update_post_meta($ticket_id, '_famtastic_ticket_revoke_source_id', $source_id);
        self::append_audit($ticket_id, 'revoked', $reason, $source_id);
        do_action('famtastic_reunion_ticket_revoked', $ticket_id, $reason, $source_id);
        return true;
    }

    private static function atomic_status_transition(int $ticket_id, string $from, string $to): bool
    {
        global $wpdb;
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = '_famtastic_ticket_status' AND meta_value = %s",
            $to,
            $ticket_id,
            $from
        ));
        if ($updated === 1) {
            wp_cache_delete($ticket_id, 'post_meta');
            return true;
        }
        return false;
    }

    private static function append_audit(int $ticket_id, string $event, string $source, int $source_id): void
    {
        add_post_meta($ticket_id, '_famtastic_ticket_audit', wp_json_encode([
            'event' => sanitize_key($event),
            'source' => sanitize_key($source),
            'sourceId' => $source_id,
            'at' => current_time('mysql', true),
        ]));
    }

    private static function signed_code(string $public_id): string
    {
        return $public_id . '.' . self::signature($public_id);
    }

    private static function signature(string $public_id): string
    {
        return hash_hmac('sha256', $public_id, wp_salt('auth'));
    }
}
