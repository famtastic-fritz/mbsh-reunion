<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$type = $_GET['type'] ?? 'rsvp';
if (!in_array($type, ['rsvp', 'menu'], true)) {
  $type = 'rsvp';
}

$whereClause = $type === 'menu'
  ? 'm.menu_created_at IS NULL'
  : 'r.rsvp_created_at IS NULL';
$sql = <<<SQL
SELECT
  h.first_name,
  h.last_name,
  h.hs_name,
  h.email_key AS email,
  h.phone,
  h.mailing_address,
  h.reunion_month,
  h.budget,
  h.comments,
  h.imported_at,
  h.survey_row_count,
  r.rsvp_created_at,
  m.menu_created_at
FROM (
  SELECT
    LOWER(TRIM(email)) AS email_key,
    MAX(NULLIF(first_name, '')) AS first_name,
    MAX(NULLIF(last_name, '')) AS last_name,
    MAX(NULLIF(hs_name, '')) AS hs_name,
    MAX(NULLIF(phone, '')) AS phone,
    MAX(NULLIF(mailing_address, '')) AS mailing_address,
    MAX(NULLIF(reunion_month, '')) AS reunion_month,
    MAX(NULLIF(budget, '')) AS budget,
    MAX(NULLIF(comments, '')) AS comments,
    MAX(imported_at) AS imported_at,
    COUNT(*) AS survey_row_count
  FROM surveys
  WHERE is_imported = 1
    AND email IS NOT NULL
    AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) h
LEFT JOIN (
  SELECT LOWER(TRIM(email)) AS email_key, MAX(created_at) AS rsvp_created_at
  FROM rsvps
  WHERE email IS NOT NULL AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) r ON r.email_key = h.email_key
LEFT JOIN (
  SELECT LOWER(TRIM(email)) AS email_key, MAX(created_at) AS menu_created_at
  FROM menu_selections
  WHERE email IS NOT NULL AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) m ON m.email_key = h.email_key
WHERE {$whereClause}
ORDER BY COALESCE(NULLIF(h.last_name, ''), NULLIF(h.hs_name, ''), h.email_key), COALESCE(NULLIF(h.first_name, ''), h.email_key)
SQL;
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$title = $type === 'rsvp' ? 'Historical contacts missing RSVP' : 'Historical contacts missing menu';
$otherType = $type === 'rsvp' ? 'menu' : 'rsvp';
$otherLabel = $type === 'rsvp' ? 'View missing menu' : 'View missing RSVP';
$exportSource = $type === 'rsvp' ? 'historical_missing_rsvp' : 'historical_missing_menu';
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
h1{font-family:Georgia,serif;margin:0}.actions{display:flex;gap:.75rem;flex-wrap:wrap}.actions a{background:#fff;padding:.7rem 1rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;color:#0A0A0A;font-weight:600}
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}.card{background:#fff;padding:1rem 1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}.num{font-family:'JetBrains Mono',monospace;font-size:2rem;color:#C8102E;font-weight:700}.label{color:#666;font-size:.9rem}
.table-wrap{background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:auto}.note{color:#666;font-size:.95rem;line-height:1.5;margin:0 0 1rem}
table{width:100%;border-collapse:collapse;min-width:1100px}th,td{padding:.75rem .9rem;border-bottom:1px solid #eee;text-align:left;vertical-align:top}th{background:#111;color:#fff;position:sticky;top:0}tbody tr:nth-child(even){background:#faf7f1}.muted{color:#777;font-size:.85rem}
</style></head><body>
<header>
  <h1><?= htmlspecialchars($title) ?></h1>
  <div class="actions">
    <a href="dashboard.php">&larr; Dashboard</a>
    <a href="reports.php">Reports</a>
    <a href="follow-up.php?type=<?= htmlspecialchars($otherType) ?>"><?= htmlspecialchars($otherLabel) ?></a>
    <a href="export-emails.php?source=<?= htmlspecialchars($exportSource) ?>">Export this list</a>
  </div>
</header>

<p class="note">This is deduped by email so the committee sees real outreach targets, not duplicated historical survey rows. <strong>survey rows</strong> shows how many historical survey entries rolled up into that contact.</p>

<div class="summary">
  <div class="card"><div class="num"><?= count($rows) ?></div><div class="label">Distinct contacts in this follow-up list</div></div>
  <div class="card"><div class="num"><?= $type === 'rsvp' ? 'RSVP' : 'Menu' ?></div><div class="label">Missing response type</div></div>
</div>

<div class="table-wrap">
<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Mailing address</th>
      <th>Preferred month</th>
      <th>Budget</th>
      <th>Comments</th>
      <th>Imported at</th>
      <th>Survey rows</th>
      <th>RSVP status</th>
      <th>Menu status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $row):
      $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
      if ($name === '') $name = (string)($row['hs_name'] ?: $row['email']);
    ?>
    <tr>
      <td>
        <strong><?= htmlspecialchars($name) ?></strong>
        <?php if (!empty($row['hs_name'])): ?><div class="muted">HS name: <?= htmlspecialchars((string)$row['hs_name']) ?></div><?php endif; ?>
      </td>
      <td><?= htmlspecialchars((string)$row['email']) ?></td>
      <td><?= htmlspecialchars((string)($row['phone'] ?? '')) ?></td>
      <td><?= nl2br(htmlspecialchars((string)($row['mailing_address'] ?? ''))) ?></td>
      <td><?= htmlspecialchars((string)($row['reunion_month'] ?? '')) ?></td>
      <td><?= htmlspecialchars((string)($row['budget'] ?? '')) ?></td>
      <td><?= nl2br(htmlspecialchars((string)($row['comments'] ?? ''))) ?></td>
      <td><?= htmlspecialchars((string)($row['imported_at'] ?? '')) ?></td>
      <td><?= (int)($row['survey_row_count'] ?? 0) ?></td>
      <td><?= !empty($row['rsvp_created_at']) ? 'Completed (' . htmlspecialchars((string)$row['rsvp_created_at']) . ')' : 'Missing' ?></td>
      <td><?= !empty($row['menu_created_at']) ? 'Completed (' . htmlspecialchars((string)$row['menu_created_at']) . ')' : 'Missing' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
</body></html>
