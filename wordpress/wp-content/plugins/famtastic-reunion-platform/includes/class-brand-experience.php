<?php

declare(strict_types=1);

final class Famtastic_Reunion_Brand_Experience
{
    public static function register(): void
    {
        add_action('admin_enqueue_scripts', [self::class, 'admin_assets']);
        add_action('login_enqueue_scripts', [self::class, 'login_assets']);
        add_action('admin_bar_menu', [self::class, 'brand_toolbar'], 100);
        add_action('admin_menu', [self::class, 'brand_menu'], 999);
        add_action('admin_init', [self::class, 'remove_generic_welcome']);
        add_action('wp_dashboard_setup', [self::class, 'dashboard'], 999);
        add_action('admin_footer', [self::class, 'harry_operator_guide']);
        add_filter('admin_footer_text', [self::class, 'footer']);
        add_filter('update_footer', [self::class, 'version_footer'], 20);
        add_filter('login_headerurl', static fn (): string => home_url('/'));
        add_filter('login_headertext', static fn (): string => 'MBSH Class of 1996 · Committee Portal');
        add_filter('login_title', static fn (): string => 'Sign in ‹ MBSH ’96 Committee Portal');
        add_filter('gettext', [self::class, 'login_language'], 20, 3);
        add_filter('admin_title', [self::class, 'admin_title'], 10, 2);
        add_filter('get_user_option_meta-box-order_dashboard', [self::class, 'command_center_first']);
    }

    public static function admin_assets(): void
    {
        wp_enqueue_style(
            'famtastic-reunion-admin',
            plugins_url('assets/admin.css', FAMTASTIC_REUNION_FILE),
            [],
            FAMTASTIC_REUNION_VERSION
        );
        wp_enqueue_script(
            'famtastic-reunion-admin',
            plugins_url('assets/admin.js', FAMTASTIC_REUNION_FILE),
            [],
            FAMTASTIC_REUNION_VERSION,
            true
        );
    }

    public static function login_assets(): void
    {
        wp_enqueue_style(
            'famtastic-reunion-login',
            plugins_url('assets/login.css', FAMTASTIC_REUNION_FILE),
            [],
            FAMTASTIC_REUNION_VERSION
        );
    }

    public static function brand_toolbar(WP_Admin_Bar $bar): void
    {
        $bar->remove_node('wp-logo');
        $accessLabel = current_user_can('manage_options') ? 'Site Owner' : 'Committee Admin';
        if (current_user_can('famtastic_access_committee') && !current_user_can('manage_options')) {
            $bar->remove_node('comments');
            $bar->remove_node('new-content');
        }
        $bar->add_node([
            'id' => 'famtastic-event-cinema',
            'title' => '<span class="famtastic-toolbar-mark" aria-hidden="true">96</span><span class="famtastic-toolbar-label">FAMtastic Event Cinema · ' . esc_html($accessLabel) . '</span>',
            'href' => admin_url(),
            'meta' => ['title' => 'FAMtastic Event Cinema command center'],
        ]);
    }

    public static function dashboard(): void
    {
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
        remove_meta_box('dashboard_primary', 'dashboard', 'side');
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
        if (current_user_can('famtastic_access_committee') && !current_user_can('manage_options')) {
            remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
            remove_meta_box('dashboard_activity', 'dashboard', 'normal');
            remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        }
        add_meta_box(
            'famtastic_reunion_command_center',
            'Tonight’s Command Center',
            [self::class, 'dashboard_widget'],
            'dashboard',
            'normal',
            'high'
        );
    }

    public static function remove_generic_welcome(): void
    {
        remove_action('welcome_panel', 'wp_welcome_panel');
    }

    public static function brand_menu(): void
    {
        global $menu, $submenu;
        $renames = [
            'index.php' => 'Command Center',
            'edit.php' => 'Announcements',
            'upload.php' => 'Media Archive',
            'edit.php?post_type=page' => 'Program Pages',
        ];
        foreach ($menu as &$item) {
            if (isset($item[2], $renames[$item[2]])) {
                $item[0] = $renames[$item[2]];
            }
        }
        unset($item);
        if (isset($submenu['index.php'][0])) {
            $submenu['index.php'][0][0] = 'Overview';
        }
        remove_menu_page('edit-comments.php');
        if (current_user_can('famtastic_access_committee') && !current_user_can('manage_options')) {
            remove_submenu_page('woocommerce', 'wc-admin&path=/extensions');
        }
    }

    public static function dashboard_widget(): void
    {
        $accessLabel = current_user_can('manage_options') ? 'Site Owner' : 'Committee Admin';
        $links = [
            ['label' => 'Event details', 'url' => admin_url('edit.php?post_type=reunion_event')],
            ['label' => 'Memory review', 'url' => admin_url('edit.php?post_type=reunion_memory')],
            ['label' => 'Suggestions', 'url' => admin_url('edit.php?post_type=reunion_suggestion')],
            ['label' => 'Virtual tickets', 'url' => admin_url('edit.php?post_type=reunion_ticket')],
            ['label' => 'Orders', 'url' => admin_url('admin.php?page=wc-orders')],
        ];
        echo '<div class="famtastic-command-center">';
        echo '<p class="famtastic-eyebrow">MBSH CLASS OF 1996 · 30TH REUNION</p>';
        echo '<h2>The countdown is active.</h2>';
        echo '<p><strong>Signed in as ' . esc_html($accessLabel) . '.</strong></p>';
        echo '<p>Manage the event, community archive, orders, tickets, and attendee experience from one branded workspace.</p>';
        echo '<div class="famtastic-command-grid">';
        foreach ($links as $link) {
            printf('<a href="%s">%s <span aria-hidden="true">→</span></a>', esc_url($link['url']), esc_html($link['label']));
        }
        echo '</div></div>';
    }

    public static function harry_operator_guide(): void
    {
        if (!current_user_can('famtastic_access_committee') && !current_user_can('manage_options')) {
            return;
        }
        $screen = get_current_screen();
        $screenId = $screen ? (string) $screen->id : 'dashboard';
        echo '<aside class="famtastic-harry-guide" data-famtastic-harry-guide data-screen="' . esc_attr($screenId) . '" aria-label="Hi-Tide Harry operator guide">';
        echo '<button class="famtastic-harry-toggle" type="button" aria-expanded="false" aria-controls="famtastic-harry-panel"><span aria-hidden="true">H</span><strong>Ask Harry</strong></button>';
        echo '<section id="famtastic-harry-panel" hidden><button class="famtastic-harry-close" type="button" aria-label="Close Harry guide">×</button><p class="famtastic-eyebrow">BACKSTAGE GUIDE</p><h2>Hi-Tide Harry</h2><p data-harry-context>I’ll explain what this screen controls and where to complete the job.</p>';
        echo '<form data-harry-wp-form><label for="famtastic-harry-question">What are you trying to do?</label><input id="famtastic-harry-question" name="question" maxlength="240" required placeholder="Approve a memory, answer a question…"><button class="button button-primary" type="submit">Ask Harry</button></form>';
        echo '<div class="famtastic-harry-answer" data-harry-wp-answer aria-live="polite"></div><p class="famtastic-harry-boundary">Harry provides operating guidance. WordPress permissions and audited portal actions remain the authority.</p></section></aside>';
    }

    public static function footer(): string
    {
        return 'MBSH Class of 1996 Event Cinema · Crafted and operated by <strong>FAMtastic Designs</strong>';
    }

    public static function version_footer(string $text): string
    {
        return 'Event Cinema platform ' . esc_html(FAMTASTIC_REUNION_VERSION);
    }

    public static function admin_title(string $adminTitle, string $title): string
    {
        return trim($title . ' ‹ FAMtastic Event Cinema');
    }

    public static function login_language(string $translated, string $text, string $domain): string
    {
        if (($GLOBALS['pagenow'] ?? '') === 'wp-login.php' && $text === 'Log In') {
            return 'Committee Sign In';
        }
        return $translated;
    }

    public static function command_center_first(mixed $order): mixed
    {
        if (!is_array($order)) {
            return $order;
        }
        $id = 'famtastic_reunion_command_center';
        $normal = array_values(array_filter(explode(',', (string) ($order['normal'] ?? '')), static fn (string $item): bool => $item !== '' && $item !== $id));
        $order['normal'] = implode(',', array_merge([$id], $normal));
        return $order;
    }
}
