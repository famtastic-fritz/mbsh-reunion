<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
require_once __DIR__ . '/../lib/csrf.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit('Missing id'); }

$stmt = $pdo->prepare('SELECT * FROM rsvps WHERE id = ?');
$stmt->execute([$id]);
$rsvp = $stmt->fetch();
if (!$rsvp) { http_response_code(404); exit('RSVP not found'); }

$err = '';
$ok = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  fam_require_csrf($config['admin_csrf_secret']);
  try {
    $upd = $pdo->prepare('UPDATE rsvps SET
      first_name=?, last_name=?, maiden_name=?, email=?, phone=?,
      city_state=?, attending=?, guest_count=?, guest_names=?,
      dietary=?, help_planning=?, message=?, display_publicly=?
      WHERE id=?');
    $upd->execute([
      $_POST['first_name'] ?? '',
      $_POST['last_name'] ?? '',
      $_POST['maiden_name'] ?: null,
      $_POST['email'] ?? '',
      $_POST['phone'] ?: null,
      $_POST['city_state'] ?: null,
      $_POST['attending'] ?? 'maybe',
      (int)($_POST['guest_count'] ?? 1),
      $_POST['guest_names'] ?: null,
      $_POST['dietary'] ?: null,
      !empty($_POST['help_planning']) ? 1 : 0,
      $_POST['message'] ?: null,
      !empty($_POST['display_publicly']) ? 1 : 0,
      $id,
    ]);
    fam_admin_audit($pdo, 'rsvp_update', 'rsvps', $id);
    $ok = 'RSVP updated.';
    // Refresh
    $stmt->execute([$id]);
    $rsvp = $stmt->fetch();
  } catch (Throwable $e) {
    $err = 'Update failed: ' . $e->getMessage();
  }
}

$csrf = fam_csrf_issue(session_id() ?: 'cli', $config['admin_csrf_secret']);
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit RSVP #<?= $id ?> — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem}
h1{font-family:Georgia,serif;margin:0}
form{background:#fff;padding:2rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);max-width:700px}
.row{display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap}
.row > label{flex:1;min-width:220px}
label{display:block;font-size:.85rem;font-weight:600;margin-bottom:.35rem}
input,select,textarea{width:100%;padding:.6rem;border:1px solid #ddd;border-radius:3px;font-size:.95rem;box-sizing:border-box}
textarea{min-height:80px;resize:vertical}
.actions{display:flex;gap:1rem;margin-top:1.5rem;align-items:center}
button{padding:.75rem 1.5rem;background:#C8102E;color:#fff;border:none;border-radius:3px;font-weight:600;cursor:pointer;font-size:1rem}
button.secondary{background:#888}
.err{color:#c62828;background:#ffebee;padding:.75rem 1rem;border-radius:3px;margin-bottom:1rem}
.ok{color:#2e7d32;background:#e8f5e9;padding:.75rem 1rem;border-radius:3rem;margin-bottom:1rem}
.small{color:#888;font-size:.8rem;margin-top:.25rem}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
</style></head><body>
<header><h1>Edit RSVP #<?= $id ?></h1><a class="logout" href="rsvps.php">← All RSVPs</a></header>

<?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="POST">
  <input type="hidden" name="X-CSRF-Token" value="<?= htmlspecialchars($csrf) ?>">
  <div class="row">
    <label>First Name<input type="text" name="first_name" value="<?= htmlspecialchars($rsvp['first_name']) ?>" required></label>
    <label>Last Name<input type="text" name="last_name" value="<?= htmlspecialchars($rsvp['last_name']) ?>" required></label>
    <label>Maiden Name<input type="text" name="maiden_name" value="<?= htmlspecialchars($rsvp['maiden_name'] ?? '') ?>"></label>
  </div>
  <div class="row">
    <label>Email<input type="email" name="email" value="<?= htmlspecialchars($rsvp['email']) ?>" required></label>
    <label>Phone<input type="text" name="phone" value="<?= htmlspecialchars($rsvp['phone'] ?? '') ?>"></label>
  </div>
  <div class="row">
    <label>City / State<input type="text" name="city_state" value="<?= htmlspecialchars($rsvp['city_state'] ?? '') ?>"></label>
    <label>Attending
      <select name="attending">
        <option value="yes" <?= ($rsvp['attending']==='yes')?'selected':'' ?>>Yes</option>
        <option value="maybe" <?= ($rsvp['attending']==='maybe')?'selected':'' ?>>Maybe</option>
        <option value="no" <?= ($rsvp['attending']==='no')?'selected':'' ?>>No</option>
      </select>
    </label>
    <label>Guest Count<input type="number" name="guest_count" value="<?= (int)$rsvp['guest_count'] ?>" min="1"></label>
  </div>
  <div class="row">
    <label>Guest Names<textarea name="guest_names"><?= htmlspecialchars($rsvp['guest_names'] ?? '') ?></textarea></label>
  </div>
  <div class="row">
    <label>Dietary Restrictions<textarea name="dietary"><?= htmlspecialchars($rsvp['dietary'] ?? '') ?></textarea></label>
  </div>
  <div class="row">
    <label>Message to Committee<textarea name="message"><?= htmlspecialchars($rsvp['message'] ?? '') ?></textarea></label>
  </div>
  <div class="row" style="align-items:center">
    <label style="display:flex;align-items:center;gap:.5rem;font-weight:400">
      <input type="checkbox" name="help_planning" value="1" <?= ($rsvp['help_planning'])?'checked':'' ?>> Wants to help plan
    </label>
    <label style="display:flex;align-items:center;gap:.5rem;font-weight:400">
      <input type="checkbox" name="display_publicly" value="1" <?= ($rsvp['display_publicly'])?'checked':'' ?>> Display publicly on attendee list
    </label>
  </div>
  <div class="actions">
    <button type="submit">Save Changes</button>
    <a href="rsvps.php" class="secondary" style="text-decoration:none;color:#fff;background:#888;padding:.75rem 1.5rem;border-radius:3px;font-weight:600">Cancel</a>
  </div>
  <p class="small">Submitted: <?= date('F j, Y g:i A', strtotime($rsvp['created_at'])) ?> | Last updated: <?= date('F j, Y g:i A', strtotime($rsvp['updated_at'])) ?></p>
</form>
<script>
document.querySelector('form').addEventListener('submit', function(e) {
  const csrf = this.querySelector('[name="X-CSRF-Token"]').value;
  // The form posts normally; CSRF is checked server-side from POST body
});
</script>
</body></html>
