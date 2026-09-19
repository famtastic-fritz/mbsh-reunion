<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$capsules = $pdo->query("SELECT * FROM time_capsules ORDER BY created_at DESC")->fetchAll();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Time Capsules — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
h1{font-family:Georgia,serif;margin:0}
.grid{display:grid;gap:1rem}
.card{background:#fff;padding:1.5rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h3{margin:0 0 .5rem;font-size:1rem}
.card .meta{color:#888;font-size:.8rem;margin-bottom:.75rem}
.card .q{font-weight:600;font-size:.85rem;color:#444;margin-bottom:.25rem}
.card .a{white-space:pre-wrap;font-size:.9rem;line-height:1.5}
.sent{color:#2e7d32;font-weight:700}
.pending{color:#f57c00;font-weight:700}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.empty{text-align:center;padding:3rem;color:#888}
</style></head><body>
<header><h1>Time Capsules (<?= count($capsules) ?>)</h1><a class="logout" href="dashboard.php">← Dashboard</a></header>

<?php if (empty($capsules)): ?>
<div class="empty">No time capsules submitted yet.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($capsules as $c): ?>
  <div class="card">
    <h3><?= htmlspecialchars($c['email']) ?></h3>
    <div class="meta">
      #<?= (int)$c['id'] ?> |
      Send date: <?= date('F j, Y g:i A', strtotime($c['send_date'])) ?> |
      Status: <?= $c['sent_at'] ? '<span class="sent">SENT</span> ('.date('M j, Y g:i A', strtotime($c['sent_at'])).')' : '<span class="pending">PENDING</span>' ?> |
      Submitted: <?= date('M j, Y', strtotime($c['created_at'])) ?>
      <?php if ($c['send_attempts'] > 0): ?>| Attempts: <?= (int)$c['send_attempts'] ?><?php endif; ?>
    </div>
    <?php if ($c['song_answer']): ?>
      <div class="q">Song that defined your time at MBSH:</div>
      <div class="a"><?= nl2br(htmlspecialchars($c['song_answer'])) ?></div>
    <?php endif; ?>
    <?php if ($c['person_answer']): ?>
      <div class="q">Someone you wish you could reconnect with:</div>
      <div class="a"><?= nl2br(htmlspecialchars($c['person_answer'])) ?></div>
    <?php endif; ?>
    <?php if ($c['memory_answer']): ?>
      <div class="q">A memory that still makes you smile:</div>
      <div class="a"><?= nl2br(htmlspecialchars($c['memory_answer'])) ?></div>
    <?php endif; ?>
    <?php if ($c['send_error']): ?>
      <div style="color:#c62828;font-size:.85rem;margin-top:.5rem">Error: <?= htmlspecialchars($c['send_error']) ?></div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<!-- famtastic-creator-credit:start -->
<style>@media(max-width:600px){[data-famtastic-creator-credit="v1"]{padding-bottom:192px!important}}</style>
<div data-famtastic-creator-credit="v1" style="box-sizing:border-box;clear:both;position:relative;width:100%;padding:24px 16px 88px;text-align:center;background:#080a08;color:#f5f5ee;grid-column:1 / -1">
  <p style="margin:0 0 10px;font:12px/1.5 Arial,sans-serif;color:#f5f5ee">Created by FAMtasticDesigns.com</p>
  <a href="https://famtasticdesigns.com/?utm_source=mbsh96reunion&amp;utm_medium=creator_credit&amp;utm_campaign=created_by_famtastic" aria-label="Created by FAMtastic Designs — visit our website" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;max-width:100%;border-radius:6px">
    <img src="/assets/famtastic/famtastic-designs-logo-v1.png" alt="FAMtastic Designs" width="2172" height="724" loading="lazy" style="display:block;width:190px;max-width:100%;height:auto;border:0">
  </a>
</div>
<!-- famtastic-creator-credit:end -->
</body></html>
