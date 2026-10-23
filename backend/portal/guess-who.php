<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
require_once dirname(__DIR__).'/lib/reunion-experience.php';
fam_experience_errors();$method=fam_portal_json_method(['GET','POST']);
$id=fam_require_active_attendee($pdo);
function fam_guess_state(PDO $pdo,int $id,bool $includeRound=true): array {
    $q=$pdo->prepare('SELECT public_id,clues_json,status,moderation_note,consent_to_publish FROM reunion_guess_submissions WHERE attendee_id=?');$q->execute([$id]);$own=$q->fetch()?:null;
    if($own){$own['editable']=$own['status']!=='published';$own['clues']=json_decode($own['clues_json'],true);unset($own['clues_json']);}
    $round=$includeRound?($pdo->query("SELECT id,public_id,title,status FROM reunion_guess_rounds WHERE status IN ('open','closed','published') ORDER BY (status='open') DESC,id DESC LIMIT 1")->fetch()?:null):null;
    if($round){
        $q=$pdo->prepare("SELECT c.public_id,c.clues_json,c.answer_name,s.attendee_id,EXISTS(SELECT 1 FROM reunion_guess_answers a WHERE a.card_id=c.id AND a.attendee_id=?) answered FROM reunion_guess_cards c JOIN reunion_guess_submissions s ON s.id=c.submission_id WHERE c.round_id=? ORDER BY c.id");$q->execute([$id,$round['id']]);$cards=$q->fetchAll();
        foreach($cards as &$c){$c['clues']=json_decode($c['clues_json'],true);$c['answered']=(bool)$c['answered'];$c['is_own']=(int)$c['attendee_id']===$id;if($round['status']==='published')$c['reveal_name']=$c['answer_name'];unset($c['answer_name'],$c['attendee_id'],$c['clues_json']);}
        $round['cards']=$cards;$round['score']=null;
        if($round['status']==='published'){
            $round['leaderboard']=fam_guess_leaderboard($pdo,(int)$round['id']);$round['winners']=array_values(array_filter($round['leaderboard'],fn($r)=>$r['rank']===1));
            $s=$pdo->prepare('SELECT COALESCE(SUM(a.points_awarded),0) FROM reunion_guess_answers a JOIN reunion_guess_cards c ON c.id=a.card_id WHERE c.round_id=? AND a.attendee_id=?');$s->execute([$round['id'],$id]);$round['score']=(int)$s->fetchColumn();
            if(count(array_filter($cards,fn($c)=>$c['answered']))>0)$round['score']+=100*count(array_filter($cards,fn($c)=>$c['is_own']));
        }
        unset($round['id']);
    }
    return ['submission'=>$own,'round'=>$round];
}
if($method==='GET'){fam_json_response(200,fam_guess_state($pdo,$id,fam_experience_entitlement($pdo,$config,$id)));}
fam_require_attendee_csrf();$d=fam_read_json_body();fam_experience_text_fields($d,['action','round_id','card_id','answer']);$action=fam_enum($d,'action',['submit','withdraw','guess'],true);fam_experience_rate($pdo,$id,'guess',100);
if($action!=='withdraw')fam_require_experience_ticket($pdo,$config,$id);
if($action==='submit'||$action==='withdraw'){
    $lock=$pdo->query("SELECT GET_LOCK('mbsh_guess_round_lifecycle',5)")->fetchColumn();if((int)$lock!==1)throw new RuntimeException('round_busy');
    register_shutdown_function(function()use($pdo){try{$pdo->query("SELECT RELEASE_LOCK('mbsh_guess_round_lifecycle')");}catch(Throwable $e){}});
    $pdo->beginTransaction();
    // Account lock serializes first submission and retries without an existing submission row.
    $lock=$pdo->prepare('SELECT id FROM attendee_accounts WHERE id=? FOR UPDATE');$lock->execute([$id]);
    $q=$pdo->prepare('SELECT id,public_id,status FROM reunion_guess_submissions WHERE attendee_id=? FOR UPDATE');$q->execute([$id]);$own=$q->fetch();
    if($action==='submit'&&$own&&$own['status']==='published')fam_json_response(409,['error'=>'submission_published','message'=>'This clue set is in a round. Withdraw it first to remove the affected round, then submit corrected clues for review.']);
    if($action==='withdraw'){
        if(!$own)fam_json_response(404,['error'=>'not_found']);
        $affected=$pdo->prepare("SELECT DISTINCT r.id,r.public_id FROM reunion_guess_rounds r JOIN reunion_guess_cards c ON c.round_id=r.id WHERE c.submission_id=? AND r.status<>'archived' FOR UPDATE");$affected->execute([$own['id']]);foreach($affected->fetchAll() as $r){fam_guess_archive_round($pdo,(int)$r['id']);fam_experience_audit($pdo,$id,'guess_round_withdrawn','guess_round',$r['public_id']);}
        $pdo->prepare("UPDATE reunion_guess_submissions SET status='withdrawn',consent_to_publish=0 WHERE id=?")->execute([$own['id']]);
        fam_experience_audit($pdo,$id,'guess_withdrawn','guess_submission',$own['public_id']);$pdo->commit();fam_json_response(200,['ok'=>true]+fam_guess_state($pdo,$id,false));
    }
    $clues=fam_guess_clues($d);if(!fam_bool($d,'consent_to_publish'))throw new ValidationError('Consent is required to show your clues and reveal your name during the game.');
    $a=fam_portal_account($pdo,$id);$name=trim($a['first_name'].' '.$a['last_name']);$aliases=[$name];
    if(!empty($a['maiden_name'])){$aliases[]=trim($a['first_name'].' '.$a['maiden_name']);$aliases[]=trim($a['first_name'].' '.$a['maiden_name'].' '.$a['last_name']);}
    $public=$own?$own['public_id']:fam_uuid_v4();
    if($own){
        $pdo->prepare("DELETE c FROM reunion_guess_cards c JOIN reunion_guess_rounds r ON r.id=c.round_id WHERE c.submission_id=? AND r.status='draft'")->execute([$own['id']]);
        $pdo->prepare("UPDATE reunion_guess_submissions SET clues_json=?,answer_name=?,answer_aliases_json=?,consent_to_publish=1,status='pending',moderation_note=NULL,reviewed_at=NULL WHERE id=?")->execute([json_encode($clues),$name,json_encode($aliases),$own['id']]);
    }else{$pdo->prepare('INSERT INTO reunion_guess_submissions(public_id,attendee_id,clues_json,answer_name,answer_aliases_json,consent_to_publish) VALUES(?,?,?,?,?,1)')->execute([$public,$id,json_encode($clues),$name,json_encode($aliases)]);}
    fam_experience_audit($pdo,$id,'guess_submitted','guess_submission',$public);$pdo->commit();fam_json_response(200,['ok'=>true,'id'=>$public]+fam_guess_state($pdo,$id));
}
$roundPublic=fam_required($d,'round_id',36);$cardPublic=fam_required($d,'card_id',36);$answer=fam_required($d,'answer',200);
$pdo->beginTransaction();
$q=$pdo->prepare('SELECT id,status FROM reunion_guess_rounds WHERE public_id=? FOR UPDATE');$q->execute([$roundPublic]);$round=$q->fetch();
if(!$round||$round['status']!=='open')fam_json_response(409,['error'=>'round_not_open']);
$q=$pdo->prepare('SELECT c.id,c.answer_aliases_json,s.attendee_id FROM reunion_guess_cards c JOIN reunion_guess_submissions s ON s.id=c.submission_id WHERE c.public_id=? AND c.round_id=?');$q->execute([$cardPublic,$round['id']]);$card=$q->fetch();
if(!$card)fam_json_response(404,['error'=>'not_found']);if((int)$card['attendee_id']===$id)fam_json_response(409,['error'=>'own_card']);
$q=$pdo->prepare('SELECT id FROM reunion_guess_answers WHERE card_id=? AND attendee_id=?');$q->execute([$card['id'],$id]);
if($q->fetchColumn()){$pdo->commit();fam_json_response(200,['ok'=>true,'already_recorded'=>true,'message'=>'Your guess is saved. Answers will be revealed by the committee.']);}
$correct=fam_guess_correct($answer,json_decode($card['answer_aliases_json'],true));
$pdo->prepare('INSERT INTO reunion_guess_answers(card_id,attendee_id,answer_text,is_correct,points_awarded) VALUES(?,?,?,?,?)')->execute([$card['id'],$id,$answer,$correct?1:0,$correct?100:0]);
fam_experience_audit($pdo,$id,'guess_recorded','guess_card',$cardPublic);$pdo->commit();
fam_json_response(200,['ok'=>true,'message'=>'Your guess is saved. Answers will be revealed by the committee.']);
