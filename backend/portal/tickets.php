<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
fam_portal_json_method(['GET']); header('Cache-Control: private, no-store'); $id=fam_require_active_attendee($pdo);
$q=$pdo->prepare('SELECT public_id,ticket_type,holder_name,status,issued_at,checked_in_at,created_at FROM ticket_wallet_items WHERE attendee_id=? ORDER BY created_at DESC'); $q->execute([$id]);
require_once dirname(__DIR__).'/lib/commerce-wallet.php';
try { $rows=array_merge($q->fetchAll(),fam_commerce_wallet_for_attendee($pdo,$config,$id)); }
catch(Throwable $error){ error_log('[portal-wallet] commerce unavailable'); fam_json_response(503,['error'=>'commerce_wallet_unavailable']); }
$tickets=[]; foreach($rows as $row){ $row['credential']=$row['status']==='active'?fam_ticket_credential($row['public_id'],(string)($config['portal_token_secret']??'')):null; $tickets[]=$row; }
fam_json_response(200,['tickets'=>$tickets]);
