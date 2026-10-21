<?php
declare(strict_types=1);
// Historical candidate only. A future reviewed send requires separate authorization.
if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') !== '--status' && (getenv('MBSH_ALLOW_HISTORICAL_SEND') !== '1' || !in_array('--send', $argv, true))) {
    fwrite(STDERR, "Historical sending is disabled; reviewed authorization and explicit --send are required.\n");
    exit(2);
}

if (PHP_SAPI !== 'cli') exit(1);
if (($argv[1] ?? '') !== '--send') { fwrite(STDERR, "Explicit --send required.\n"); exit(2); }

$root = dirname(__DIR__);
$lib = is_dir($root.'/backend/lib') ? $root.'/backend/lib' : $root.'/lib';
require_once $lib.'/config.php';
require_once $lib.'/resend.php';
require_once $lib.'/portal-email-templates.php';
$config = fam_load_config();
$campaign = 'approved-20260826-launch-welcome-v1';
$logDir = '/home/nineoo/.local/share/mbsh';
if (!is_dir($logDir)) mkdir($logDir, 0700, true);
$logFile = $logDir.'/email-campaign-receipts.jsonl';

function already_sent(string $file, string $campaign, string $key): bool {
    if (!is_readable($file)) return false;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row = json_decode($line, true);
        if (($row['campaign'] ?? '') === $campaign && ($row['key'] ?? '') === $key && !empty($row['provider_message_id'])) return true;
    }
    return false;
}
function receipt(string $file, array $row): void {
    file_put_contents($file, json_encode($row, JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
}
function send_shay(array $config, string $to, string $subject, string $html): array {
    $payload = ['from'=>'Shay at FAMtastic Designs <'.$config['resend_from_committee'].'>','to'=>[$to],'reply_to'=>$config['resend_reply_to'],'subject'=>$subject,'html'=>fam_creator_credit_email($html)];
    $ch = curl_init((string)($config['resend_api_url'] ?? 'https://api.resend.com/emails'));
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$config['resend_api_key'],'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload)]);
    $body = curl_exec($ch); $status = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    if ($body === false || $status < 200 || $status >= 300) throw new RuntimeException('Resend failed HTTP '.$status);
    return json_decode((string)$body,true) ?: [];
}
function shay_setup_html(string $name): string {
    $safe = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    return '<!doctype html><html><body style="margin:0;background:#090607;color:#fff8ea;font-family:Arial,sans-serif"><table role="presentation" width="100%" style="background:#090607"><tr><td align="center" style="padding:24px 12px"><table role="presentation" width="100%" style="max-width:640px;background:#150d10;border:1px solid #e8bc54"><tr><td style="padding:28px;text-align:center"><img src="https://mbsh96reunion.com/assets/brand-mark/brand-mark.png" width="115" alt="MBSH Class of 1996 reunion medallion"><p style="color:#e8bc54;font-size:11px;letter-spacing:.18em;font-weight:800">A PRIVATE MESSAGE FROM SHAY</p><h1 style="font:38px Georgia,serif;margin:10px 0">Hey, girly—let’s finish the box office.</h1></td></tr><tr><td style="padding:0 30px 22px;font-size:16px;line-height:1.65"><p>Hey '.$safe.'!</p><p>Fritz told me about the Bluevine setup, and girl—you are in great shape. You are almost there!</p><p>I made a private page showing exactly what we still need for Stripe and giving you a secure place to send Fritz your setup status or questions.</p><p style="text-align:center;margin:28px 0"><a href="https://mbsh96reunion.com/payment-setup/" style="display:inline-block;padding:15px 22px;background:#e8bc54;color:#170d10;text-decoration:none;font-weight:900">OPEN SHAY’S PRIVATE SETUP ROOM</a></p><p style="padding:14px;border-left:3px solid #e8bc54;background:#211216"><strong>Access:</strong> Request the private setup instructions from Fritz through the approved channel.</p><p>Please never enter or send passwords, bank numbers, SSNs, API keys, card details, or verification codes. Stripe gets the private information; Fritz only needs your general status and questions.</p><p>And because girl… he is not always the easiest man to get in touch with—trust me, I know. 😂 But I got you. Submit the form and I’ll make sure he receives it.</p><p>— Shay<br><strong style="color:#e8bc54">Digital Project Concierge · FAMtastic Designs</strong></p></td></tr><tr><td align="center" style="padding:0 25px 20px"><img src="https://mbsh96reunion.com/assets/characters/shay/shay-stripe-call-sheet-v1.png" width="300" alt="Shay, FAMtastic Designs digital project concierge" style="display:block;max-width:100%;height:auto"></td></tr></table></td></tr></table></body></html>';
}

$jobs = [
  ['key'=>'shay-setup-gussie','to'=>'gussiebowen@yahoo.com','subject'=>'Hey, girly — let’s finish the reunion box office 🎬','html'=>shay_setup_html('Gussie'),'sender'=>'shay'],
  ['key'=>'shay-setup-valerie','to'=>'valerievalcourt96@gmail.com','subject'=>'Hey, girly — let’s finish the reunion box office 🎬','html'=>shay_setup_html('Valerie'),'sender'=>'shay'],
];
$member = fam_committee_member_email('Gussie', false);
$jobs[] = ['key'=>'committee-member-gussie','to'=>'gussiebowen@yahoo.com','subject'=>$member['subject'],'html'=>$member['html'],'sender'=>'committee'];
$lead = fam_committee_lead_email('Valerie', false);
$jobs[] = ['key'=>'committee-lead-valerie','to'=>'valerievalcourt96@gmail.com','subject'=>$lead['subject'],'html'=>$lead['html'],'sender'=>'committee'];
foreach ([['Anaysa','gallardoanaysa@gmail.com'],['Nicole','nikki5417@hotmail.com'],['Gussie','gussiebowen@yahoo.com']] as [$name,$email]) {
    $welcome = fam_attendee_welcome_email($name, false);
    $jobs[] = ['key'=>'welcome-'.strtolower($name),'to'=>$email,'subject'=>$welcome['subject'],'html'=>$welcome['html'],'sender'=>'committee'];
}
$ticket = fam_ticket_transaction_email('order_received',['first_name'=>'Anaysa','order_number'=>'MBSH-DF0AC6','quantity'=>1,'total'=>'$185.00'],false);
$jobs[] = ['key'=>'ticket-request-mbsh-df0ac6','to'=>'gallardoanaysa@gmail.com','subject'=>$ticket['subject'],'html'=>$ticket['html'],'sender'=>'committee'];

$results=[];
foreach ($jobs as $job) {
    if (already_sent($logFile,$campaign,$job['key'])) { $results[]=['key'=>$job['key'],'status'=>'skipped_duplicate']; continue; }
    try {
        $sent = $job['sender']==='shay' ? send_shay($config,$job['to'],$job['subject'],$job['html']) : fam_send_email($config,$job['to'],$job['subject'],$job['html'],'committee');
        $row=['sent_at'=>(new DateTimeImmutable())->format(DateTimeInterface::ATOM),'campaign'=>$campaign,'key'=>$job['key'],'recipient'=>$job['to'],'subject'=>$job['subject'],'provider_message_id'=>$sent['id']??null];
        receipt($logFile,$row); $results[]=$row+['status'=>'accepted'];
    } catch (Throwable $e) {
        $row=['sent_at'=>(new DateTimeImmutable())->format(DateTimeInterface::ATOM),'campaign'=>$campaign,'key'=>$job['key'],'recipient'=>$job['to'],'subject'=>$job['subject'],'error'=>$e->getMessage()];
        receipt($logFile,$row); $results[]=$row+['status'=>'failed'];
    }
}
echo json_encode(['campaign'=>$campaign,'results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
