<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
fam_portal_json_method(['GET']); $id=fam_require_active_attendee($pdo);
$q=$pdo->prepare('SELECT public_id,ticket_type,holder_name,status,issued_at,checked_in_at,created_at FROM ticket_wallet_items WHERE attendee_id=? ORDER BY created_at DESC'); $q->execute([$id]);
$tickets=[]; foreach($q->fetchAll() as $row){ $row['credential']=$row['status']==='active'?fam_ticket_credential($row['public_id'],(string)($config['portal_token_secret']??'')):null; $tickets[]=$row; }
fam_json_response(200,['tickets'=>$tickets]);
