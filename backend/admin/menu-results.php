<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$summary = $pdo->query("SELECT COUNT(*) AS total, SUM(submitter_email_status = 'sent') AS submitter_sent, SUM(submitter_email_status = 'failed') AS submitter_failed, SUM(committee_email_status = 'sent') AS committee_sent, SUM(committee_email_status = 'failed') AS committee_failed FROM menu_selections")->fetch();
$total = (int)($summary['total'] ?? 0);
$submitterSent = (int)($summary['submitter_sent'] ?? 0);
$submitterFailed = (int)($summary['submitter_failed'] ?? 0);
$committeeSent = (int)($summary['committee_sent'] ?? 0);
$committeeFailed = (int)($summary['committee_failed'] ?? 0);

// Aggregate selections
$allSelections = $pdo->query("SELECT selections_json FROM menu_selections")->fetchAll(PDO::FETCH_COLUMN);
$counts = [
  'hors' => [],
  'salad' => [],
  'entree' => [],
  'side' => [],
];
foreach ($allSelections as $json) {
  $s = json_decode($json, true);
  if (!is_array($s)) continue;
  foreach (['hors','salad','entree','side'] as $cat) {
    $items = $s[$cat] ?? [];
    if (!is_array($items)) $items = [$items];
    foreach ($items as $item) {
      if (!is_string($item) || $item === '') continue;
      $counts[$cat][$item] = ($counts[$cat][$item] ?? 0) + 1;
    }
  }
}

foreach ($counts as $cat => &$items) { arsort($items); }
unset($items);

$recent = $pdo->query("SELECT id, name, email, selections_json, dietary, submitter_email_status, submitter_email_error, submitter_email_sent_at, submitter_email_message_id, committee_email_status, committee_email_error, committee_email_sent_at, committee_email_message_id, created_at FROM menu_selections ORDER BY created_at DESC LIMIT 50")->fetchAll();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Results — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap}
h1{font-family:Georgia,serif;margin:0}
.stats{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem}
.stat-card{background:#fff;padding:1rem 1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);min-width:160px}
.stat{font-family:'JetBrains Mono',monospace;font-size:2rem;color:#C8102E;font-weight:700}
.stat-label{font-size:0.85rem;color:#666}
.actions{display:flex;gap:0.75rem;flex-wrap:wrap}
.actions a{background:#fff;padding:.7rem 1rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;color:#0A0A0A;font-weight:600}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:2rem}
.card{background:#fff;padding:1.5rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h2{margin:0 0 1rem;font-size:1rem;font-family:Georgia,serif}
.bar{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem}
.bar-name{flex:1;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-count{font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:#666;min-width:2rem;text-align:right}
.bar-track{background:#eee;height:6px;border-radius:3px;flex:1;position:relative;overflow:hidden}
.bar-fill{background:#C8102E;height:100%;border-radius:3px}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.table-wrap{overflow:auto}
.table{width:100%;border-collapse:collapse;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
th,td{padding:0.75rem 1rem;text-align:left;font-size:0.85rem;border-bottom:1px solid #eee;vertical-align:top}
th{background:#0A0A0A;color:#fff;font-size:0.75rem;text-transform:uppercase}
tr:hover{background:#fafafa}
.small{color:#888;font-size:0.8rem}
.status{display:inline-block;padding:.2rem .45rem;border-radius:999px;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.status-sent{background:#e8f5e9;color:#2e7d32}
.status-failed{background:#ffebee;color:#c62828}
.status-pending{background:#fff3e0;color:#ef6c00}
.error{margin-top:.35rem;color:#b71c1c;font-size:.75rem;max-width:18rem;white-space:normal}
</style></head>
<body>
<header>
  <h1>Gold Menu Results</h1>
  <div class="actions">
    <a href="dashboard.php">&larr; Dashboard</a>
    <a href="export-emails.php?source=menu">Export menu CSV</a>
    <a href="reports.php">Reports</a>
  </div>
</header>

<div class="stats">
  <div class="stat-card"><div class="stat"><?= $total ?></div><div class="stat-label">Total submissions</div></div>
  <div class="stat-card"><div class="stat"><?= $submitterSent ?></div><div class="stat-label">Submitter emails sent</div></div>
  <div class="stat-card"><div class="stat"><?= $submitterFailed ?></div><div class="stat-label">Submitter emails failed</div></div>
  <div class="stat-card"><div class="stat"><?= $committeeSent ?></div><div class="stat-label">Committee emails sent</div></div>
  <div class="stat-card"><div class="stat"><?= $committeeFailed ?></div><div class="stat-label">Committee emails failed</div></div>
</div>

<div class="grid">
  <?php foreach ($counts as $cat => $items): if (empty($items)) continue; $max = max($items); ?>
  <div class="card">
    <h2><?= ucfirst($cat) ?></h2>
    <?php foreach ($items as $name => $count): $pct = $max > 0 ? round(($count / $max) * 100) : 0; ?>
      <div class="bar">
        <span class="bar-name"><?= htmlspecialchars($name) ?></span>
        <span class="bar-count"><?= $count ?></span>
      </div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>

<h2>Recent Submissions</h2>
<div class="table-wrap">
<table class="table">
  <thead>
    <tr><th>#</th><th>Name</th><th>Email</th><th>Selections</th><th>Submitter Email</th><th>Committee Email</th><th>Date</th></tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r):
      $s = json_decode($r['selections_json'], true) ?: [];
      $submitterClass = 'status-' . htmlspecialchars((string)($r['submitter_email_status'] ?: 'pending'));
      $committeeClass = 'status-' . htmlspecialchars((string)($r['committee_email_status'] ?: 'pending'));
    ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['email']) ?></td>
      <td class="small">
        <div><strong>Hors:</strong> <?= htmlspecialchars(implode(', ', $s['hors'] ?? [])) ?></div>
        <div><strong>Salad:</strong> <?= htmlspecialchars($s['salad'] ?? '—') ?></div>
        <div><strong>Entrée:</strong> <?= htmlspecialchars(implode(', ', $s['entree'] ?? [])) ?></div>
        <div><strong>Sides:</strong> <?= htmlspecialchars(implode(', ', $s['side'] ?? [])) ?></div>
        <div><strong>Dietary:</strong> <?= htmlspecialchars($r['dietary'] ?: '—') ?></div>
      </td>
      <td class="small">
        <span class="status <?= $submitterClass ?>"><?= htmlspecialchars((string)($r['submitter_email_status'] ?: 'pending')) ?></span>
        <?php if (!empty($r['submitter_email_sent_at'])): ?><div>Sent: <?= htmlspecialchars($r['submitter_email_sent_at']) ?></div><?php endif; ?>
        <?php if (!empty($r['submitter_email_message_id'])): ?><div>ID: <?= htmlspecialchars($r['submitter_email_message_id']) ?></div><?php endif; ?>
        <?php if (!empty($r['submitter_email_error'])): ?><div class="error"><?= htmlspecialchars($r['submitter_email_error']) ?></div><?php endif; ?>
      </td>
      <td class="small">
        <span class="status <?= $committeeClass ?>"><?= htmlspecialchars((string)($r['committee_email_status'] ?: 'pending')) ?></span>
        <?php if (!empty($r['committee_email_sent_at'])): ?><div>Sent: <?= htmlspecialchars($r['committee_email_sent_at']) ?></div><?php endif; ?>
        <?php if (!empty($r['committee_email_message_id'])): ?><div>ID: <?= htmlspecialchars($r['committee_email_message_id']) ?></div><?php endif; ?>
        <?php if (!empty($r['committee_email_error'])): ?><div class="error"><?= htmlspecialchars($r['committee_email_error']) ?></div><?php endif; ?>
      </td>
      <td class="small"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recent)): ?>
    <tr><td colspan="7" style="text-align:center;padding:2rem;color:#888">No submissions yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>
</body></html>
