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
</body></html>
