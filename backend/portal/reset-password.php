<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
fam_portal_json_method(['POST']);
fam_rate_limit($pdo,'portal_reset_password',3600,10);
$data=fam_read_json_body(); $token=fam_required($data,'token',200); $password=(string)($data['password']??'');
if (!fam_password_valid($password)) fam_json_response(400,['error'=>'weak_password']);
$id=fam_consume_auth_token($pdo,$token,'reset_password');
if (!$id) fam_json_response(400,['error'=>'invalid_or_expired_token']);
$pdo->prepare('UPDATE attendee_accounts SET password_hash=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
fam_json_response(200,['ok'=>true]);
