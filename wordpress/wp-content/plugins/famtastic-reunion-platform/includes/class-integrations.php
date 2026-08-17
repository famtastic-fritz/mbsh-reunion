<?php

declare(strict_types=1);

/** Owner-only integration health and consent-safe marketing synchronization. */
final class Famtastic_Reunion_Integrations
{
    private const CRM_LIST = 'Reunion Community Updates';
    private const CRM_LIST_SLUG = 'reunion-community-updates';
    private const CRM_TAG = 'Portal promotional opt-in';
    private const CRM_TAG_SLUG = 'portal-promotional-opt-in';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'menu'], 40);
        add_action('famtastic_reunion_marketing_sync', [self::class, 'sync_marketing_consent']);
        add_action('init', [self::class, 'schedule'], 30);
        add_action('admin_post_famtastic_marketing_sync', [self::class, 'manual_sync']);
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled('famtastic_reunion_marketing_sync')) {
            wp_schedule_event(time() + 600, 'hourly', 'famtastic_reunion_marketing_sync');
        }
    }

    public static function menu(): void
    {
        add_menu_page(
            'Growth & Delivery',
            'Growth & Delivery',
            'manage_options',
            'famtastic-growth-delivery',
            [self::class, 'render'],
            'dashicons-megaphone',
            57
        );
    }

    public static function manual_sync(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Owner access required.', '', ['response' => 403]);
        }
        check_admin_referer('famtastic_marketing_sync');
        self::sync_marketing_consent();
        wp_safe_redirect(add_query_arg('famtastic-sync', 'complete', admin_url('admin.php?page=famtastic-growth-delivery')));
        exit;
    }

    /**
     * FluentCRM receives only verified attendees who explicitly enabled
     * promotional email. Portal transactional preferences never enter CRM.
     */
    public static function sync_marketing_consent(): array
    {
        $result = ['eligible' => 0, 'subscribed' => 0, 'suppressed' => 0, 'errors' => []];
        if (!function_exists('FluentCrmApi')) {
            $result['errors'][] = 'FluentCRM is unavailable.';
            return self::record_sync($result);
        }

        global $wpdb;
        $required = ['attendee_accounts', 'attendee_profiles', 'attendee_preferences'];
        foreach ($required as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
                $result['errors'][] = "Missing portal table: {$table}";
                return self::record_sync($result);
            }
        }

        try {
            $lists = FluentCrmApi('lists')->importBulk([[
                'title' => self::CRM_LIST,
                'slug' => self::CRM_LIST_SLUG,
                'description' => 'Verified reunion attendees who explicitly opted into promotional email in the attendee portal.',
            ]]);
            $tags = FluentCrmApi('tags')->importBulk([[
                'title' => self::CRM_TAG,
                'slug' => self::CRM_TAG_SLUG,
                'description' => 'Consent authority: attendee_preferences.promotional_email.',
            ]]);
            $listId = (int) ($lists[0]->id ?? 0);
            $tagId = (int) ($tags[0]->id ?? 0);
            if (!$listId || !$tagId) {
                throw new RuntimeException('Could not establish the consent list and tag.');
            }

            $rows = $wpdb->get_results(
                "SELECT a.email,a.email_verified_at,a.status,p.first_name,p.last_name,pr.promotional_email
                 FROM attendee_accounts a
                 JOIN attendee_profiles p ON p.attendee_id=a.id
                 JOIN attendee_preferences pr ON pr.attendee_id=a.id",
                ARRAY_A
            );

            foreach ($rows as $row) {
                $existing = FluentCrmApi('contacts')->getContact((string) $row['email']);
                $eligible = $row['status'] === 'active'
                    && !empty($row['email_verified_at'])
                    && (int) $row['promotional_email'] === 1;
                if ($eligible) {
                    $result['eligible']++;
                    $contact = FluentCrmApi('contacts')->createOrUpdate([
                        'email' => (string) $row['email'],
                        'first_name' => (string) $row['first_name'],
                        'last_name' => (string) $row['last_name'],
                        'status' => 'subscribed',
                        'source' => 'MBSH attendee portal consent',
                    ], false, false);
                    if ($contact) {
                        $contact->attachLists([$listId]);
                        $contact->attachTags([$tagId]);
                        $result['subscribed']++;
                    }
                } elseif ($existing && method_exists($existing, 'hasAnyTagId') && $existing->hasAnyTagId([$tagId])) {
                    $existing->status = 'unsubscribed';
                    $existing->save();
                    $result['suppressed']++;
                }
            }
        } catch (Throwable $exception) {
            $result['errors'][] = $exception->getMessage();
        }
        return self::record_sync($result);
    }

    private static function record_sync(array $result): array
    {
        $result['at'] = current_time('mysql', true);
        update_option('famtastic_reunion_marketing_sync_status', $result, false);
        return $result;
    }

    public static function render(): void
    {
        $status = get_option('famtastic_reunion_marketing_sync_status', []);
        $heartbeat = get_option('famtastic_reunion_worker_heartbeat', []);
        $cards = [
            ['Email delivery', Famtastic_Reunion_Resend_Mailer::configured() ? 'Connected to Resend' : 'Needs configuration', admin_url('admin.php?page=famtastic-growth-delivery')],
            ['Permission-based marketing', is_plugin_active('fluent-crm/fluent-crm.php') ? 'FluentCRM active' : 'Needs configuration', admin_url('admin.php?page=fluentcrm-admin')],
            ['Public forms & surveys', is_plugin_active('fluentform/fluentform.php') ? 'Fluent Forms active' : 'Needs configuration', admin_url('admin.php?page=fluent_forms')],
            ['Scheduled work', is_plugin_active('wp-crontrol/wp-crontrol.php') ? 'Cron visibility active' : 'Needs configuration', admin_url('tools.php?page=crontrol_admin_manage_page')],
            ['Owner security', is_plugin_active('two-factor/two-factor.php') ? 'Two-Factor ready to enroll' : 'Needs configuration', admin_url('profile.php')],
        ];
        echo '<div class="wrap famtastic-integrations"><p class="famtastic-eyebrow">OWNER OPERATIONS</p><h1>Growth & Delivery</h1>';
        echo '<p class="famtastic-lede">One place to understand how permission-based marketing, public forms, delivery, security, and scheduled work fit the Event Cinema platform.</p>';
        if (isset($_GET['famtastic-sync'])) {
            echo '<div class="notice notice-success"><p>Marketing consent synchronization completed. No campaign was sent.</p></div>';
        }
        echo '<div class="famtastic-integration-grid">';
        foreach ($cards as [$label, $value, $url]) {
            printf('<article><p>%s</p><h2>%s</h2><a class="button" href="%s">Open tool</a></article>', esc_html($label), esc_html($value), esc_url($url));
        }
        echo '</div><section class="famtastic-doctrine"><h2>Authority rules</h2><ol>';
        echo '<li><strong>Attendee portal</strong> owns identity, verification, RSVP, preferences, private uploads, messages, and wallet access.</li>';
        echo '<li><strong>WordPress</strong> owns editable event content, FAQs, approved collections, committee forms, and marketing operations.</li>';
        echo '<li><strong>WooCommerce</strong> owns products, orders, refunds, and payment-backed tickets.</li>';
        echo '<li><strong>Resend</strong> delivers mail. Transactional notices remain separate from promotions.</li>';
        echo '<li><strong>Public frontend</strong> owns the cinematic experience and search index. The CMS remains noindex.</li>';
        echo '</ol></section>';
        echo '<section class="famtastic-doctrine"><h2>Consent bridge</h2><p>Only verified attendees with <code>promotional_email = 1</code> are subscribed to the FluentCRM list. Opt-outs are suppressed on the next run.</p>';
        printf('<p>Last sync: <strong>%s</strong> · eligible %d · subscribed %d · suppressed %d</p>', esc_html((string) ($status['at'] ?? 'Not run')), (int) ($status['eligible'] ?? 0), (int) ($status['subscribed'] ?? 0), (int) ($status['suppressed'] ?? 0));
        if (!empty($status['errors'])) {
            echo '<p class="notice notice-error">' . esc_html(implode(' ', (array) $status['errors'])) . '</p>';
        }
        printf('<form method="post" action="%s">', esc_url(admin_url('admin-post.php')));
        wp_nonce_field('famtastic_marketing_sync');
        echo '<input type="hidden" name="action" value="famtastic_marketing_sync"><button class="button button-primary">Sync consent now</button></form></section>';
        printf('<section class="famtastic-doctrine"><h2>Worker proof</h2><p>Last platform heartbeat: <strong>%s</strong>. Use Scheduled Work to inspect missed or late tasks.</p></section>', esc_html((string) ($heartbeat['at'] ?? 'Not recorded')));
        echo '</div>';
    }
}
