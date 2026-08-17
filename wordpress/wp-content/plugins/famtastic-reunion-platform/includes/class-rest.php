<?php

declare(strict_types=1);

final class Famtastic_Reunion_REST
{
    private const NS = 'famtastic/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route(self::NS, '/me', [
            'methods' => 'GET',
            'callback' => [self::class, 'me'],
            'permission_callback' => [self::class, 'verified_user'],
        ]);
        register_rest_route(self::NS, '/memories', [
            'methods' => 'POST',
            'callback' => [self::class, 'create_memory'],
            'permission_callback' => [self::class, 'verified_user'],
        ]);
        register_rest_route(self::NS, '/suggestions', [
            'methods' => 'POST',
            'callback' => [self::class, 'create_suggestion'],
            'permission_callback' => 'is_user_logged_in',
        ]);
        register_rest_route(self::NS, '/preferences', [
            [
                'methods' => 'GET',
                'callback' => [self::class, 'preferences'],
                'permission_callback' => [self::class, 'verified_user'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [self::class, 'update_preferences'],
                'permission_callback' => [self::class, 'verified_user'],
            ],
        ]);
    }

    public static function me(WP_REST_Request $request): WP_REST_Response
    {
        $user = wp_get_current_user();
        return rest_ensure_response([
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'roles' => array_values($user->roles),
            'emailVerified' => (bool) get_user_meta($user->ID, 'famtastic_email_verified_at', true),
        ]);
    }

    public static function verified_user(): bool|WP_Error
    {
        if (!is_user_logged_in()) {
            return new WP_Error('authentication_required', 'Authentication required.', ['status' => 401]);
        }
        if (!get_user_meta(get_current_user_id(), 'famtastic_email_verified_at', true)) {
            return new WP_Error('email_verification_required', 'Email verification required.', ['status' => 403]);
        }
        return true;
    }

    public static function create_memory(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $title = sanitize_text_field((string) $request->get_param('title'));
        $story = wp_kses_post((string) $request->get_param('story'));
        $rights = sanitize_key((string) $request->get_param('rights'));
        if ($title === '' || $story === '' || !in_array($rights, ['private_archive', 'committee', 'public'], true)) {
            return new WP_Error('invalid_memory', 'Title, story, and a valid usage permission are required.', ['status' => 422]);
        }
        $id = wp_insert_post([
            'post_type' => 'reunion_memory',
            'post_status' => 'pending',
            'post_title' => $title,
            'post_content' => $story,
            'post_author' => get_current_user_id(),
        ], true);
        if (is_wp_error($id)) {
            return $id;
        }
        update_post_meta($id, '_famtastic_usage_rights', $rights);
        update_post_meta($id, '_famtastic_submitted_at', current_time('mysql', true));
        return new WP_REST_Response(['id' => $id, 'status' => 'pending'], 201);
    }

    public static function create_suggestion(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $body = sanitize_textarea_field((string) $request->get_param('message'));
        if (mb_strlen($body) < 10 || mb_strlen($body) > 5000) {
            return new WP_Error('invalid_suggestion', 'Suggestion must be 10–5,000 characters.', ['status' => 422]);
        }
        $id = wp_insert_post([
            'post_type' => 'reunion_suggestion',
            'post_status' => 'pending',
            'post_title' => wp_trim_words($body, 8, '…'),
            'post_content' => $body,
            'post_author' => get_current_user_id(),
        ], true);
        return is_wp_error($id) ? $id : new WP_REST_Response(['id' => $id, 'status' => 'received'], 201);
    }

    public static function preferences(WP_REST_Request $request): WP_REST_Response
    {
        $id = get_current_user_id();
        return rest_ensure_response([
            'eventUpdates' => self::bool_meta($id, 'famtastic_event_updates', true),
            'promotions' => self::bool_meta($id, 'famtastic_promotions', false),
            'memoryNotifications' => self::bool_meta($id, 'famtastic_memory_notifications', true),
        ]);
    }

    public static function update_preferences(WP_REST_Request $request): WP_REST_Response
    {
        $id = get_current_user_id();
        $map = [
            'eventUpdates' => 'famtastic_event_updates',
            'promotions' => 'famtastic_promotions',
            'memoryNotifications' => 'famtastic_memory_notifications',
        ];
        foreach ($map as $input => $meta) {
            if ($request->has_param($input)) {
                update_user_meta($id, $meta, rest_sanitize_boolean($request->get_param($input)) ? '1' : '0');
            }
        }
        update_user_meta($id, 'famtastic_preferences_updated_at', current_time('mysql', true));
        return self::preferences($request);
    }

    private static function bool_meta(int $user_id, string $key, bool $default): bool
    {
        $value = get_user_meta($user_id, $key, true);
        return $value === '' ? $default : $value === '1';
    }
}
