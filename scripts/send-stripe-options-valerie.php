<?php
declare(strict_types=1);
// Historical candidate only. A future reviewed send requires separate authorization.
if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') !== '--status' && (getenv('MBSH_ALLOW_HISTORICAL_SEND') !== '1' || !in_array('--send', $argv, true))) {
    fwrite(STDERR, "Historical sending is disabled; reviewed authorization and explicit --send are required.\n");
    exit(2);
}


if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') === '--status') {
    $messageId = trim((string)($argv[2] ?? ''));
    if ($messageId === '') exit(2);
    $root = dirname(__DIR__);
    $lib = is_dir($root . '/backend/lib') ? $root . '/backend/lib' : $root . '/lib';
    require_once $lib . '/config.php';
    $config = fam_load_config();
    $ch = curl_init(rtrim((string)($config['resend_api_url'] ?? 'https://api.resend.com/emails'), '/') . '/' . rawurlencode($messageId));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $config['resend_api_key']]]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $record = json_decode((string)$body, true) ?: [];
    echo json_encode(['http_status' => $status, 'id' => $record['id'] ?? $messageId, 'last_event' => $record['last_event'] ?? null], JSON_PRETTY_PRINT) . PHP_EOL;
    exit($status >= 200 && $status < 300 ? 0 : 1);
}

if (($argv[1] ?? '') !== '--send') {
    fwrite(STDERR, "Explicit --send required.\n");
    exit(2);
}

$root = dirname(__DIR__);
$lib = is_dir($root . '/backend/lib') ? $root . '/backend/lib' : $root . '/lib';
require_once $lib . '/config.php';
require_once $lib . '/resend.php';

$config = fam_load_config();
$recipient = 'valerievalcourt96@gmail.com';
$subject = 'The Stripe button is ready — here are our two ticket options';
$campaign = 'stripe-options-valerie-20260831-v1';
$logDir = '/home/nineoo/.local/share/mbsh';
$logFile = $logDir . '/email-campaign-receipts.jsonl';

if (!is_dir($logDir)) mkdir($logDir, 0700, true);
if (is_readable($logFile)) {
    foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row = json_decode($line, true);
        if (($row['campaign'] ?? '') === $campaign && !empty($row['provider_message_id'])) {
            echo json_encode(['status' => 'skipped_duplicate', 'campaign' => $campaign]) . PHP_EOL;
            exit(0);
        }
    }
}

$html = <<<'HTML'
<!doctype html><html><body style="margin:0;background:#090607;color:#fff8ea;font-family:Arial,sans-serif"><table role="presentation" width="100%" style="background:#090607"><tr><td align="center" style="padding:24px 12px"><table role="presentation" width="100%" style="max-width:640px;background:#150d10;border:1px solid #e8bc54"><tr><td style="padding:26px;text-align:center"><img src="https://mbsh96reunion.com/assets/brand-mark/brand-mark.png" width="105" alt="MBSH Class of 1996 reunion medallion"><p style="color:#e8bc54;font-size:11px;letter-spacing:.18em;font-weight:800">ACT I · THE BOX OFFICE IS ALMOST OPEN</p><h1 style="font:34px Georgia,serif;margin:10px 0">Hey Valerie—here’s what the Stripe button gives us.</h1></td></tr><tr><td style="padding:0 30px 24px;font-size:16px;line-height:1.62"><p>We have a live Stripe Buy Button. Once it is placed on the reunion site, guests can securely pay <strong>$190.67 per ticket</strong>. Stripe processes the card, deposits the funds, and provides its standard receipt.</p><p><strong>Option 1 — Use the button by itself.</strong><br>The committee manually matches every Stripe payment to a reunion request, marks it paid, and sends the personalized ticket.</p><p><strong>Option 2 — Add a webhook.</strong><br>A webhook is a secure automatic message from Stripe that tells our website, “This order was paid.” The site can then match the purchaser, mark the order paid, generate the ticket, email it, and correctly handle failed payments or refunds.</p><p style="padding:14px;border-left:3px solid #e8bc54;background:#211216"><strong>My recommendation:</strong> add the webhook. It takes a little more setup from the authorized Stripe account owner, but it saves the committee ongoing work and reduces mistakes.</p><p>Just reply and tell me which option the committee wants, and I’ll make sure Fritz has the decision.</p><p style="margin-top:26px">— <strong>Shay</strong><br><span style="color:#e8bc54">Digital Project Concierge · FAMtastic Designs</span></p></td></tr><tr><td align="center" style="padding:0 25px 20px"><img src="https://mbsh96reunion.com/assets/characters/shay/shay-stripe-call-sheet-v1.png" width="270" alt="Shay, FAMtastic Designs digital project concierge" style="display:block;max-width:100%;height:auto"></td></tr></table></td></tr></table></body></html>
HTML;

try {
    $sent = fam_send_email($config, $recipient, $subject, $html, 'committee');
    $row = [
        'sent_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        'campaign' => $campaign,
        'key' => 'stripe-options-valerie',
        'recipient' => $recipient,
        'subject' => $subject,
        'provider_message_id' => $sent['id'] ?? null,
    ];
    file_put_contents($logFile, json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    echo json_encode($row + ['status' => 'accepted'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
