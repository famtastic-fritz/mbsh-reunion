<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
fam_portal_json_method(['POST']);
fam_rate_limit($pdo,'portal_forgot_password',3600,5);
$data=fam_read_json_body(); $email=fam_email($data,'email',true);
$q=$pdo->prepare("SELECT id FROM attendee_accounts WHERE email=? AND status='active'"); $q->execute([$email]); $id=(int)$q->fetchColumn();
if ($id) { $token=fam_issue_auth_token($pdo,$id,'reset_password',3600); fam_queue_reset_email($pdo,$config,$email,$token); }
fam_json_response(202, ['ok'=>true,'message'=>'If an account exists, a reset link has been sent.']);
