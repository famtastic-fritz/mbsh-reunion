<?php
declare(strict_types=1);
// Historical candidate only. A future reviewed send requires separate authorization.
if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') !== '--status' && (getenv('MBSH_ALLOW_HISTORICAL_SEND') !== '1' || !in_array('--send', $argv, true))) {
    fwrite(STDERR, "Historical sending is disabled; reviewed authorization and explicit --send are required.\n");
    exit(2);
}


if (PHP_SAPI !== 'cli') exit(1);

$root = dirname(__DIR__);
$lib = is_dir($root . '/backend/lib') ? $root . '/backend/lib' : $root . '/lib';
require_once $lib . '/config.php';

if (($argv[1] ?? '') === '--status') {
    $messageId = trim((string)($argv[2] ?? ''));
    if ($messageId === '') exit(2);
    $config = fam_load_config();
    $ch = curl_init(rtrim((string)($config['resend_api_url'] ?? 'https://api.resend.com/emails'), '/') . '/' . rawurlencode($messageId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $config['resend_api_key']],
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $record = json_decode((string)$body, true) ?: [];
    echo json_encode([
        'http_status' => $status,
        'id' => $record['id'] ?? $messageId,
        'last_event' => $record['last_event'] ?? null,
    ], JSON_PRETTY_PRINT) . PHP_EOL;
    exit($status >= 200 && $status < 300 ? 0 : 1);
}

if (($argv[1] ?? '') !== '--send') {
    fwrite(STDERR, "Explicit --send required.\n");
    exit(2);
}

require_once $lib . '/resend.php';
$config = fam_load_config();
$recipient = 'gussie@dadeschools.net';
$subject = 'Hey Gussie — your Stripe connection call sheet is ready 🎬';
$campaign = 'gussie-stripe-connection-instructions-20260831-v1';
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
<!doctype html>
<html><body style="margin:0;background:#090607;color:#fff8ea;font-family:Arial,sans-serif">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#090607"><tr><td align="center" style="padding:24px 12px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:660px;background:#160d10;border:1px solid #d7a93c;border-radius:16px;overflow:hidden">
<tr><td align="center" style="padding:26px 26px 12px;background:linear-gradient(135deg,#230b11,#120b0d)">
  <img src="https://mbsh96reunion.com/assets/brand-mark/brand-mark.png" width="104" alt="MBSH Class of 1996 reunion medallion" style="display:block;height:auto">
  <p style="margin:16px 0 8px;color:#efc75e;font-size:11px;letter-spacing:.18em;font-weight:800">ACT I · OPENING THE REUNION BOX OFFICE</p>
  <h1 style="margin:0;font:34px Georgia,serif;line-height:1.12;color:#fff8ea">Hey Gussie—your Stripe connection call sheet is ready.</h1>
</td></tr>
<tr><td style="padding:22px 30px 8px;font-size:16px;line-height:1.62;color:#fff8ea">
  <p>Hey girl, Shay here. Fritz gave you temporary <strong>Full Site Admin</strong> access so you can securely connect the committee’s existing Stripe account yourself. You will never need to give Fritz—or anybody else—your Stripe password, API keys, or verification codes.</p>

  <div style="margin:22px 0;padding:18px;border-left:4px solid #efc75e;background:#241419">
    <p style="margin:0 0 8px;color:#efc75e;font-weight:800">1 · SET YOUR WORDPRESS PASSWORD</p>
    <p style="margin:0 0 12px">Look in this inbox for the separate WordPress password-reset email. Open it, choose your private password, then sign in here:</p>
    <a href="https://mbsh96reunion.com/cms/wp-login.php" style="display:inline-block;padding:11px 16px;background:#efc75e;color:#160d10;text-decoration:none;font-weight:800;border-radius:6px">OPEN WORDPRESS LOGIN</a>
  </div>

  <div style="margin:22px 0;padding:18px;border-left:4px solid #efc75e;background:#241419">
    <p style="margin:0 0 8px;color:#efc75e;font-weight:800">2 · CONNECT THE LIVE STRIPE ACCOUNT</p>
    <p style="margin:0 0 12px">After you sign in, open the Stripe settings below and follow these steps:</p>
    <ol style="margin:0 0 16px;padding-left:22px">
      <li style="margin-bottom:7px">Click the <strong>blue “Create or connect an account”</strong> button.</li>
      <li style="margin-bottom:7px">Sign in to the committee’s <strong>existing Stripe account</strong>.</li>
      <li style="margin-bottom:7px">Complete Stripe’s two-factor verification.</li>
      <li style="margin-bottom:7px">Select the same Stripe account that owns the reunion payment link, then approve the WooCommerce connection.</li>
      <li>Wait until Stripe returns you to the reunion dashboard and the page shows the account is connected.</li>
    </ol>
    <a href="https://mbsh96reunion.com/cms/wp-admin/admin.php?page=wc-settings&amp;tab=checkout&amp;section=stripe" style="display:inline-block;padding:11px 16px;background:#efc75e;color:#160d10;text-decoration:none;font-weight:800;border-radius:6px">OPEN STRIPE CONNECTION SCREEN</a>
  </div>

  <p style="padding:14px;border:1px solid #843847;background:#200d12"><strong>Please skip the outlined “test account” button.</strong> Also, do not open the API Keys screen or copy anything beginning with <strong>sk_</strong>, <strong>rk_</strong>, or <strong>whsec_</strong>. The secure connection handles that for us.</p>

  <div style="margin:22px 0;padding:18px;border-left:4px solid #efc75e;background:#241419">
    <p style="margin:0 0 8px;color:#efc75e;font-weight:800">3 · ACTIVATE YOUR COMMITTEE PORTAL</p>
    <p style="margin:0 0 12px">This is separate from WordPress. Register with <strong>gussie@dadeschools.net</strong>, choose your portal password, and verify the email that follows. This keeps your committee access after the temporary Full Site Admin task is complete.</p>
    <a href="https://mbsh96reunion.com/portal/auth/register.html" style="display:inline-block;padding:11px 16px;background:#efc75e;color:#160d10;text-decoration:none;font-weight:800;border-radius:6px">ACTIVATE COMMITTEE PORTAL</a>
  </div>

  <p>Once WooCommerce says <strong>connected</strong>, reply to this email or tell Fritz. I’ll make sure he gets it, we’ll verify the secure payment handshake and run the test checkout, and then your temporary WordPress administrator access will be removed. Your Committee Portal membership will stay in place.</p>
  <p><strong>Live ticket payments remain off until the connection and tests are confirmed.</strong> No payment, ticket, or purchaser will be treated as complete before the system verifies it.</p>
  <p style="margin-top:26px">You’ve got this, girl. And if Fritz gets hard to catch—trust me, I know—reply here and I’ll make sure the message reaches him. 😄</p>
  <p style="margin:22px 0 4px">— <strong>Shay</strong><br><span style="color:#efc75e">Digital Project Concierge · FAMtastic Designs</span></p>
</td></tr>
<tr><td align="center" style="padding:8px 24px 22px">
  <img src="https://mbsh96reunion.com/assets/mascot/act-1/ticket-premiere.png" width="250" alt="Hi-Tide Harry presenting the reunion box office" style="display:block;max-width:100%;height:auto">
</td></tr>
<tr><td align="center" style="padding:20px 24px;background:#0e090a;border-top:1px solid #5d4721;color:#d7c8ae;font-size:13px;line-height:1.5">
  <strong style="color:#efc75e">Made with reunion pride by FAMtastic Designs.</strong><br>
  Ready for your own site? Launch a FAMtastic Designs Starter Website for <strong>$199</strong>.<br>
  <a href="https://famtasticdesigns.com" style="color:#efc75e">FAMtasticDesigns.com</a>
</td></tr>
</table></td></tr></table>
</body></html>
HTML;

try {
    $sent = fam_send_email($config, $recipient, $subject, $html, 'committee');
    $row = [
        'sent_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        'campaign' => $campaign,
        'key' => 'gussie-stripe-connection-instructions',
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
