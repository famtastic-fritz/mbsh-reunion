<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$total = (int)$pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();

// Recent submissions
$recent = $pdo->query("SELECT * FROM surveys ORDER BY created_at DESC LIMIT 50")->fetchAll();

// Simple aggregations
$vibes = $pdo->query("SELECT reunion_vibe, COUNT(*) as c FROM surveys WHERE reunion_vibe IS NOT NULL AND reunion_vibe != '' GROUP BY reunion_vibe ORDER BY c DESC")->fetchAll();
$timings = $pdo->query("SELECT reunion_timing, COUNT(*) as c FROM surveys WHERE reunion_timing IS NOT NULL AND reunion_timing != '' GROUP BY reunion_timing ORDER BY c DESC")->fetchAll();
$travels = $pdo->query("SELECT travel_method, COUNT(*) as c FROM surveys WHERE travel_method IS NOT NULL AND travel_method != '' GROUP BY travel_method ORDER BY c DESC")->fetchAll();
$hotelCount = (int)$pdo->query("SELECT COUNT(*) FROM surveys WHERE need_hotel = 1")->fetchColumn();
$plusOneTotal = (int)$pdo->query("SELECT SUM(plus_one_count) FROM surveys")->fetchColumn();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Class Survey Results — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
h1{font-family:Georgia,serif;margin:0}
.stats{display:flex;gap:2rem;margin-bottom:2rem;flex-wrap:wrap}
.stat{font-family:'JetBrains Mono',monospace;font-size:2rem;color:#C8102E;font-weight:700}
.stat-label{font-size:0.85rem;color:#666}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;margin-bottom:2rem}
.card{background:#fff;padding:1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h2{margin:0 0 0.75rem;font-size:0.95rem;font-family:Georgia,serif}
.bar{display:flex;align-items:center;gap:0.5rem;margin-bottom:0.3rem}
.bar-name{flex:1;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bar-count{font-family:'JetBrains Mono',monospace;font-size:0.8rem;color:#666;min-width:2rem;text-align:right}
.bar-track{background:#eee;height:6px;border-radius:3px;flex:1;position:relative;overflow:hidden}
.bar-fill{background:#C8102E;height:100%;border-radius:3px}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.table{width:100%;border-collapse:collapse;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);font-size:0.85rem}
th,td{padding:0.6rem 0.75rem;text-align:left;border-bottom:1px solid #eee}
th{background:#0A0A0A;color:#fff;font-size:0.75rem;text-transform:uppercase;white-space:nowrap}
tr:hover{background:#fafafa}
.small{color:#888;font-size:0.8rem}
.wrap{max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
</style></head>
<body>
<header><h1>Class Survey Results</h1><a class="logout" href="dashboard.php">&larr; Dashboard</a></header>

<div class="stats">
  <div><div class="stat"><?= $total ?></div><div class="stat-label">Total Submissions</div></div>
  <div><div class="stat"><?= $hotelCount ?></div><div class="stat-label">Need Hotel</div></div>
  <div><div class="stat"><?= $plusOneTotal ?></div><div class="stat-label">Plus-Ones</div></div>
</div>

<div class="grid">
  <div class="card">
    <h2>Reunion Vibe</h2>
    <?php if (empty($vibes)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($vibes) ? max(array_column($vibes, 'c')) : 0; ?>
    <?php foreach ($vibes as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['reunion_vibe'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2>Timing Preference</h2>
    <?php if (empty($timings)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($timings) ? max(array_column($timings, 'c')) : 0; ?>
    <?php foreach ($timings as $t): $pct = $max > 0 ? round(($t['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($t['reunion_timing'] ?: '—') ?></span><span class="bar-count"><?= (int)$t['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2>Travel Method</h2>
    <?php if (empty($travels)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($travels) ? max(array_column($travels, 'c')) : 0; ?>
    <?php foreach ($travels as $t): $pct = $max > 0 ? round(($t['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($t['travel_method'] ?: '—') ?></span><span class="bar-count"><?= (int)$t['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
</div>

<h2>Recent Submissions</h2>
<table class="table">
  <thead>
    <tr><th>#</th><th>Name</th><th>Email</th><th>City</th><th>Vibe</th><th>Timing</th><th>Travel</th><th>Hotel</th><th>+1s</th><th>Dietary</th><th>Date</th></tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td class="wrap"><?= htmlspecialchars($r['full_name']) ?></td>
      <td class="wrap"><?= htmlspecialchars($r['email']) ?></td>
      <td class="wrap"><?= htmlspecialchars($r['current_city'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['reunion_vibe'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['reunion_timing'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['travel_method'] ?: '—') ?></td>
      <td><?= $r['need_hotel'] ? 'Yes' : 'No' ?></td>
      <td><?= (int)$r['plus_one_count'] ?></td>
      <td class="wrap small"><?= htmlspecialchars($r['dietary'] ?: '—') ?></td>
      <td class="small"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recent)): ?>
    <tr><td colspan="11" style="text-align:center;padding:2rem;color:#888">No submissions yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body></html>
