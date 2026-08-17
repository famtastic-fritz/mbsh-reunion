<?php
/**
 * Plugin Name: FAMtastic Reunion Platform
 * Description: Headless reunion accounts, moderated memories, preferences, and WooCommerce-backed virtual tickets.
 * Version: 0.4.1
 * Author: FAMtastic Designs
 * Requires at least: 6.6
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('FAMTASTIC_REUNION_VERSION', '0.4.1');
define('FAMTASTIC_REUNION_FILE', __FILE__);

require_once __DIR__ . '/includes/class-content.php';
require_once __DIR__ . '/includes/class-rest.php';
require_once __DIR__ . '/includes/class-tickets.php';
require_once __DIR__ . '/includes/class-operations.php';
require_once __DIR__ . '/includes/class-brand-experience.php';
require_once __DIR__ . '/includes/class-seo.php';
require_once __DIR__ . '/includes/class-access.php';
require_once __DIR__ . '/includes/class-integrations.php';
require_once __DIR__ . '/includes/class-resend-mailer.php';

add_action('plugins_loaded', static function (): void {
    Famtastic_Reunion_Content::register();
    Famtastic_Reunion_REST::register();
    Famtastic_Reunion_Tickets::register();
    Famtastic_Reunion_Operations::register();
    Famtastic_Reunion_Brand_Experience::register();
    Famtastic_Reunion_SEO::register();
    Famtastic_Reunion_Access::register();
    Famtastic_Reunion_Integrations::register();
    Famtastic_Reunion_Resend_Mailer::register();
});

register_activation_hook(__FILE__, static function (): void {
    Famtastic_Reunion_Content::register_types();
    Famtastic_Reunion_Access::ensure_roles();
    Famtastic_Reunion_Operations::schedule();
    Famtastic_Reunion_Integrations::schedule();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    wp_clear_scheduled_hook('famtastic_reunion_health_check');
    wp_clear_scheduled_hook('famtastic_reunion_marketing_sync');
    if (function_exists('as_unschedule_all_actions')) {
        as_unschedule_all_actions('famtastic_reunion_reconciliation_check', [], 'famtastic-reunion');
        as_unschedule_all_actions('famtastic_reunion_delivery_check', [], 'famtastic-reunion');
    }
    flush_rewrite_rules();
});
