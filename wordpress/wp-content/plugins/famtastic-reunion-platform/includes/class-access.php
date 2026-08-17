<?php

declare(strict_types=1);

/** Least-privilege committee identity and login routing. */
final class Famtastic_Reunion_Access
{
    public const ROLE = 'famtastic_committee_admin';

    public static function register(): void
    {
        add_action('init', [self::class, 'ensure_roles'], 5);
        add_action('admin_init', [self::class, 'protect_full_admin'], 1);
        add_filter('login_redirect', [self::class, 'login_redirect'], 999, 3);
        add_filter('allowed_redirect_hosts', [self::class, 'allow_frontend_host']);
        add_filter('user_row_actions', [self::class, 'label_committee_users'], 10, 2);
        add_filter('admin_body_class', [self::class, 'admin_body_class']);
    }

    /** Keep role upgrades idempotent without granting platform administration. */
    public static function ensure_roles(): void
    {
        $capabilities = [
            'read' => true,
            'upload_files' => true,
            'edit_posts' => true,
            'edit_others_posts' => true,
            'edit_published_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'delete_others_posts' => true,
            'delete_published_posts' => true,
            'read_private_posts' => true,
            'manage_categories' => true,
            'famtastic_access_committee' => true,
            'famtastic_manage_reunion' => true,
            'view_woocommerce_reports' => true,
            'edit_products' => true,
            'edit_others_products' => true,
            'edit_published_products' => true,
            'publish_products' => true,
            'read_private_products' => true,
            'edit_shop_orders' => true,
            'edit_others_shop_orders' => true,
            'edit_published_shop_orders' => true,
            'read_private_shop_orders' => true,
        ];

        $role = get_role(self::ROLE);
        if (!$role) {
            $role = add_role(self::ROLE, 'Committee Admin', $capabilities);
        }
        if ($role instanceof WP_Role) {
            foreach ($capabilities as $capability => $grant) {
                $role->add_cap($capability, $grant);
            }
            foreach (['activate_plugins', 'install_plugins', 'update_plugins', 'switch_themes', 'edit_theme_options', 'manage_options', 'promote_users', 'create_users', 'delete_users'] as $forbidden) {
                $role->remove_cap($forbidden);
            }
        }

        $administrator = get_role('administrator');
        if ($administrator instanceof WP_Role) {
            $administrator->add_cap('famtastic_access_committee');
            $administrator->add_cap('famtastic_manage_reunion');
        }
    }

    public static function protect_full_admin(): void
    {
        if (wp_doing_ajax() || !is_user_logged_in() || current_user_can('manage_options')) {
            return;
        }
        wp_safe_redirect(self::admin_portal_url());
        exit;
    }

    public static function login_redirect(string $redirectTo, string $requested, WP_User|WP_Error $user): string
    {
        if ($user instanceof WP_User && user_can($user, 'manage_options')) {
            return admin_url();
        }
        if ($user instanceof WP_User && user_can($user, 'famtastic_access_committee')) return self::admin_portal_url();
        if ($user instanceof WP_User) {
            return self::attendee_portal_url();
        }
        return $redirectTo;
    }

    public static function allow_frontend_host(array $hosts): array
    {
        $host = wp_parse_url(self::attendee_portal_url(), PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            $hosts[] = $host;
        }
        return array_values(array_unique($hosts));
    }

    public static function label_committee_users(array $actions, WP_User $user): array
    {
        if (in_array(self::ROLE, $user->roles, true)) {
            $actions['famtastic_committee_role'] = '<span class="famtastic-role-label">Committee Admin</span>';
        }
        return $actions;
    }

    public static function admin_body_class(string $classes): string
    {
        if (current_user_can('famtastic_access_committee') && !current_user_can('manage_options')) {
            $classes .= ' famtastic-committee-admin';
        }
        return $classes;
    }

    private static function attendee_portal_url(): string
    {
        if (defined('FAMTASTIC_FRONTEND_URL') && is_string(FAMTASTIC_FRONTEND_URL)) {
            return trailingslashit(FAMTASTIC_FRONTEND_URL) . 'portal/';
        }
        return home_url('/');
    }

    private static function admin_portal_url(): string
    {
        if (defined('FAMTASTIC_FRONTEND_URL') && is_string(FAMTASTIC_FRONTEND_URL)) return trailingslashit(FAMTASTIC_FRONTEND_URL) . 'portal/admin/';
        return home_url('/portal/admin/');
    }
}
