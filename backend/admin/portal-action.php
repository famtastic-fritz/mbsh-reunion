<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/lib/config.php'; require_once dirname(__DIR__).'/lib/db.php'; require_once dirname(__DIR__).'/lib/admin-auth.php'; require_once dirname(__DIR__).'/lib/csrf.php'; require_once dirname(__DIR__).'/lib/validate.php'; require_once dirname(__DIR__).'/lib/portal-auth.php';
$config=fam_load_config(); $pdo=fam_db($config); fam_require_admin_auth();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST') fam_json_response(405,['error'=>'method_not_allowed']);
if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')===false) fam_json_response(415,['error'=>'expected_application_json']);
fam_require_csrf((string)$config['admin_csrf_secret']);
$data=fam_read_json_body(); $action=fam_required($data,'action',50);
try {
  if($action==='set_account_status'){
    $public=fam_required($data,'account_id',36); $status=fam_enum($data,'status',['active','suspended'],true);
    $q=$pdo->prepare('UPDATE attendee_accounts SET status=? WHERE public_id=?'); $q->execute([$status,$public]);
    fam_admin_audit($pdo,'portal_account_'.$status,'attendee_accounts',null,$public); fam_json_response(200,['ok'=>true]);
  }
  if($action==='review_media'){
    $public=fam_required($data,'submission_id',36); $status=fam_enum($data,'status',['approved','rejected'],true); $note=fam_optional($data,'note',1000);
    $q=$pdo->prepare('UPDATE attendee_media_submissions SET status=?,moderation_note=?,reviewed_at=NOW() WHERE public_id=? AND status="pending"'); $q->execute([$status,$note,$public]);
    fam_admin_audit($pdo,'portal_media_'.$status,'attendee_media_submissions',null,$public); fam_json_response(200,['ok'=>true]);
  }
  if($action==='set_suggestion_status'){
    $public=fam_required($data,'suggestion_id',36); $status=fam_enum($data,'status',['reviewing','accepted','declined','closed'],true); $note=fam_optional($data,'note',1000);
    $pdo->prepare('UPDATE attendee_suggestions SET status=?,admin_note=? WHERE public_id=?')->execute([$status,$note,$public]);
    fam_admin_audit($pdo,'portal_suggestion_'.$status,'attendee_suggestions',null,$public); fam_json_response(200,['ok'=>true]);
  }
  if($action==='issue_ticket'){
    $accountPublic=fam_required($data,'account_id',36); $type=fam_required($data,'ticket_type',100); $holder=fam_required($data,'holder_name',200); $orderId=fam_int($data,'ticket_order_id',1,PHP_INT_MAX,null);
    $q=$pdo->prepare('SELECT id FROM attendee_accounts WHERE public_id=? AND status="active"'); $q->execute([$accountPublic]); $attendeeId=(int)$q->fetchColumn(); if(!$attendeeId) fam_json_response(404,['error'=>'account_not_found']);
    $public=fam_uuid_v4(); $secret=(string)($config['portal_token_secret']??$config['admin_csrf_secret']); $credential=fam_ticket_credential($public,$secret);
    $pdo->prepare('INSERT INTO ticket_wallet_items (public_id,attendee_id,ticket_order_id,ticket_type,holder_name,credential_fingerprint,status,issued_at) VALUES (?,?,?,?,?,?,"active",NOW())')->execute([$public,$attendeeId,$orderId,$type,$holder,fam_token_hash('ticket|'.$public)]);
    $pdo->prepare('INSERT INTO attendee_notifications (public_id,attendee_id,notification_type,title,message,action_url) VALUES (?,?,"ticket","Your reunion ticket is ready","Open your wallet to view your digital ticket.","/portal/wallet")')->execute([fam_uuid_v4(),$attendeeId]);
    fam_admin_audit($pdo,'portal_ticket_issue','ticket_wallet_items',null,$public); fam_json_response(201,['ok'=>true,'ticket_id'=>$public]);
  }
  if($action==='ticket_check_in'){
    $public=fam_required($data,'ticket_id',36); $credential=fam_required($data,'credential',200); $secret=(string)($config['portal_token_secret']??$config['admin_csrf_secret']);
    if(!fam_ticket_credential_valid($credential,$public,$secret)) fam_json_response(400,['error'=>'invalid_ticket']);
    $q=$pdo->prepare('UPDATE ticket_wallet_items SET status="checked_in",checked_in_at=NOW() WHERE public_id=? AND status="active"'); $q->execute([$public]);
    if($q->rowCount()!==1) fam_json_response(409,['error'=>'ticket_not_active']);
    fam_admin_audit($pdo,'portal_ticket_check_in','ticket_wallet_items',null,$public); fam_json_response(200,['ok'=>true]);
  }
  fam_json_response(400,['error'=>'unknown_action']);
} catch(ValidationError $e){ fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]); }
