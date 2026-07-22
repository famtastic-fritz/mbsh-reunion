<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
require_once __DIR__ . '/../lib/csrf.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  fam_require_csrf($config['admin_csrf_secret']);
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';
  if ($action === 'approve') {
    $stmt = $pdo->prepare('UPDATE memories SET approved=1, approved_at=NOW() WHERE id=?');
    $stmt->execute([$id]);
    // Move photo from pending → approved if present
    $row = $pdo->prepare('SELECT photo_path FROM memories WHERE id=?');
    $row->execute([$id]);
    $p = $row->fetchColumn();
    if ($p && strpos($p, $config['pending_uploads_path']) === 0) {
      $approvedDir = $config['approved_uploads_path'] . '/memories';
      if (!is_dir($approvedDir)) mkdir($approvedDir, 0755, true);
      $newPath = $approvedDir . '/' . basename($p);
      if (@rename($p, $newPath)) {
        $upd = $pdo->prepare('UPDATE memories SET photo_path=? WHERE id=?');
        $upd->execute([$newPath, $id]);
      }
    }
    fam_admin_audit($pdo, 'memory_approve', 'memories', $id);
  } elseif ($action === 'reject') {
    $stmt = $pdo->prepare('DELETE FROM memories WHERE id=? AND approved=0');
    $stmt->execute([$id]);
    fam_admin_audit($pdo, 'memory_reject', 'memories', $id);
  }
  header('Location: review-memory.php');
  exit;
}

$pending = $pdo->query('SELECT * FROM memories WHERE approved=0 ORDER BY created_at DESC')->fetchAll();
$csrf = fam_csrf_issue(session_id() ?: 'cli', $config['admin_csrf_secret']);
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Review Memories</title>
<style>body{font-family:Inter,sans-serif;background:#F8F4EC;padding:2rem}h1{font-family:Georgia,serif}.row{background:#fff;padding:1.5rem;margin-bottom:1rem;border-radius:4px}button{padding:.6rem 1.2rem;border:none;cursor:pointer;font-weight:600;border-radius:3px;margin-right:.5rem}.approve{background:#C8102E;color:#fff}.reject{background:#888;color:#fff}</style></head><body>
<h1>Memories — pending</h1><p><a href="dashboard.php">← Dashboard</a></p>
<?php foreach ($pending as $m): ?>
  <div class="row">
    <h3><?= htmlspecialchars($m['contributor_name']) ?> <?= $m['contributor_email'] ? '— ' . htmlspecialchars($m['contributor_email']) : '' ?></h3>
    <p><?= nl2br(htmlspecialchars($m['memory_text'])) ?></p>
    <?php if ($m['photo_path']): ?><p><a href="serve-pending-upload.php?path=<?= urlencode($m['photo_path']) ?>" target="_blank">View photo</a></p><?php endif; ?>
    <form method="POST" data-csrf="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value=""><button type="submit" class="approve" data-action="approve">Approve</button><button type="submit" class="reject" data-action="reject">Reject</button></form>
  </div>
<?php endforeach; ?>
<script>document.querySelectorAll('form button').forEach(b=>b.addEventListener('click',async e=>{e.preventDefault();const f=b.closest('form');f.querySelector('[name=action]').value=b.dataset.action;const r=await fetch('review-memory.php',{method:'POST',headers:{'X-CSRF-Token':f.dataset.csrf},body:new FormData(f)});if(r.ok||r.redirected)location.reload();else alert('failed');}));</script>
</body></html>
