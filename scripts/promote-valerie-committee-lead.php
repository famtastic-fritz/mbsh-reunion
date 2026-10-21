<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--apply' || getenv('MBSH_ALLOW_HISTORICAL_ROLE_CHANGE') !== '1') { fwrite(STDERR, "Historical role changes are disabled.\n"); exit(2); }

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$config = require '/home/nineoo/.config/mbsh-config.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']),
    $config['db_user'],
    $config['db_password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$pdo->beginTransaction();
try {
    $ownerQuery = $pdo->prepare(
        "SELECT a.id FROM attendee_accounts a
         JOIN portal_staff_memberships m ON m.attendee_id = a.id
         WHERE LOWER(a.email) = ? AND m.role = 'site_owner' AND m.status = 'active'"
    );
    $ownerQuery->execute(['fritz.medine@gmail.com']);
    $ownerId = (int)$ownerQuery->fetchColumn();

    $valerieQuery = $pdo->prepare('SELECT id, public_id FROM attendee_accounts WHERE LOWER(email) = ?');
    $valerieQuery->execute(['valerievalcourt96@gmail.com']);
    $valerie = $valerieQuery->fetch();
    if ($ownerId < 1 || !$valerie) {
        throw new RuntimeException('Owner or Valerie account was not found.');
    }

    $pdo->prepare(
        "UPDATE portal_staff_memberships
         SET role = 'committee_lead', status = 'active', granted_by = ?, granted_at = NOW(), revoked_at = NULL
         WHERE attendee_id = ?"
    )->execute(['owner-approved:' . $ownerId, (int)$valerie['id']]);

    $pdo->prepare(
        "INSERT INTO portal_staff_audit_log
           (actor_attendee_id, actor_role, action, target_type, target_public_id, details_json, ip_address)
         VALUES (?, 'site_owner', 'membership_changed', 'attendee', ?, ?, 'Codex_operator')"
    )->execute([
        $ownerId,
        $valerie['public_id'],
        json_encode(['role'=>'committee_lead', 'source'=>'owner_request'], JSON_UNESCAPED_SLASHES),
    ]);
    $pdo->commit();

    $verify = $pdo->prepare(
        "SELECT a.email, a.status, a.email_verified_at, m.role, m.status AS membership_status
         FROM attendee_accounts a JOIN portal_staff_memberships m ON m.attendee_id = a.id
         WHERE a.id = ?"
    );
    $verify->execute([(int)$valerie['id']]);
    echo json_encode($verify->fetch(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
