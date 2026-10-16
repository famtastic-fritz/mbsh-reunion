<?php

declare(strict_types=1);

/** Current offer guard. WooCommerce still owns all financial records. */
final class Famtastic_Reunion_Mixer_Offer
{
    public const ID = 'mbsh-mixer-2026-11-07-v1';
    public const PRODUCT_ID = 26;
    public const UNIT_AMOUNT = 30.0;

    public static function register(): void
    {
        add_action('woocommerce_before_calculate_totals', [self::class, 'normalize_cart'], 1000);
        add_action('woocommerce_check_cart_items', [self::class, 'validate_cart']);
        add_action('woocommerce_after_calculate_totals', [self::class, 'validate_cart_total'], 1000);
        add_action('woocommerce_store_api_cart_errors', [self::class, 'store_cart_errors'], 10, 2);
        add_filter('woocommerce_order_needs_payment', [self::class, 'order_needs_payment'], 1000, 3);
        add_filter('woocommerce_valid_order_statuses_for_payment', [self::class, 'payable_statuses'], 1000, 2);
        add_action('woocommerce_checkout_create_order', [self::class, 'guard_new_order'], 1000);
        add_action('woocommerce_store_api_checkout_order_processed', [self::class, 'guard_new_order'], 9);
        add_action('woocommerce_email_after_order_table', [self::class, 'render_event_details'], 30, 4);
        add_action('woocommerce_thankyou', [self::class, 'render_thankyou_details'], 30);
    }

    public static function ready(): bool
    {
        return get_woocommerce_currency() === 'USD'
            && get_option('famtastic_mixer_offer_revision', '') === self::ID;
    }

    public static function product_ready($product): bool
    {
        return self::ready() && $product
            && (int) $product->get_id() === self::PRODUCT_ID
            && $product->get_meta('_famtastic_ticket_event') === 'mbsh-1996-30th'
            && $product->get_meta('_famtastic_offer_id') === self::ID
            && (float) $product->get_price() === self::UNIT_AMOUNT;
    }

    public static function is_ticket_line(array $line): bool
    {
        return (int) ($line['product_id'] ?? 0) === self::PRODUCT_ID;
    }

    public static function current_line(array $line): bool
    {
        return ($line['famtastic_offer_id'] ?? '') === self::ID
            && !empty($line['famtastic_reservation_code']);
    }

    public static function normalize_cart($cart): void
    {
        foreach ($cart->get_cart() as $key => $line) {
            if (!self::is_ticket_line($line)) {
                continue;
            }
            if (!self::current_line($line)) {
                $cart->remove_cart_item($key);
                wc_add_notice('The reunion offer has changed. Please start a new $30 mixer reservation on the ticket page.', 'notice');
                continue;
            }
            // A changed catalog must fail validation, not silently bill a different offer.
            if (self::product_ready($line['data'] ?? null)) {
                $line['data']->set_price(self::UNIT_AMOUNT);
            }
        }
    }

    public static function cart_error($cart): string
    {
        $quantity = 0;
        $has_ticket = false;
        foreach ($cart->get_cart() as $line) {
            if (!self::is_ticket_line($line)) {
                continue;
            }
            $has_ticket = true;
            if (!self::current_line($line) || !self::product_ready($line['data'] ?? null)) {
                return 'The mixer checkout is being updated. Please return to the ticket page and start again.';
            }
            $quantity += (int) $line['quantity'];
        }
        if ($has_ticket && ($quantity < 1 || $quantity > 10 || count($cart->get_cart()) !== 1)) {
            return 'Mixer checkout must contain one reservation for 1–10 admissions. Please start again on the ticket page.';
        }
        return '';
    }

    public static function validate_cart(): void
    {
        if (function_exists('WC') && WC() && WC()->cart && ($error = self::cart_error(WC()->cart)) !== '') {
            wc_add_notice($error, 'error');
        }
    }

    public static function store_cart_errors($errors, $cart)
    {
        if (($error = self::cart_error($cart)) !== '') {
            $errors->add('mixer_offer_unavailable', $error);
        }
        if (self::has_ticket($cart) && !self::total_matches($cart)) {
            $errors->add('mixer_total_mismatch', 'Mixer admission is $30 per person. Checkout is paused because the total does not match.');
        }
        return $errors;
    }

    private static function has_ticket($cart): bool
    {
        foreach ($cart->get_cart() as $line) {
            if (self::is_ticket_line($line)) {
                return true;
            }
        }
        return false;
    }

    public static function total_matches($cart): bool
    {
        $quantity = 0;
        foreach ($cart->get_cart() as $line) {
            if (self::is_ticket_line($line)) {
                $quantity += (int) $line['quantity'];
            }
        }
        return abs((float) $cart->get_total('edit') - self::UNIT_AMOUNT * $quantity) < 0.005;
    }

    public static function validate_cart_total($cart): void
    {
        if (self::has_ticket($cart) && !self::total_matches($cart)) {
            wc_add_notice('Mixer admission is $30 per person. Checkout is paused because the total does not match.', 'error');
        }
    }

    public static function is_ticket_order($order): bool
    {
        foreach ($order->get_items() as $item) {
            if ((int) $item->get_product_id() === self::PRODUCT_ID) {
                return true;
            }
        }
        return false;
    }

    public static function order_current($order): bool
    {
        if (!self::is_ticket_order($order)) {
            return true;
        }
        if (!self::ready() || $order->get_currency() !== 'USD' || $order->get_meta('_famtastic_offer_id') !== self::ID) {
            return false;
        }
        $quantity = 0;
        foreach ($order->get_items() as $item) {
            if ((int) $item->get_product_id() !== self::PRODUCT_ID) {
                return false;
            }
            $quantity += (int) $item->get_quantity();
        }
        return $quantity >= 1 && $quantity <= 10
            && abs((float)$order->get_total() - self::UNIT_AMOUNT * $quantity) < 0.005;
    }

    public static function order_needs_payment(bool $needs_payment, $order, array $statuses = []): bool
    {
        return self::order_current($order) ? $needs_payment : false;
    }

    public static function payable_statuses(array $statuses, $order): array
    {
        return self::order_current($order) ? $statuses : [];
    }

    public static function guard_new_order($order): void
    {
        if (!self::order_current($order)) {
            throw new Exception('The reunion offer has changed. Start a new $30 mixer reservation at /tickets.html.');
        }
    }

    public static function render_event_details($order, bool $sent_to_admin = false, bool $plain_text = false, $email = null): void
    {
        if ($order->get_meta('_famtastic_offer_id') !== self::ID) {
            return;
        }
        $details = 'MBSH Class of 1996 Reunion Mixer · Twisted Tonic · Saturday, November 7, 2026 · 7–10 PM Eastern. 723 Lincoln Ln N, Miami Beach, FL 33139. Food on your own.';
        echo $plain_text ? "\n" . $details . "\n" : '<p>' . esc_html($details) . '</p>';
    }

    public static function render_thankyou_details(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if ($order) {
            self::render_event_details($order);
        }
    }
}
