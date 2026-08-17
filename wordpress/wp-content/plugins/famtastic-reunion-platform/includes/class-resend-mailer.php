<?php

declare(strict_types=1);

/** Sends WordPress transactional mail through the verified reunion Resend domain. */
final class Famtastic_Reunion_Resend_Mailer
{
    public static function register(): void
    {
        add_filter('pre_wp_mail', [self::class, 'send'], 10, 2);
    }

    public static function configured(): bool
    {
        return defined('FAMTASTIC_RESEND_API_KEY')
            && trim((string) FAMTASTIC_RESEND_API_KEY) !== ''
            && defined('FAMTASTIC_RESEND_FROM')
            && is_email((string) FAMTASTIC_RESEND_FROM);
    }

    public static function send(null|bool $return, array $attributes): null|bool
    {
        if (!self::configured()) {
            return $return;
        }

        $recipients = array_values(array_filter(array_map(
            'sanitize_email',
            is_array($attributes['to'] ?? null) ? $attributes['to'] : preg_split('/\s*,\s*/', (string) ($attributes['to'] ?? ''))
        )));
        if ($recipients === []) {
            return false;
        }

        $headers = self::headers($attributes['headers'] ?? []);
        $payload = [
            'from' => (string) FAMTASTIC_RESEND_FROM,
            'to' => $recipients,
            'subject' => wp_strip_all_tags((string) ($attributes['subject'] ?? '')),
            'html' => (string) ($attributes['message'] ?? ''),
            'reply_to' => $headers['reply_to'] ?? (defined('FAMTASTIC_RESEND_REPLY_TO') ? (string) FAMTASTIC_RESEND_REPLY_TO : ''),
        ];
        if ($payload['reply_to'] === '') {
            unset($payload['reply_to']);
        }

        $attachments = self::attachments((array) ($attributes['attachments'] ?? []));
        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        $response = wp_remote_post('https://api.resend.com/emails', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . (string) FAMTASTIC_RESEND_API_KEY,
                'Content-Type' => 'application/json',
                'Idempotency-Key' => 'wp-' . hash('sha256', implode('|', $recipients) . '|' . $payload['subject'] . '|' . $payload['html']),
            ],
            'body' => wp_json_encode($payload),
        ]);

        $status = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
        $body = is_wp_error($response) ? [] : json_decode((string) wp_remote_retrieve_body($response), true);
        $success = $status >= 200 && $status < 300 && !empty($body['id']);

        update_option('famtastic_resend_delivery_status', [
            'at' => current_time('mysql', true),
            'status' => $success ? 'sent' : 'failed',
            'provider_id' => $success ? sanitize_text_field((string) $body['id']) : '',
            'http_status' => $status,
        ], false);

        if (!$success) {
            do_action('wp_mail_failed', new WP_Error('famtastic_resend_failed', 'Resend rejected WordPress email.', ['status' => $status]));
        }
        return $success;
    }

    private static function headers(array|string $headers): array
    {
        $lines = is_array($headers) ? $headers : preg_split('/\r?\n/', $headers);
        $parsed = [];
        foreach ($lines ?: [] as $line) {
            if (stripos((string) $line, 'reply-to:') === 0) {
                $parsed['reply_to'] = sanitize_email(trim(substr((string) $line, 9)));
            }
        }
        return $parsed;
    }

    private static function attachments(array $paths): array
    {
        $attachments = [];
        foreach ($paths as $path) {
            if (!is_string($path) || !is_readable($path) || filesize($path) > 10 * MB_IN_BYTES) {
                continue;
            }
            $content = file_get_contents($path);
            if ($content !== false) {
                $attachments[] = ['filename' => sanitize_file_name(basename($path)), 'content' => base64_encode($content)];
            }
        }
        return $attachments;
    }
}
