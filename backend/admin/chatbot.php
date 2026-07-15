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
  if ($action === 'respond') {
    $stmt = $pdo->prepare('UPDATE chatbot_questions SET responded=1, response_notes=?, responded_at=NOW() WHERE id=?');
    $stmt->execute([$_POST['notes'] ?? null, $id]);
    fam_admin_audit($pdo, 'chatbot_respond', 'chatbot_questions', $id);
  } elseif ($action === 'unrespond') {
    $stmt = $pdo->prepare('UPDATE chatbot_questions SET responded=0, response_notes=NULL, responded_at=NULL WHERE id=?');
    $stmt->execute([$id]);
    fam_admin_audit($pdo, 'chatbot_unrespond', 'chatbot_questions', $id);
  }
  header('Location: chatbot.php');
  exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM chatbot_questions WHERE 1=1";
if ($filter === 'unresponded') {
  $sql .= " AND responded = 0 AND was_fallback = 1";
} elseif ($filter === 'responded') {
  $sql .= " AND responded = 1";
} elseif ($filter === 'fallback') {
  $sql .= " AND was_fallback = 1";
}
$sql .= " ORDER BY created_at DESC";
$questions = $pdo->query($sql)->fetchAll();
$csrf = fam_csrf_issue(session_id() ?: 'cli', $config['admin_csrf_secret']);
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chatbot Questions — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
h1{font-family:Georgia,serif;margin:0}
.filters{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.filters a{text-decoration:none;padding:.5rem 1rem;border-radius:3px;font-size:.9rem;font-weight:600;background:#fff;color:#0A0A0A;border:1px solid #ddd}
.filters a.active{background:#C8102E;color:#fff;border-color:#C8102E}
.grid{display:grid;gap:1rem}
.card{background:#fff;padding:1.5rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.card h3{margin:0 0 .25rem;font-size:1rem}
.card .meta{color:#888;font-size:.8rem;margin-bottom:.75rem}
.card .q{white-space:pre-wrap;font-size:.95rem;line-height:1.5;margin-bottom:.75rem;background:#fafafa;padding:1rem;border-radius:3px}
.faq{font-size:.8rem;color:#666}
.status-unresponded{color:#f57c00;font-weight:700}
.status-responded{color:#2e7d32;font-weight:700}
.actions{display:flex;gap:.5rem;margin-top:1rem;align-items:flex-start}
.actions textarea{flex:1;min-height:60px;padding:.5rem;border:1px solid #ddd;border-radius:3px;font-size:.9rem}
.actions button{padding:.6rem 1.2rem;border:none;border-radius:3px;cursor:pointer;font-weight:600}
.respond{background:#C8102E;color:#fff}
.unrespond{background:#888;color:#fff}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.empty{text-align:center;padding:3rem;color:#888}
</style></head><body>
<header><h1>Chatbot Questions (<?= count($questions) ?>)</h1><a class="logout" href="dashboard.php">← Dashboard</a></header>

<div class="filters">
  <a href="chatbot.php" class="<?= $filter==='all'?'active':'' ?>">All</a>
  <a href="chatbot.php?filter=unresponded" class="<?= $filter==='unresponded'?'active':'' ?>">Unresponded</a>
  <a href="chatbot.php?filter=responded" class="<?= $filter==='responded'?'active':'' ?>">Responded</a>
  <a href="chatbot.php?filter=fallback" class="<?= $filter==='fallback'?'active':'' ?>">Fallbacks only</a>
</div>

<?php if (empty($questions)): ?>
<div class="empty">No chatbot questions found.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($questions as $q): ?>
  <div class="card">
    <h3><?= htmlspecialchars($q['email'] ?: 'Anonymous') ?></h3>
    <div class="meta">
      #<?= (int)$q['id'] ?> |
      <?= $q['was_fallback'] ? '<strong>Fallback</strong>' : 'Matched FAQ' ?> |
      Status: <?= $q['responded'] ? '<span class="status-responded">Responded</span> ('.date('M j, Y g:i A', strtotime($q['responded_at'])).')' : '<span class="status-unresponded">Unresponded</span>' ?> |
      <?= date('M j, Y g:i A', strtotime($q['created_at'])) ?>
    </div>
    <?php if ($q['matched_faq']): ?><div class="faq">Matched FAQ: <?= htmlspecialchars($q['matched_faq']) ?></div><?php endif; ?>
    <div class="q"><?= nl2br(htmlspecialchars($q['question'])) ?></div>
    <?php if ($q['response_notes']): ?><div style="font-size:.9rem;color:#555;margin-bottom:.5rem"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($q['response_notes'])) ?></div><?php endif; ?>
    <form method="POST" class="actions">
      <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
      <input type="hidden" name="X-CSRF-Token" value="<?= htmlspecialchars($csrf) ?>">
      <?php if (!$q['responded']): ?>
        <input type="hidden" name="action" value="respond">
        <textarea name="notes" placeholder="Add response notes (optional)..."></textarea>
        <button type="submit" class="respond">Mark Responded</button>
      <?php else: ?>
        <input type="hidden" name="action" value="unrespond">
        <button type="submit" class="unrespond">Mark Unresponded</button>
      <?php endif; ?>
    </form>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<script>
document.querySelectorAll('form.actions').forEach(f => {
  f.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(f);
    const csrf = fd.get('X-CSRF-Token');
    fd.delete('X-CSRF-Token');
    const r = await fetch('chatbot.php', { method: 'POST', headers: { 'X-CSRF-Token': csrf }, body: fd });
    if (r.ok || r.redirected) location.reload();
    else alert('Action failed: HTTP ' + r.status);
  });
});
</script>
</body></html>
