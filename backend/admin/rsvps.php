<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? 'newest';

$sql = "SELECT * FROM rsvps WHERE 1=1";
$params = [];

if ($filter === 'yes' || $filter === 'maybe' || $filter === 'no') {
  $sql .= " AND attending = ?";
  $params[] = $filter;
}
if ($search !== '') {
  $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR maiden_name LIKE ?)";
  $like = '%' . $search . '%';
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
}

$order = ($sort === 'oldest') ? 'ASC' : 'DESC';
$sql .= " ORDER BY created_at {$order}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rsvps = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>All RSVPs — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
h1{font-family:Georgia,serif;margin:0}
.filters{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem}
.filters a,.filters button{text-decoration:none;padding:.5rem 1rem;border-radius:3px;font-size:.9rem;font-weight:600;border:none;cursor:pointer}
.filters a{background:#fff;color:#0A0A0A;border:1px solid #ddd}
.filters a.active{background:#C8102E;color:#fff;border-color:#C8102E}
.filters input{padding:.5rem .75rem;border:1px solid #ddd;border-radius:3px;font-size:.9rem;min-width:220px}
.filters select{padding:.5rem .75rem;border:1px solid #ddd;border-radius:3px;font-size:.9rem}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
th,td{padding:.85rem 1rem;text-align:left;font-size:.9rem;border-bottom:1px solid #eee}
th{background:#0A0A0A;color:#fff;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}
tr:hover{background:#fafafa}
.attending-yes{color:#2e7d32;font-weight:700}
.attending-maybe{color:#f57c00;font-weight:700}
.attending-no{color:#c62828;font-weight:700}
.small{color:#888;font-size:.8rem}
.actions a{color:#C8102E;text-decoration:none;font-weight:600;font-size:.85rem}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
.count{font-family:'JetBrains Mono',monospace;font-size:1.2rem;color:#C8102E;font-weight:700}
</style></head><body>
<header><h1>All RSVPs</h1><a class="logout" href="dashboard.php">← Dashboard</a></header>

<div class="filters">
  <a href="rsvps.php" class="<?= $filter==='all'?'active':'' ?>">All <span class="count">(<?= count($rsvps) ?>)</span></a>
  <a href="rsvps.php?filter=yes" class="<?= $filter==='yes'?'active':'' ?>">Yes</a>
  <a href="rsvps.php?filter=maybe" class="<?= $filter==='maybe'?'active':'' ?>">Maybe</a>
  <a href="rsvps.php?filter=no" class="<?= $filter==='no'?'active':'' ?>">No</a>
  <form method="GET" style="display:flex;gap:.5rem;margin-left:auto">
    <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
    <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($search) ?>">
    <select name="sort" onchange="this.form.submit()">
      <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest first</option>
      <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest first</option>
    </select>
    <button type="submit">Search</button>
    <?php if ($search || $filter !== 'all'): ?><a href="rsvps.php" style="background:#888;color:#fff">Reset</a><?php endif; ?>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Email</th>
      <th>Attending</th>
      <th>Guests</th>
      <th>City</th>
      <th>Dietary</th>
      <th>Date</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rsvps as $r): ?>
    <tr>
      <td><?= (int)$r['id'] ?></td>
      <td>
        <strong><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></strong>
        <?php if ($r['maiden_name']): ?><br><span class="small">(was <?= htmlspecialchars($r['maiden_name']) ?>)</span><?php endif; ?>
      </td>
      <td><?= htmlspecialchars($r['email']) ?></td>
      <td class="attending-<?= $r['attending'] ?>"><?= ucfirst($r['attending']) ?></td>
      <td><?= (int)$r['guest_count'] ?></td>
      <td><?= htmlspecialchars($r['city_state'] ?: '—') ?></td>
      <td><?= htmlspecialchars($r['dietary'] ?: '—') ?></td>
      <td class="small"><?= date('M j, Y g:i A', strtotime($r['created_at'])) ?></td>
      <td class="actions"><a href="rsvp-edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($rsvps)): ?>
    <tr><td colspan="9" style="text-align:center;padding:2rem;color:#888">No RSVPs found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body></html>
