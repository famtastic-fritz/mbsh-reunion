<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff = fam_require_portal_staff($pdo, 'view_menu');
$snapshot = fam_production_snapshot($config);
if (!$snapshot) {
    fam_json_response(503, ['error'=>'snapshot_unavailable', 'message'=>'Dinner records are temporarily unavailable.']);
}
$rows = $snapshot->query(
    "SELECT m.id,m.name,m.email,m.selections_json,m.dietary,m.submitter_email_status,
            m.committee_email_status,m.notification_email_status,m.created_at,
            a.public_id AS attendee_public_id,
            CASE WHEN a.id IS NULL THEN 0 ELSE 1 END AS account_linked
     FROM menu_selections m
     LEFT JOIN attendee_accounts a ON LOWER(TRIM(a.email))=LOWER(TRIM(m.email))
     ORDER BY m.created_at DESC,m.id DESC"
)->fetchAll();
$linked = 0;
$dietary = 0;
foreach ($rows as $row) {
    $linked += (int)$row['account_linked'];
    $dietary += trim((string)($row['dietary'] ?? '')) === '' ? 0 : 1;
}
fam_json_response(200, [
    'staff'=>fam_portal_staff_client_access($staff),
    'data_context'=>fam_snapshot_context($snapshot),
    'summary'=>['total'=>count($rows), 'linked_accounts'=>$linked, 'dietary_notes'=>$dietary],
    'selections'=>$rows,
]);
