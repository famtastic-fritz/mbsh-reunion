<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/lib/ticket-payments.php';

function check_email(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

// Rendering must remain usable offline, without loading a provider or sending.
foreach (['order_received', 'payment_confirmed', 'ticket_issued', 'payment_failed', 'order_cancelled', 'refunded'] as $state) {
    $message = fam_ticket_transaction_email($state, [
        'first_name' => '<script>alert(1)</script>',
        'order_number' => 'MBSH-FIXTURE',
        'quantity' => 2,
        'total' => '$400.00',
        'payment_url' => 'javascript:alert(1)',
    ], true);
    check_email(!str_contains($message['html'], '<script>'), 'Holder text must be escaped.');
    check_email(!str_contains($message['html'], 'javascript:'), 'Unsafe payment URLs must be rejected.');
    check_email(substr_count($message['html'], 'data-famtastic-email-credit') === 1, 'Render must contain one final creator logo.');
    check_email(fam_creator_credit_email($message['html']) === $message['html'], 'The send wrapper must not duplicate credit.');
}
try {
    fam_ticket_transaction_email('unknown', []);
    throw new RuntimeException('Unsupported states must fail.');
} catch (InvalidArgumentException $expected) {}

check_email(!fam_ticket_payment_link_is_open(new DateTimeImmutable('2026-10-04', new DateTimeZone('America/New_York'))), 'Old Early Bird link must remain closed.');
check_email(fam_ticket_payment_expected_amount(2) === 381.34, 'Retain main payment amount behavior.');
check_email(!function_exists('fam_send_email'), 'Renderer must not load or invoke the mail transport.');
echo "PASS offline email rendering, escaping, invalid states, creator credit, and main payment-link expiry\n";
