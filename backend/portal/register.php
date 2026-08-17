<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
fam_portal_json_method(['POST']);

try {
  fam_rate_limit($pdo, 'portal_register', 3600, 5);
  $data = fam_read_json_body();
  $email = fam_email($data, 'email', true);
  $first = fam_required($data, 'first_name', 100);
  $last = fam_required($data, 'last_name', 100);
  $password = (string)($data['password'] ?? '');
  if (!fam_password_valid($password)) throw new ValidationError('Password must be 12+ characters and contain a letter and number');
  $pdo->beginTransaction();
  $q = $pdo->prepare('SELECT id,status FROM attendee_accounts WHERE email=? FOR UPDATE');
  $q->execute([$email]);
  $existing = $q->fetch();
  if ($existing && $existing['status'] !== 'pending_verification') throw new ValidationError('An account already exists for this email');
  if ($existing) {
    $id = (int)$existing['id'];
    $pdo->prepare('UPDATE attendee_accounts SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    $pdo->prepare('UPDATE attendee_profiles SET first_name=?,last_name=? WHERE attendee_id=?')->execute([$first,$last,$id]);
  } else {
    $pdo->prepare('INSERT INTO attendee_accounts (public_id,email,password_hash) VALUES (?,?,?)')->execute([fam_uuid_v4(),$email,password_hash($password,PASSWORD_DEFAULT)]);
    $id = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO attendee_profiles (attendee_id,first_name,last_name,graduation_year) VALUES (?,?,?,1996)')->execute([$id,$first,$last]);
    $pdo->prepare('INSERT INTO attendee_preferences (attendee_id) VALUES (?)')->execute([$id]);
  }
  // Reconcile historical RSVP identity by normalized email when that legacy table exists.
  try {
    $r=$pdo->prepare('SELECT id FROM rsvps WHERE LOWER(TRIM(email))=? ORDER BY created_at DESC LIMIT 1'); $r->execute([$email]); $rsvpId=$r->fetchColumn();
    if($rsvpId) $pdo->prepare('INSERT IGNORE INTO attendee_record_links (attendee_id,source_type,source_id) VALUES (?,"rsvp",?)')->execute([$id,(string)$rsvpId]);
  } catch(PDOException $ignored) { /* Fresh proof schemas may not include the legacy RSVP table. */ }
  $token = fam_issue_auth_token($pdo, $id, 'verify_email', 86400);
  $pdo->commit();
  fam_queue_verification_email($pdo,$config,$email,$first,$token);
  fam_json_response(202, ['ok'=>true,'message'=>'Check your email to verify the account.']);
} catch (ValidationError $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  fam_json_response(400, ['error'=>'validation_error','message'=>$e->getMessage()]);
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('[portal-register] '.$e->getMessage());
  fam_json_response(500, ['error'=>'internal_error']);
}
