<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') fam_json_response(405, ['error'=>'method_not_allowed']);
$id = fam_consume_auth_token($pdo, trim((string)($_GET['token'] ?? '')), 'verify_email');
if (!$id) fam_json_response(400, ['error'=>'invalid_or_expired_token']);
$pdo->prepare("UPDATE attendee_accounts SET email_verified_at=COALESCE(email_verified_at,NOW()),status='active' WHERE id=? AND status='pending_verification'")->execute([$id]);
$q=$pdo->prepare('SELECT public_id,email FROM attendee_accounts WHERE id=?'); $q->execute([$id]);
$account=$q->fetch();
fam_attendee_login_session($id, (string)$account['public_id']);
try {
  fam_fulfill_paid_ticket_orders_for_email($pdo,$config,(string)$account['email']);
} catch(Throwable $error) {
  error_log('[portal-verify] paid ticket fulfillment: '.$error->getMessage());
}
fam_json_response(200, ['ok'=>true,'message'=>'Email verified.']);
