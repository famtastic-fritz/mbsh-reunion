<?php
declare(strict_types=1);
// Historical candidate only. A future reviewed send requires separate authorization.
if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') !== '--status' && (getenv('MBSH_ALLOW_HISTORICAL_SEND') !== '1' || !in_array('--send', $argv, true))) {
    fwrite(STDERR, "Historical sending is disabled; reviewed authorization and explicit --send are required.\n");
    exit(2);
}
$argv = array_values(array_filter($argv, static fn($arg) => $arg !== '--send'));


if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$recipient = strtolower(trim((string)($argv[1] ?? '')));
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/send-committee-email-drafts.php owner@example.com\n");
    exit(1);
}

$root = dirname(__DIR__);
$lib = is_dir($root . '/backend/lib') ? $root . '/backend/lib' : $root . '/lib';
require_once $lib . '/config.php';
require_once $lib . '/resend.php';
require_once $lib . '/portal-email-templates.php';

$config = fam_load_config();
$messages = [
    'committee_member' => fam_committee_member_email('Committee Member', true),
    'committee_lead' => fam_committee_lead_email('Committee Lead', true),
    'attendee_welcome' => fam_attendee_welcome_email('Hi-Tide', true),
    'ticket_request' => fam_ticket_transaction_email('order_received', ['first_name'=>'Hi-Tide', 'order_number'=>'MBSH-PREVIEW', 'quantity'=>1, 'total'=>'$185.00'], true),
    'payment_confirmed' => fam_ticket_transaction_email('payment_confirmed', ['first_name'=>'Hi-Tide', 'order_number'=>'MBSH-PREVIEW', 'quantity'=>1, 'total'=>'$185.00'], true),
    'ticket_issued' => fam_ticket_transaction_email('ticket_issued', ['first_name'=>'Hi-Tide', 'order_number'=>'MBSH-PREVIEW', 'quantity'=>1, 'total'=>'$185.00'], true),
];
$receipts = [];
foreach ($messages as $template => $message) {
    $result = fam_send_email($config, $recipient, $message['subject'], $message['html'], 'committee');
    $receipts[] = ['template'=>$template, 'recipient'=>$recipient, 'provider_message_id'=>$result['id'] ?? null];
}
echo json_encode(['sent'=>count($receipts), 'receipts'=>$receipts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
