<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['POST']);
fam_require_attendee_csrf();
$data=fam_read_json_body();
$action=fam_required($data,'action',50);

try {
  if($action==='review_media'){
    $staff=fam_require_portal_staff($pdo,'moderate_media');
    $public=fam_required($data,'submission_id',36);
    $status=fam_enum($data,'status',['approved','rejected'],true);
    $note=fam_optional($data,'note',1000);
    $q=$pdo->prepare("UPDATE attendee_media_submissions SET status=?,moderation_note=?,reviewed_at=NOW() WHERE public_id=? AND status='pending'");
    $q->execute([$status,$note,$public]);
    if($q->rowCount()!==1) fam_json_response(409,['error'=>'submission_not_pending']);
    fam_portal_staff_audit($pdo,$staff,'media_'.$status,'attendee_media_submissions',$public,['note'=>$note]);
    fam_json_response(200,['ok'=>true]);
  }
  if($action==='set_suggestion_status'){
    $staff=fam_require_portal_staff($pdo,'manage_suggestions');
    $public=fam_required($data,'suggestion_id',36);
    $status=fam_enum($data,'status',['reviewing','accepted','declined','closed'],true);
    $note=fam_optional($data,'note',1000);
    $q=$pdo->prepare('UPDATE attendee_suggestions SET status=?,admin_note=? WHERE public_id=?');
    $q->execute([$status,$note,$public]);
    if($q->rowCount()!==1) fam_json_response(404,['error'=>'suggestion_not_found']);
    fam_portal_staff_audit($pdo,$staff,'suggestion_'.$status,'attendee_suggestions',$public,['note'=>$note]);
    fam_json_response(200,['ok'=>true]);
  }
  if($action==='set_account_status'){
    $staff=fam_require_portal_staff($pdo,'manage_committee');
    $public=fam_required($data,'account_id',36);
    $status=fam_enum($data,'status',['active','suspended'],true);
    $target=$pdo->prepare('SELECT id FROM attendee_accounts WHERE public_id=?');$target->execute([$public]);$targetId=(int)$target->fetchColumn();
    if(!$targetId)fam_json_response(404,['error'=>'account_not_found']);
    if($status==='suspended'&&$targetId===(int)$staff['attendee_id'])fam_json_response(409,['error'=>'cannot_suspend_current_owner']);
    $q=$pdo->prepare('UPDATE attendee_accounts SET status=? WHERE public_id=?');$q->execute([$status,$public]);
    if($q->rowCount()!==1) fam_json_response(404,['error'=>'account_not_found']);
    fam_portal_staff_audit($pdo,$staff,'account_'.$status,'attendee_accounts',$public);
    fam_json_response(200,['ok'=>true]);
  }
  if($action==='issue_manual_ticket'){
    $staff=fam_require_portal_staff($pdo,'issue_manual_tickets');
    $accountPublic=fam_required($data,'account_id',36);$type=fam_required($data,'ticket_type',100);$holder=fam_required($data,'holder_name',200);
    $q=$pdo->prepare("SELECT id FROM attendee_accounts WHERE public_id=? AND status='active'");$q->execute([$accountPublic]);$attendeeId=(int)$q->fetchColumn();
    if(!$attendeeId) fam_json_response(404,['error'=>'account_not_found']);
    $public=fam_uuid_v4();$fingerprint=fam_token_hash('ticket|'.$public);
    $pdo->prepare("INSERT INTO ticket_wallet_items (public_id,attendee_id,ticket_type,holder_name,credential_fingerprint,status,issued_at) VALUES (?,?,?,?,?,'active',NOW())")->execute([$public,$attendeeId,$type,$holder,$fingerprint]);
    $pdo->prepare("INSERT INTO attendee_notifications (public_id,attendee_id,notification_type,title,message,action_url) VALUES (?,?,'ticket','Your reunion ticket is ready','Open your wallet to view your digital ticket.','/portal/#wallet')")->execute([fam_uuid_v4(),$attendeeId]);
    fam_portal_staff_audit($pdo,$staff,'manual_ticket_issued','ticket_wallet_items',$public,['reason'=>'owner_manual_issue']);
    fam_json_response(201,['ok'=>true,'ticket_id'=>$public]);
  }
  if($action==='ticket_check_in'){
    $staff=fam_require_portal_staff($pdo,'check_in_tickets');
    $public=fam_required($data,'ticket_id',36);$credential=fam_required($data,'credential',200);
    $secret=(string)($config['portal_token_secret']??'');
    if(!fam_ticket_credential_valid($credential,$public,$secret)) fam_json_response(400,['error'=>'invalid_ticket']);
    $q=$pdo->prepare("UPDATE ticket_wallet_items SET status='checked_in',checked_in_at=NOW() WHERE public_id=? AND status='active'");$q->execute([$public]);
    if($q->rowCount()!==1) fam_json_response(409,['error'=>'ticket_not_active']);
    fam_portal_staff_audit($pdo,$staff,'ticket_checked_in','ticket_wallet_items',$public);
    fam_json_response(200,['ok'=>true]);
  }
  if($action==='ticket_void'){
    $staff=fam_require_portal_staff($pdo,'issue_manual_tickets');
    $public=fam_required($data,'ticket_id',36);$note=fam_required($data,'note',1000);
    $q=$pdo->prepare("UPDATE ticket_wallet_items SET status='void' WHERE public_id=? AND status IN ('pending','active')");$q->execute([$public]);
    if($q->rowCount()!==1) fam_json_response(409,['error'=>'ticket_not_voidable']);
    fam_portal_staff_audit($pdo,$staff,'ticket_voided','ticket_wallet_items',$public,['note'=>$note]);
    fam_json_response(200,['ok'=>true]);
  }
  fam_json_response(400,['error'=>'unknown_action']);
} catch(ValidationError $e){ fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]); }
