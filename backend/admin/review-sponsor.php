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
  $stmt = $pdo->prepare('SELECT * FROM sponsors_pending WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(404); exit('not found'); }

  if ($action === 'approve') {
    $displayName = $_POST['display_name'] ?? ($row['company_name'] ?: $row['contact_name']);
    $tier = in_array($row['tier_interest'], ['diamond','captain','crew','friend'], true) ? $row['tier_interest'] : 'friend';
    $approvedLogo = null;
    if ($row['logo_path']) {
      // Move from pending to approved
      $approvedDir = $config['approved_uploads_path'] . '/sponsors';
      if (!is_dir($approvedDir)) mkdir($approvedDir, 0755, true);
      $approvedLogo = $approvedDir . '/' . basename($row['logo_path']);
      @rename($row['logo_path'], $approvedLogo);
    }
    $ins = $pdo->prepare('INSERT INTO sponsors_approved (pending_id, display_name, tier, logo_path, website_url) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$id, $displayName, $tier, $approvedLogo, $_POST['website_url'] ?? null]);
    $upd = $pdo->prepare("UPDATE sponsors_pending SET status='approved', reviewed_at=NOW() WHERE id=?");
    $upd->execute([$id]);
    fam_admin_audit($pdo, 'sponsor_approve', 'sponsors_pending', $id);
  } elseif ($action === 'reject') {
    $upd = $pdo->prepare("UPDATE sponsors_pending SET status='rejected', reviewed_at=NOW(), notes=? WHERE id=?");
    $upd->execute([$_POST['notes'] ?? null, $id]);
    fam_admin_audit($pdo, 'sponsor_reject', 'sponsors_pending', $id);
  }
  header('Location: review-sponsor.php');
  exit;
}

$pending = $pdo->query("SELECT * FROM sponsors_pending WHERE status='pending' ORDER BY created_at DESC")->fetchAll();
$csrf = fam_csrf_issue(session_id() ?: 'cli', $config['admin_csrf_secret']);
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Review Sponsors</title>
<style>body{font-family:Inter,sans-serif;background:#F8F4EC;padding:2rem;color:#0A0A0A}h1{font-family:Georgia,serif}.row{background:#fff;padding:1.5rem;margin-bottom:1rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}.actions{display:flex;gap:.5rem;margin-top:1rem}button{padding:.6rem 1.2rem;border:none;border-radius:3px;cursor:pointer;font-weight:600}.approve{background:#C8102E;color:#fff}.reject{background:#888;color:#fff}img{max-width:160px;display:block;margin:1rem 0}</style></head><body>
<h1>Sponsor inquiries — pending</h1>
<p><a href="dashboard.php">← Dashboard</a></p>
<?php if (!$pending): ?><p>No pending sponsor inquiries.</p><?php endif; ?>
<?php foreach ($pending as $p): ?>
  <div class="row">
    <h3><?= htmlspecialchars($p['contact_name']) ?> <?= $p['company_name'] ? '(' . htmlspecialchars($p['company_name']) . ')' : '' ?></h3>
    <p><strong>Tier:</strong> <?= htmlspecialchars($p['tier_interest']) ?> <?= $p['custom_amount'] ? '($' . (int)$p['custom_amount'] . ')' : '' ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($p['email']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($p['phone'] ?? '—') ?></p>
    <p><strong>Message:</strong> <?= nl2br(htmlspecialchars($p['message'] ?? '—')) ?></p>
    <?php if ($p['logo_path']): ?>
      <p><a href="serve-pending-upload.php?path=<?= urlencode($p['logo_path']) ?>" target="_blank">View logo (auth-gated)</a></p>
    <?php endif; ?>
    <form method="POST" class="actions">
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <input type="hidden" name="action" value="approve">
      <input type="hidden" name="X-CSRF-Token" value="<?= htmlspecialchars($csrf) ?>">
      <button type="submit" class="approve" formaction="review-sponsor.php" onclick="this.form.querySelector('[name=action]').value='approve'">Approve</button>
      <button type="submit" class="reject" onclick="this.form.querySelector('[name=action]').value='reject'">Reject</button>
    </form>
  </div>
<?php endforeach; ?>
<script>
// Inject CSRF as header (since plain forms can't set headers, post via fetch)
document.querySelectorAll('form.actions button').forEach(btn => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const form = btn.closest('form');
    form.querySelector('[name=action]').value = btn.classList.contains('approve') ? 'approve' : 'reject';
    const fd = new FormData(form);
    const csrf = fd.get('X-CSRF-Token');
    fd.delete('X-CSRF-Token');
    const r = await fetch('review-sponsor.php', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd });
    if (r.ok || r.redirected) location.reload();
    else alert('Action failed: HTTP ' + r.status);
  });
});
</script>
</body></html>
