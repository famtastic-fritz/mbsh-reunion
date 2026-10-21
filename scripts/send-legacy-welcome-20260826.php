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
require_once $lib.'/db.php';
require_once $lib.'/resend.php';
require_once $lib.'/portal-email-templates.php';
$config = fam_load_config();
$pdo = fam_db($config);
$campaign = 'legacy-welcome-20260826-v1';
$logDir = '/home/nineoo/.local/share/mbsh';
if (!is_dir($logDir)) mkdir($logDir, 0700, true);
$logFile = $logDir.'/email-campaign-receipts.jsonl';

function sent_emails(string $file): array {
    $sent=[];
    if (!is_readable($file)) return $sent;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $row=json_decode($line,true);
        if (!empty($row['provider_message_id']) && str_starts_with((string)($row['key']??''),'welcome-')) $sent[strtolower((string)($row['recipient']??''))]=true;
    }
    return $sent;
}
function log_receipt(string $file,array $row): void { file_put_contents($file,json_encode($row,JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX); }

$previous=sent_emails($logFile);
$rows=$pdo->query("SELECT first_name,last_name,LOWER(TRIM(email)) AS email,id FROM surveys WHERE TRIM(email)<>'' ORDER BY id")->fetchAll();
$audience=[];
foreach($rows as $row){
    $email=(string)$row['email'];
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)) continue;
    $audience[$email]=['first_name'=>trim((string)$row['first_name']),'last_name'=>trim((string)$row['last_name']),'email'=>$email];
}

$results=[];
foreach($audience as $email=>$person){
    $key='welcome-legacy-'.substr(hash('sha256',$email),0,16);
    if(isset($previous[$email])){$results[]=['key'=>$key,'recipient'=>$email,'status'=>'skipped_previously_welcomed'];continue;}
    $duplicate=false;
    if(is_readable($logFile))foreach(file($logFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line){$old=json_decode($line,true);if(($old['campaign']??'')===$campaign&&($old['key']??'')===$key&&!empty($old['provider_message_id'])){$duplicate=true;break;}}
    if($duplicate){$results[]=['key'=>$key,'recipient'=>$email,'status'=>'skipped_duplicate'];continue;}
    $first=$person['first_name']!==''?$person['first_name']:'Hi-Tide';
    $message=fam_attendee_welcome_email($first,false);
    try{
        $sent=fam_send_email($config,$email,$message['subject'],$message['html'],'committee');
        $row=['sent_at'=>(new DateTimeImmutable())->format(DateTimeInterface::ATOM),'campaign'=>$campaign,'key'=>$key,'recipient'=>$email,'subject'=>$message['subject'],'provider_message_id'=>$sent['id']??null];
        log_receipt($logFile,$row);$results[]=$row+['status'=>'accepted'];
    }catch(Throwable $error){
        $row=['sent_at'=>(new DateTimeImmutable())->format(DateTimeInterface::ATOM),'campaign'=>$campaign,'key'=>$key,'recipient'=>$email,'subject'=>$message['subject'],'error'=>$error->getMessage()];
        log_receipt($logFile,$row);$results[]=$row+['status'=>'failed'];
    }
    usleep(600000);
}
$counts=[];foreach($results as $row)$counts[$row['status']]=($counts[$row['status']]??0)+1;
echo json_encode(['campaign'=>$campaign,'source_rows'=>count($rows),'unique_audience'=>count($audience),'counts'=>$counts,'results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
