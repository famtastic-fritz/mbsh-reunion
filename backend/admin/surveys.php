<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$total = (int)$pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
$imported = (int)$pdo->query("SELECT COUNT(*) FROM surveys WHERE is_imported = 1")->fetchColumn();

// Aggregations matching MS Forms fields
$planning = $pdo->query("SELECT planning, COUNT(*) as c FROM surveys WHERE planning IS NOT NULL AND planning != '' GROUP BY planning ORDER BY c DESC")->fetchAll();
$tshirt = $pdo->query("SELECT tshirt_size, COUNT(*) as c FROM surveys WHERE tshirt_size IS NOT NULL AND tshirt_size != '' GROUP BY tshirt_size ORDER BY c DESC")->fetchAll();
$duration = $pdo->query("SELECT duration, COUNT(*) as c FROM surveys WHERE duration IS NOT NULL AND duration != '' GROUP BY duration ORDER BY c DESC")->fetchAll();
$days = $pdo->query("SELECT days_of_week, COUNT(*) as c FROM surveys WHERE days_of_week IS NOT NULL AND days_of_week != '' GROUP BY days_of_week ORDER BY c DESC LIMIT 10")->fetchAll();
$reunionType = $pdo->query("SELECT reunion_type, COUNT(*) as c FROM surveys WHERE reunion_type IS NOT NULL AND reunion_type != '' GROUP BY reunion_type ORDER BY c DESC LIMIT 10")->fetchAll();
$venue = $pdo->query("SELECT venue_type, COUNT(*) as c FROM surveys WHERE venue_type IS NOT NULL AND venue_type != '' GROUP BY venue_type ORDER BY c DESC LIMIT 10")->fetchAll();
$budget = $pdo->query("SELECT budget, COUNT(*) as c FROM surveys WHERE budget IS NOT NULL AND budget != '' GROUP BY budget ORDER BY c DESC")->fetchAll();
$openOther = $pdo->query("SELECT open_other_classes, COUNT(*) as c FROM surveys WHERE open_other_classes IS NOT NULL AND open_other_classes != '' GROUP BY open_other_classes ORDER BY c DESC LIMIT 10")->fetchAll();
$groupme = (int)$pdo->query("SELECT COUNT(*) FROM surveys WHERE groupme = 'Yes'")->fetchColumn();
$contact = $pdo->query("SELECT contact_pref, COUNT(*) as c FROM surveys WHERE contact_pref IS NOT NULL AND contact_pref != '' GROUP BY contact_pref ORDER BY c DESC")->fetchAll();

// Recent submissions
$recent = $pdo->query("SELECT * FROM surveys ORDER BY created_at DESC LIMIT 50")->fetchAll();
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
.wrap{max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.badge{font-size:0.7rem;padding:0.15rem 0.4rem;border-radius:3px;background:#eee}
.badge--imported{background:#C8102E;color:#fff}
</style></head>
<body>
<header><h1>Class Survey Results</h1><a class="logout" href="dashboard.php">&larr; Dashboard</a></header>

<div class="stats">
  <div><div class="stat"><?= $total ?></div><div class="stat-label">Total Submissions</div></div>
  <div><div class="stat"><?= $imported ?></div><div class="stat-label">Historical (Imported)</div></div>
  <div><div class="stat"><?= $groupme ?></div><div class="stat-label">Want GroupMe</div></div>
</div>

<div class="grid">
  <div class="card"><h2>Planning Participation</h2>
    <?php if (empty($planning)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($planning) ? max(array_column($planning, 'c')) : 0; ?>
    <?php foreach ($planning as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['planning'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>T-Shirt Size</h2>
    <?php if (empty($tshirt)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($tshirt) ? max(array_column($tshirt, 'c')) : 0; ?>
    <?php foreach ($tshirt as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['tshirt_size'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Duration</h2>
    <?php if (empty($duration)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($duration) ? max(array_column($duration, 'c')) : 0; ?>
    <?php foreach ($duration as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['duration'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Day(s) of Week</h2>
    <?php if (empty($days)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($days) ? max(array_column($days, 'c')) : 0; ?>
    <?php foreach ($days as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['days_of_week'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Reunion Type</h2>
    <?php if (empty($reunionType)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($reunionType) ? max(array_column($reunionType, 'c')) : 0; ?>
    <?php foreach ($reunionType as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['reunion_type'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Venue</h2>
    <?php if (empty($venue)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($venue) ? max(array_column($venue, 'c')) : 0; ?>
    <?php foreach ($venue as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['venue_type'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Budget</h2>
    <?php if (empty($budget)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($budget) ? max(array_column($budget, 'c')) : 0; ?>
    <?php foreach ($budget as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['budget'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
  <div class="card"><h2>Open to Other Classes?</h2>
    <?php if (empty($openOther)): ?><p class="small">No data yet.</p><?php endif; ?>
    <?php $max = !empty($openOther) ? max(array_column($openOther, 'c')) : 0; ?>
    <?php foreach ($openOther as $v): $pct = $max > 0 ? round(($v['c'] / $max) * 100) : 0; ?>
      <div class="bar"><span class="bar-name"><?= htmlspecialchars($v['open_other_classes'] ?: '—') ?></span><span class="bar-count"><?= (int)$v['c'] ?></span></div>
      <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <?php endforeach; ?>
  </div>
</div>

<h2>Recent Submissions</h2>
<table class="table">
  <thead>
    <tr>
      <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>T-Shirt</th>
      <th>Planning</th><th>Contact</th><th>GroupMe</th><th>Month</th>
      <th>Duration</th><th>Days</th><th>Type</th><th>Venue</th><th>Budget</th><th>Open?</th>
      <th>Source</th><th>Date</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($recent as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td class="wrap"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
      <td class="wrap"><?= htmlspecialchars($r['email']) ?></td>
      <td class="wrap"><?= htmlspecialchars($r['phone'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['tshirt_size'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['planning'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['contact_pref'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['groupme'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['reunion_month'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['duration'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['days_of_week'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['reunion_type'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['venue_type'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['budget'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['open_other_classes'] ?: '—') ?></td>
      <td><?= $r['is_imported'] ? '<span class="badge badge--imported">Imported</span>' : '<span class="badge">Live</span>' ?></td>
      <td class="small"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($recent)): ?>
    <tr><td colspan="17" style="text-align:center;padding:2rem;color:#888">No submissions yet.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body></html>
