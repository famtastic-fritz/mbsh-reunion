<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';require_once dirname(__DIR__,2).'/lib/reunion-media.php';
fam_experience_errors();$method=fam_portal_json_method(['GET','POST']);$staff=fam_require_portal_staff($pdo);
$can=fn(string $cap)=>in_array($cap,$staff['capabilities'],true);
if($method==='GET'){
    $out=['guess_submissions'=>[],'guess_rounds'=>[],'media'=>[],'suggestions'=>[]];
    if($can('manage_event_content')){
        $out['guess_submissions']=$pdo->query("SELECT public_id,answer_name display_name,clues_json,status,consent_to_publish,moderation_note,created_at FROM reunion_guess_submissions WHERE status<>'withdrawn' ORDER BY created_at DESC LIMIT 200")->fetchAll();
        foreach($out['guess_submissions'] as &$s){$s['clues']=json_decode($s['clues_json'],true);unset($s['clues_json']);}unset($s);
        $out['guess_rounds']=$pdo->query('SELECT id,public_id,title,status FROM reunion_guess_rounds ORDER BY id DESC LIMIT 30')->fetchAll();
        foreach($out['guess_rounds'] as &$r){$q=$pdo->prepare('SELECT s.public_id FROM reunion_guess_cards c JOIN reunion_guess_submissions s ON s.id=c.submission_id WHERE c.round_id=? ORDER BY c.id');$q->execute([$r['id']]);$r['card_ids']=$q->fetchAll(PDO::FETCH_COLUMN);$r['leaderboard']=fam_guess_leaderboard($pdo,(int)$r['id']);unset($r['id']);}unset($r);
    }
    if($can('moderate_media')){$out['media']=$pdo->query("SELECT m.public_id,m.media_type,m.title,m.caption,m.status,m.consent_to_publish,m.moderation_note,CONCAT(p.first_name,' ',p.last_name) display_name,m.created_at FROM attendee_media_submissions m JOIN attendee_profiles p ON p.attendee_id=m.attendee_id WHERE m.status<>'withdrawn' ORDER BY m.created_at DESC LIMIT 200")->fetchAll();foreach($out['media'] as &$m)$m['preview_url']=$m['media_type']==='photo'?fam_media_preview_url($m['public_id'],true):null;unset($m);}
    if($can('manage_suggestions'))$out['suggestions']=$pdo->query("SELECT s.public_id,s.category,s.subject,s.message,s.status,s.admin_note,s.created_at,CONCAT(p.first_name,' ',p.last_name) display_name FROM attendee_suggestions s JOIN attendee_profiles p ON p.attendee_id=s.attendee_id ORDER BY s.created_at DESC LIMIT 200")->fetchAll();
    fam_json_response(200,$out);
}
fam_require_attendee_csrf();$d=fam_read_json_body();fam_experience_text_fields($d,['action','id','title','status','note']);$action=fam_enum($d,'action',['review_guess','create_guess_round','set_guess_round','review_media','set_suggestion_status'],true);
if($action==='review_media'){
    $staff=fam_require_portal_staff($pdo,'moderate_media');$public=fam_required($d,'id',36);$status=fam_enum($d,'status',['approved','rejected'],true);
    fam_review_experience_media($pdo,$config,$staff,$public,$status,fam_optional($d,'note',1000));fam_json_response(200,['ok'=>true]);
}
if($action==='set_suggestion_status'){
    $staff=fam_require_portal_staff($pdo,'manage_suggestions');$public=fam_required($d,'id',36);$status=fam_enum($d,'status',['reviewing','accepted','declined','closed'],true);$note=fam_optional($d,'note',1000);
    $q=$pdo->prepare('SELECT id FROM attendee_suggestions WHERE public_id=?');$q->execute([$public]);if(!$q->fetchColumn())fam_json_response(404,['error'=>'not_found']);
    $pdo->prepare('UPDATE attendee_suggestions SET status=?,admin_note=? WHERE public_id=?')->execute([$status,$note,$public]);fam_portal_staff_audit($pdo,$staff,'suggestion_'.$status,'attendee_suggestions',$public,['note'=>$note]);fam_json_response(200,['ok'=>true]);
}
$staff=fam_require_portal_staff($pdo,'manage_event_content');$pdo->beginTransaction();
// Serialize round selection and opening. This also prevents two concurrent open rounds.
$lock=$pdo->query("SELECT GET_LOCK('mbsh_guess_round_lifecycle',5)")->fetchColumn();if((int)$lock!==1)throw new RuntimeException('round_busy');
register_shutdown_function(function()use($pdo){try{$pdo->query("SELECT RELEASE_LOCK('mbsh_guess_round_lifecycle')");}catch(Throwable $e){}});
if($action==='review_guess'){
    $public=fam_required($d,'id',36);$status=fam_enum($d,'status',['approved','rejected'],true);$note=fam_optional($d,'note',1000);
    $q=$pdo->prepare('SELECT id,status,consent_to_publish FROM reunion_guess_submissions WHERE public_id=? FOR UPDATE');$q->execute([$public]);$row=$q->fetch();
    if(!$row||!in_array($row['status'],['pending','approved','rejected'],true))fam_json_response(409,['error'=>'submission_not_reviewable']);
    if($status==='approved'&&!(bool)$row['consent_to_publish'])throw new ValidationError('Permission to publish is required.');
    if($status==='rejected')$pdo->prepare("DELETE c FROM reunion_guess_cards c JOIN reunion_guess_rounds r ON r.id=c.round_id WHERE c.submission_id=? AND r.status='draft'")->execute([$row['id']]);
    $pdo->prepare('UPDATE reunion_guess_submissions SET status=?,moderation_note=?,reviewed_at=NOW() WHERE id=?')->execute([$status,$note,$row['id']]);
    fam_portal_staff_audit($pdo,$staff,'guess_'.$status,'guess_submission',$public,['note'=>$note]);$pdo->commit();fam_json_response(200,['ok'=>true]);
}
if($action==='create_guess_round'){
    $title=fam_required($d,'title',200);$ids=$d['submission_ids']??[];
    if(!is_array($ids)||!array_is_list($ids)||count($ids)<2||count($ids)>30||count(array_unique($ids))!==count($ids))throw new ValidationError('Choose 2–30 different approved clue sets.');
    $public=fam_uuid_v4();$pdo->prepare('INSERT INTO reunion_guess_rounds(public_id,title) VALUES(?,?)')->execute([$public,$title]);$round=(int)$pdo->lastInsertId();
    foreach($ids as $sid){if(!is_string($sid)||strlen($sid)!==36)throw new ValidationError('Invalid clue set.');$q=$pdo->prepare("SELECT id,clues_json,answer_name,answer_aliases_json FROM reunion_guess_submissions WHERE public_id=? AND status='approved' AND consent_to_publish=1 FOR UPDATE");$q->execute([$sid]);$row=$q->fetch();if(!$row)throw new ValidationError('Each selected clue set must still be approved and have permission.');
        $pdo->prepare('INSERT INTO reunion_guess_cards(public_id,round_id,submission_id,clues_json,answer_name,answer_aliases_json) VALUES(?,?,?,?,?,?)')->execute([fam_uuid_v4(),$round,$row['id'],$row['clues_json'],$row['answer_name'],$row['answer_aliases_json']]);
    }
    fam_portal_staff_audit($pdo,$staff,'guess_round_created','guess_round',$public);$pdo->commit();fam_json_response(201,['ok'=>true,'id'=>$public]);
}
$public=fam_required($d,'id',36);$status=fam_enum($d,'status',['open','closed','published','archived'],true);
$q=$pdo->prepare('SELECT id,status FROM reunion_guess_rounds WHERE public_id=? FOR UPDATE');$q->execute([$public]);$round=$q->fetch();if(!$round)fam_json_response(404,['error'=>'not_found']);
if($round['status']===$status){$pdo->commit();fam_json_response(200,['ok'=>true,'already_recorded'=>true]);}
$allowed=['draft'=>'open','open'=>'closed','closed'=>'published'];if($status!=='archived'&&($allowed[$round['status']]??null)!==$status)fam_json_response(409,['error'=>'invalid_round_transition']);
if($status==='open'){
    $q=$pdo->prepare("SELECT COUNT(*) FROM reunion_guess_cards c JOIN reunion_guess_submissions s ON s.id=c.submission_id WHERE c.round_id=? AND s.status='approved' AND s.consent_to_publish=1");$q->execute([$round['id']]);$count=(int)$q->fetchColumn();
    $all=$pdo->prepare('SELECT COUNT(*) FROM reunion_guess_cards WHERE round_id=?');$all->execute([$round['id']]);if($count<2||$count!==(int)$all->fetchColumn())fam_json_response(409,['error'=>'need_two_approved_clue_sets']);
    if((int)$pdo->query("SELECT COUNT(*) FROM reunion_guess_rounds WHERE status='open'")->fetchColumn()>0)fam_json_response(409,['error'=>'another_round_open']);
    $pdo->prepare("UPDATE reunion_guess_submissions s JOIN reunion_guess_cards c ON c.submission_id=s.id SET s.status='published' WHERE c.round_id=?")->execute([$round['id']]);
}
if($status==='archived')fam_guess_archive_round($pdo,(int)$round['id']);else $pdo->prepare('UPDATE reunion_guess_rounds SET status=? WHERE id=?')->execute([$status,$round['id']]);
fam_portal_staff_audit($pdo,$staff,'guess_round_'.$status,'guess_round',$public);$pdo->commit();fam_json_response(200,['ok'=>true]);
