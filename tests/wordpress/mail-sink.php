<?php
/** Disposable Docker proof: capture synthetic mail, never use SMTP or HTTP. */
declare(strict_types=1);
if (getenv('WORDPRESS_DB_NAME') !== 'mixer_wp' || getenv('WORDPRESS_DB_HOST') !== 'mbsh-mixer-wc-db-20261016') {
    throw new RuntimeException('Mail sink requires the isolated mixer database.');
}
$GLOBALS['mbsh_proof_mail_http'] = [];
$GLOBALS['mbsh_proof_mail_fail'] = false;
// Suppress WordPress setup mail before the reunion adapter is activated.
add_filter('pre_wp_mail', static fn() => true, -1000);
add_filter('pre_http_request', static function ($preempt, $args, $url) {
    if ($url !== 'https://api.resend.com/emails') {
        return new WP_Error('proof_network_disabled', 'External providers disabled in isolated proof.');
    }
    $payload = json_decode($args['body'] ?? '', true);
    if (empty($payload['to']) || ($args['headers']['Authorization'] ?? '') !== 'Bearer isolated-proof-no-provider') {
        throw new RuntimeException('Only fictional mail configuration is allowed.');
    }
    foreach ($payload['to'] as $recipient) {
        if (!str_ends_with($recipient, '@example.test')) {
            throw new RuntimeException('Only example.test recipients are allowed in the mail proof.');
        }
    }
    $GLOBALS['mbsh_proof_mail_http'][] = [
        'payload' => $payload,
        'idempotency_key' => $args['headers']['Idempotency-Key'] ?? '',
    ];
    if ($GLOBALS['mbsh_proof_mail_fail']) {
        return new WP_Error('proof_simulated_outage', 'Simulated provider outage.');
    }
    return ['headers' => [], 'body' => '{"id":"isolated-mock-message"}',
        'response' => ['code' => 200, 'message' => 'OK'], 'cookies' => []];
}, -1000, 3);
