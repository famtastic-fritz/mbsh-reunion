<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$total = (int)$pdo->query("SELECT COUNT(*) FROM menu_selections")->fetchColumn();

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

// Sort by count descending
foreach ($counts as $cat => &$items) { arsort($items); }
unset($items);

// Recent submissions
$recent = $pdo->query("SELECT id, name, email, selections_json, dietary, created_at FROM menu_selections ORDER BY created_at DESC LIMIT 20")->fetchAll();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Results — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
h1{font-family:Georgia,serif;margin:0}
.stats{display:flex;gap:2rem;margin-bottom:2rem}
.stat{font-family:'JetBrains Mono',monospace;font-size:2rem;color:#C8102E;font-weight:700}
.stat-label{font-size:0.85rem;color:#666}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:2rem}
.card{background:#fff;padding:1.5rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h2{margin:0 0 1rem;font-size:1rem;font-family:Georgia,serif}
.bar{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem}
.bar-name{flex:1;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-count{font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:#666;min-width:2rem;text-align:right}
.bar-track{background:#eee;height:6px;border-radius:3px;flex:1;position:relative;overflow:hidden}
.bar-fill{background:#C8102E;height:100%;border-radius:3px}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.table{width:100%;border-collapse:collapse;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
th,td{padding:0.75rem 1rem;text-align:left;font-size:0.85rem;border-bottom:1px solid #eee}
th{background:#0A0A0A;color:#fff;font-size:0.75rem;text-transform:uppercase}
tr:hover{background:#fafafa}
.small{color:#888;font-size:0.8rem}
</style></head>
<body>
<header><h1>Gold Menu Results</h1><a class="logout" href="dashboard.php">&larr; Dashboard</a></header>

<div class="stats">
  <div><div class="stat"><?= $total ?></div><div class="stat-label">Total Submissions</div></div>
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
<table class="table">
  <thead>
    <tr><th>#</th><th>Name</th><th>Email</th><th>Hors</th><th>Salad</th><th>Entrée</th><th>Sides</th><th>Dietary</th><th>Date</th></tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r):
      $s = json_decode($r['selections_json'], true);
    ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td><?= htmlspecialchars($r['email']) ?></td>
      <td class="small"><?= htmlspecialchars(implode(', ', $s['hors'] ?? [])) ?></td>
      <td class="small"><?= htmlspecialchars($s['salad'] ?? '—') ?></td>
      <td class="small"><?= htmlspecialchars(implode(', ', $s['entree'] ?? [])) ?></td>
      <td class="small"><?= htmlspecialchars(implode(', ', $s['side'] ?? [])) ?></td>
      <td class="small"><?= htmlspecialchars($r['dietary'] ?: '—') ?></td>
      <td class="small"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recent)): ?>
    <tr><td colspan="9" style="text-align:center;padding:2rem;color:#888">No submissions yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body></html>
