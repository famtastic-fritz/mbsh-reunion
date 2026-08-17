<?php

declare(strict_types=1);

final class Famtastic_Reunion_SEO
{
    private const TYPES = ['reunion_event', 'reunion_memory'];
    private const FIELDS = [
        '_famtastic_seo_title' => 'Search title',
        '_famtastic_seo_description' => 'Search description',
        '_famtastic_social_image' => 'Social image URL',
        '_famtastic_canonical_url' => 'Public canonical URL',
    ];

    public static function register(): void
    {
        add_action('init', [self::class, 'register_meta']);
        add_action('add_meta_boxes', [self::class, 'meta_box']);
        add_action('save_post', [self::class, 'save'], 10, 2);
        add_filter('wp_robots', [self::class, 'headless_robots']);
        add_filter('wp_sitemaps_enabled', [self::class, 'cms_sitemaps']);
    }

    public static function register_meta(): void
    {
        foreach (self::TYPES as $type) {
            foreach (array_keys(self::FIELDS) as $key) {
                register_post_meta($type, $key, [
                    'type' => 'string',
                    'single' => true,
                    'show_in_rest' => true,
                    'sanitize_callback' => $key === '_famtastic_social_image' || $key === '_famtastic_canonical_url'
                        ? 'esc_url_raw'
                        : 'sanitize_text_field',
                    'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
                ]);
            }
        }
    }

    public static function meta_box(): void
    {
        foreach (self::TYPES as $type) {
            add_meta_box('famtastic-search-preview', 'Public Search & Sharing', [self::class, 'render'], $type, 'normal', 'high');
        }
    }

    public static function render(WP_Post $post): void
    {
        wp_nonce_field('famtastic_seo_save', 'famtastic_seo_nonce');
        echo '<p>These values are consumed by the cinematic public frontend. The CMS itself stays out of search results to prevent duplicate pages.</p>';
        foreach (self::FIELDS as $key => $label) {
            $value = (string) get_post_meta($post->ID, $key, true);
            $max = $key === '_famtastic_seo_title' ? 70 : ($key === '_famtastic_seo_description' ? 170 : 500);
            printf(
                '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" id="%1$s" name="%1$s" type="%3$s" maxlength="%4$d" value="%5$s"></p>',
                esc_attr($key),
                esc_html($label),
                str_contains($key, 'url') || str_contains($key, 'image') ? 'url' : 'text',
                $max,
                esc_attr($value)
            );
        }
    }

    public static function save(int $postId, WP_Post $post): void
    {
        if (!in_array($post->post_type, self::TYPES, true)
            || !isset($_POST['famtastic_seo_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['famtastic_seo_nonce'])), 'famtastic_seo_save')
            || !current_user_can('edit_post', $postId)
            || wp_is_post_autosave($postId)
            || wp_is_post_revision($postId)) {
            return;
        }
        foreach (array_keys(self::FIELDS) as $key) {
            if (!isset($_POST[$key])) {
                continue;
            }
            $raw = wp_unslash($_POST[$key]);
            $value = str_contains($key, 'url') || str_contains($key, 'image') ? esc_url_raw($raw) : sanitize_text_field($raw);
            $value === '' ? delete_post_meta($postId, $key) : update_post_meta($postId, $key, $value);
        }
    }

    public static function headless_robots(array $robots): array
    {
        if (defined('FAMTASTIC_PUBLIC_CMS') && FAMTASTIC_PUBLIC_CMS === true) {
            return $robots;
        }
        $robots['noindex'] = true;
        $robots['nofollow'] = false;
        $robots['noarchive'] = true;
        unset($robots['index']);
        return $robots;
    }

    public static function cms_sitemaps(bool $enabled): bool
    {
        return defined('FAMTASTIC_PUBLIC_CMS') && FAMTASTIC_PUBLIC_CMS === true ? $enabled : false;
    }
}
