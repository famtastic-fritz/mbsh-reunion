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
require_once $lib . '/resend.php';
require_once $lib . '/portal-email-templates.php';

$config = fam_load_config();
$campaign = 'ticket-launch-handoff-committee-20260902-v1';
$recipients = [
    'Valerie' => 'valerievalcourt96@gmail.com',
    'Gussie' => 'gussie@dadeschools.net',
];
$subject = 'Reunion ticket payments are ready — here’s the process';
$logDir = '/home/nineoo/.local/share/mbsh';
$logFile = $logDir . '/email-campaign-receipts.jsonl';

if (($argv[1] ?? '') === '--status') {
    $ids = array_slice($argv, 2);
    foreach ($ids as $id) {
        $ch = curl_init(rtrim((string)($config['resend_api_url'] ?? 'https://api.resend.com/emails'), '/') . '/' . rawurlencode($id));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$config['resend_api_key']]]);
        $body = curl_exec($ch);$status = curl_getinfo($ch,CURLINFO_HTTP_CODE);$record = json_decode((string)$body,true) ?: [];
        echo json_encode(['http_status'=>$status,'id'=>$record['id']??$id,'to'=>$record['to']??null,'last_event'=>$record['last_event']??null],JSON_UNESCAPED_SLASHES).PHP_EOL;
    }
    exit;
}
if (($argv[1] ?? '') !== '--send') { fwrite(STDERR,"Explicit --send required.\n"); exit(2); }
if (!is_dir($logDir)) mkdir($logDir,0700,true);

$sentKeys = [];
if (is_readable($logFile)) {
    foreach (file($logFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row=json_decode($line,true);
        if (($row['campaign']??'')===$campaign && !empty($row['recipient'])) $sentKeys[strtolower((string)$row['recipient'])]=true;
    }
}

$body = <<<'HTML'
<p>Hi Valerie and Gussie,</p>
<p><strong>Good news—the reunion ticket-payment process is now live and ready to use.</strong></p>
<h2 style="color:#e8bc54;font-family:Georgia,serif">How each purchase works</h2>
<ol style="padding-left:22px"><li>The guest completes the ticket-order form on the reunion website.</li><li>The site assigns a unique order number, such as <strong>MBSH-ABC123</strong>.</li><li>The guest pays through the committee’s secure Stripe button or QR code.</li><li>Stripe processes the payment and sends its official receipt.</li><li>The reunion request stays <strong>Payment Pending</strong> until the successful payment is verified in Stripe.</li><li>After Fritz confirms it in the Site Owner ticket desk, the system sends the branded payment confirmation, adds a portal notification, issues the personalized wallet ticket, and sends the “Your Ticket Is Ready” email.</li></ol>
<h2 style="color:#e8bc54;font-family:Georgia,serif">What to check for every purchase</h2>
<ul style="padding-left:22px"><li>The payment says <strong>successful</strong> in Stripe.</li><li>The payer email matches the reunion order.</li><li>The item is the MBSH Early Bird reunion admission.</li><li>The Stripe payment, charge, or checkout reference is copied.</li><li>The final amount received is recorded.</li></ul>
<p style="padding:15px;border-left:4px solid #e8bc54;background:#211216"><strong>Please send Fritz:</strong> the reunion order number, purchaser email, Stripe reference, and final amount received. Never send bank passwords, card numbers, Stripe passwords, or financial credentials.</p>
<p>A receipt or screenshot by itself does not create a ticket. The successful transaction must be verified in Stripe first.</p>
<h2 style="color:#e8bc54;font-family:Georgia,serif">Important checkout details</h2>
<ul style="padding-left:22px"><li>The current Stripe link processes <strong>one ticket per checkout</strong>.</li><li>Stripe currently displays a <strong>$191 subtotal</strong>, not $190.67.</li><li>Stripe may calculate applicable tax after the purchaser enters an address.</li><li>QR-code buyers must use the same email entered on the reunion ticket form.</li><li>If the guest has a verified reunion account, the ticket enters the private wallet after confirmation. Otherwise, it waits safely until that same email is verified.</li></ul>
<h2 style="color:#e8bc54;font-family:Georgia,serif">Sample Ticket Studio</h2>
<p>The Committee Portal also has a Sample Ticket Studio. You can enter a sample holder name, choose the ticket label, generate a preview, and print it. Sample tickets are marked <strong>SAMPLE — NOT VALID FOR ENTRY</strong>, contain no live check-in credential, and never enter a guest’s wallet.</p>
<p>Valerie can monitor requests and the workflow as Committee Lead. Confirming a payment and issuing a real paid ticket remain protected Site Owner actions so nobody is accidentally admitted before payment is verified.</p>
<p style="margin:26px 0;text-align:center"><a href="https://mbsh96reunion.com/tickets.html#ticket-order" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:15px 22px;border-radius:8px">OPEN THE LIVE TICKET PAGE</a></p>
<p>Everything is ready for the first completed purchase. We’re officially open for business. 🎟️</p>
<p>With love and a little digital magic,</p><p><strong>Shay</strong><br><span style="color:#e8bc54">Digital Project Concierge · FAMtastic Designs</span></p>
HTML;

$html = fam_reunion_email_layout([
    'title'=>'The Reunion Box Office Is Ready',
    'preheader'=>'The Stripe checkout, payment tracking, branded emails, and digital ticket process are now set up.',
    'eyebrow'=>'ACT I · OFFICIAL REUNION BOX OFFICE',
    'body'=>$body,
    'harry'=>'act-1/ticket-premiere.png',
]);

$results=[];
foreach ($recipients as $name=>$email) {
    if (isset($sentKeys[strtolower($email)])) { $results[]=['recipient'=>$email,'status'=>'skipped_duplicate']; continue; }
    $sent=fam_send_email($config,$email,$subject,$html,'committee');
    $row=['sent_at'=>(new DateTimeImmutable())->format(DateTimeInterface::ATOM),'campaign'=>$campaign,'recipient'=>$email,'recipient_name'=>$name,'subject'=>$subject,'provider_message_id'=>$sent['id']??null];
    file_put_contents($logFile,json_encode($row,JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX);
    $results[]=$row+['status'=>'accepted'];
}
echo json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
