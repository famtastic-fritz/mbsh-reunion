<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','PATCH']); $id=fam_require_active_attendee($pdo);
if($method==='GET'){ $q=$pdo->prepare('SELECT event_updates,memory_updates,promotional_email,sms_notifications,updated_at FROM attendee_preferences WHERE attendee_id=?'); $q->execute([$id]); fam_json_response(200,['preferences'=>$q->fetch()]); }
fam_require_attendee_csrf(); $d=fam_read_json_body();
$event=fam_bool($d,'event_updates',true); $memory=fam_bool($d,'memory_updates',true); $promo=fam_bool($d,'promotional_email',false); $sms=fam_bool($d,'sms_notifications',false);
$pdo->prepare('UPDATE attendee_preferences SET event_updates=?,memory_updates=?,promotional_email=?,sms_notifications=? WHERE attendee_id=?')->execute([$event?1:0,$memory?1:0,$promo?1:0,$sms?1:0,$id]);
fam_json_response(200,['ok'=>true]);
