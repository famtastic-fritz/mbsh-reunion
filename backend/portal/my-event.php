<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','PATCH']);
$id=fam_require_active_attendee($pdo);
$account=fam_portal_account($pdo,$id);
if(!$account) fam_json_response(404,['error'=>'account_not_found']);

function fam_event_response(PDO $pdo,int $id): ?array {
  $q=$pdo->prepare('SELECT legacy_rsvp_id,attendance,guest_count,guest_names,phone,meal_choice,dietary_accessibility,status,migration_sync_status,submitted_at,updated_at FROM portal_event_responses WHERE attendee_id=?');
  $q->execute([$id]); $row=$q->fetch(); return $row?:null;
}

function fam_seed_event_response(PDO $pdo,array $config,int $id,array $account): array {
  $existing=fam_event_response($pdo,$id); if($existing) return $existing;
  $legacy=null; $menu=null; $snapshot=fam_production_snapshot($config);
  if($snapshot){
    $q=$snapshot->prepare('SELECT id,attending,guest_count,guest_names,phone,dietary FROM rsvps WHERE LOWER(TRIM(email))=LOWER(TRIM(?)) ORDER BY updated_at DESC,id DESC LIMIT 1');
    $q->execute([$account['email']]); $legacy=$q->fetch()?:null;
    $m=$snapshot->prepare('SELECT selections_json,dietary FROM menu_selections WHERE LOWER(TRIM(email))=LOWER(TRIM(?)) ORDER BY created_at DESC LIMIT 1');
    $m->execute([$account['email']]); $menu=$m->fetch()?:null;
  }
  $attendance='unknown';
  if($legacy){$raw=strtolower(trim((string)$legacy['attending']));$attendance=in_array($raw,['yes','attending','1'],true)?'yes':(in_array($raw,['no','not attending','0'],true)?'no':(str_contains($raw,'maybe')?'maybe':'unknown'));}
  $meal='undecided';
  if($menu){$blob=strtolower((string)($menu['selections_json']??''));foreach(['fish','chicken','vegetarian'] as $choice){if(str_contains($blob,$choice)){$meal=$choice;break;}}}
  $diet=trim(implode("\n",array_filter([(string)($legacy['dietary']??''),(string)($menu['dietary']??'')])));
  $pdo->prepare('INSERT INTO portal_event_responses(attendee_id,legacy_rsvp_id,attendance,guest_count,guest_names,phone,meal_choice,dietary_accessibility,status,migration_sync_status) VALUES(?,?,?,?,?,?,?,?,?,?)')
    ->execute([$id,$legacy['id']??null,$attendance,(int)($legacy['guest_count']??0),$legacy['guest_names']??null,$legacy['phone']??($account['phone']??null),$meal,$diet?:null,$legacy?'submitted':'draft',$legacy?'seeded_from_legacy':'local_only']);
  return fam_event_response($pdo,$id)??[];
}

if($method==='GET'){
  $response=fam_seed_event_response($pdo,$config,$id,$account);
  fam_json_response(200,['event_response'=>$response,'data_context'=>fam_snapshot_context(fam_production_snapshot($config))]);
}

fam_require_attendee_csrf(); $d=fam_read_json_body();
$attendance=strtolower(trim((string)($d['attendance']??'')));
$meal=strtolower(trim((string)($d['meal_choice']??'')));
$guestCount=(int)($d['guest_count']??0);
$guestNames=trim((string)($d['guest_names']??''));
$phone=trim((string)($d['phone']??''));
$note=trim((string)($d['dietary_accessibility']??''));
if(!in_array($attendance,['yes','maybe','no'],true)||!in_array($meal,['fish','chicken','vegetarian','undecided'],true)||$guestCount<0||$guestCount>5||mb_strlen($guestNames)>500||mb_strlen($phone)>50||mb_strlen($note)>2000) fam_json_response(422,['error'=>'validation_error']);
fam_seed_event_response($pdo,$config,$id,$account);
$q=$pdo->prepare("UPDATE portal_event_responses SET attendance=?,guest_count=?,guest_names=?,phone=?,meal_choice=?,dietary_accessibility=?,status='submitted',migration_sync_status='local_only',submitted_at=NOW() WHERE attendee_id=?");
$q->execute([$attendance,$guestCount,$guestNames?:null,$phone?:null,$meal,$note?:null,$id]);
if($phone!=='')$pdo->prepare('UPDATE attendee_profiles SET phone=? WHERE attendee_id=?')->execute([$phone,$id]);
fam_json_response(200,['ok'=>true,'event_response'=>fam_event_response($pdo,$id),'message'=>'Your reunion details are saved to your attendee account.']);
