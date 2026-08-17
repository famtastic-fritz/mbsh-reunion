<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','PATCH']); $id=fam_require_active_attendee($pdo);
if($method==='GET'){ $q=$pdo->prepare('SELECT public_id,notification_type,title,message,action_url,read_at,created_at FROM attendee_notifications WHERE attendee_id=? ORDER BY created_at DESC LIMIT 100'); $q->execute([$id]); fam_json_response(200,['notifications'=>$q->fetchAll()]); }
fam_require_attendee_csrf(); $d=fam_read_json_body(); $public=fam_required($d,'id',36);
$q=$pdo->prepare('UPDATE attendee_notifications SET read_at=COALESCE(read_at,NOW()) WHERE public_id=? AND attendee_id=?'); $q->execute([$public,$id]);
if($q->rowCount()===0) fam_json_response(404,['error'=>'not_found']); fam_json_response(200,['ok'=>true]);
