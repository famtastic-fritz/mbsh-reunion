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
  $action = $_POST['action'] ?? '';
  if ($action === 'add') {
    $stmt = $pdo->prepare('INSERT INTO in_memory (full_name, graduation_year, year_passed, tribute, display_order) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
      trim((string)($_POST['full_name'] ?? '')),
      (int)($_POST['graduation_year'] ?? 1996),
      ($_POST['year_passed'] ?? '') !== '' ? (int)$_POST['year_passed'] : null,
      trim((string)($_POST['tribute'] ?? '')) ?: null,
      (int)($_POST['display_order'] ?? 0),
    ]);
    fam_admin_audit($pdo, 'in_memory_add', 'in_memory', (int)$pdo->lastInsertId());
  } elseif ($action === 'deactivate') {
    $stmt = $pdo->prepare('UPDATE in_memory SET active=0 WHERE id=?');
    $stmt->execute([(int)$_POST['id']]);
    fam_admin_audit($pdo, 'in_memory_deactivate', 'in_memory', (int)$_POST['id']);
  }
  header('Location: manage-in-memory.php');
  exit;
}

$rows = $pdo->query('SELECT * FROM in_memory ORDER BY active DESC, display_order, full_name')->fetchAll();
$csrf = fam_csrf_issue(session_id() ?: 'cli', $config['admin_csrf_secret']);
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Manage In Memory</title>
<style>body{font-family:Inter,sans-serif;background:#F8F4EC;padding:2rem}.row{background:#fff;padding:1rem;margin-bottom:.5rem;border-radius:4px;display:flex;justify-content:space-between;align-items:center}.row.inactive{opacity:.5}form.add{background:#fff;padding:1.5rem;margin-bottom:2rem;border-radius:4px}input,textarea{width:100%;padding:.5rem;margin-bottom:.5rem;box-sizing:border-box}button{padding:.5rem 1rem;background:#C8102E;color:#fff;border:none;border-radius:3px;cursor:pointer}</style></head><body>
<h1>In Memory — manage</h1><p><a href="dashboard.php">← Dashboard</a></p>
<form method="POST" class="add" data-csrf="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="action" value="add">
  <h3>Add a name</h3>
  <input name="full_name" placeholder="Full name" required>
  <input name="graduation_year" type="number" value="1996">
  <input name="year_passed" type="number" placeholder="Year passed (optional)">
  <textarea name="tribute" placeholder="Tribute (optional, max 200 chars)" maxlength="200"></textarea>
  <input name="display_order" type="number" value="0" placeholder="Display order">
  <button type="submit">Add</button>
</form>
<h3>Current entries</h3>
<?php foreach ($rows as $r): ?>
  <div class="row <?= $r['active'] ? '' : 'inactive' ?>">
    <span><strong><?= htmlspecialchars($r['full_name']) ?></strong> · <?= htmlspecialchars((string)$r['graduation_year']) ?><?= $r['year_passed'] ? ' – ' . htmlspecialchars((string)$r['year_passed']) : '' ?></span>
    <?php if ($r['active']): ?><form method="POST" data-csrf="<?= htmlspecialchars($csrf) ?>" style="display:inline"><input type="hidden" name="action" value="deactivate"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button type="submit">Deactivate</button></form><?php endif; ?>
  </div>
<?php endforeach; ?>
<script>document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',async e=>{e.preventDefault();const r=await fetch('manage-in-memory.php',{method:'POST',headers:{'X-CSRF-Token':f.dataset.csrf},body:new FormData(f)});if(r.ok||r.redirected)location.reload();else alert('failed');}));</script>
</body></html>
