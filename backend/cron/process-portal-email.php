<?php
// Run every minute. Idempotent outbox with bounded retry and dead-letter state.
declare(strict_types=1);
if(PHP_SAPI!=='cli'){ http_response_code(403); exit; }
require_once dirname(__DIR__).'/lib/config.php'; require_once dirname(__DIR__).'/lib/db.php'; require_once dirname(__DIR__).'/lib/resend.php';
$config=fam_load_config(); $pdo=fam_db($config);
$pdo->exec("UPDATE portal_email_jobs SET status='pending',last_error='Recovered stale worker claim' WHERE status='processing' AND next_attempt_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)");
$jobs=$pdo->query("SELECT * FROM portal_email_jobs WHERE status='pending' AND next_attempt_at<=NOW() ORDER BY id LIMIT 25")->fetchAll();
foreach($jobs as $job){
  $claim=$pdo->prepare("UPDATE portal_email_jobs SET status='processing',attempts=attempts+1,next_attempt_at=DATE_ADD(NOW(),INTERVAL 10 MINUTE) WHERE id=? AND status='pending'"); $claim->execute([(int)$job['id']]); if($claim->rowCount()!==1)continue;
  try{
    $result=fam_send_email($config,$job['recipient'],$job['subject'],$job['html_body'],$job['from_role']);
    $pdo->prepare("UPDATE portal_email_jobs SET status='sent',provider_message_id=?,sent_at=NOW(),last_error=NULL WHERE id=?")->execute([$result['id']??null,(int)$job['id']]);
  }catch(Throwable $e){
    $attempt=(int)$job['attempts']+1; $dead=$attempt>=5; $delay=min(3600,60*(2**max(0,$attempt-1)));
    $pdo->prepare('UPDATE portal_email_jobs SET status=?,next_attempt_at=DATE_ADD(NOW(),INTERVAL ? SECOND),last_error=? WHERE id=?')->execute([$dead?'dead':'pending',$delay,substr($e->getMessage(),0,1000),(int)$job['id']]);
  }
}
echo json_encode(['processed'=>count($jobs)]).PHP_EOL;
