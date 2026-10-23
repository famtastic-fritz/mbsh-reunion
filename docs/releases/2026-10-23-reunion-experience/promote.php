<?php
declare(strict_types=1);
// Scoped, CLI-only release operator. Run from the private immutable release package.
if (PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', ['check', 'apply'], true)) exit(64);
umask(0077);
$release = __DIR__;
$web = '/home/nineoo/public_html';
$campaign = '/home/nineoo/.releases/mbsh-reunion/campaigns/unlock-the-night-20261023';
$bankPath = '/home/nineoo/data/trivia/1996-starter.json';
$manifest = json_decode(file_get_contents($release.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$baseline = json_decode(file_get_contents($release.'/production-baseline.json'), true, 512, JSON_THROW_ON_ERROR);
$actualHash = static fn(string $p): ?string => is_file($p) ? hash_file('sha256', $p) : null;
$checkPins = static function() use ($baseline, $campaign, $web, $actualHash): void {
    foreach ($baseline['campaign_pins'] as $path => $hash) {
        if ($actualHash($campaign.'/'.$path) !== $hash) throw new RuntimeException('Campaign pin mismatch: '.$path);
    }
    foreach (['lib/commerce-wallet.php','lib/portal-auth.php','portal/preferences.php','lib/portal-preferences.php','lib/portal-email-templates.php','lib/creator-credit.php'] as $path) {
        if ($actualHash($web.'/'.$path) !== $baseline['files'][$path]) throw new RuntimeException('Protected runtime mismatch: '.$path);
    }
};
try {
    $checkPins();
    $cronHash = hash('sha256', (string)shell_exec('crontab -l 2>/dev/null'));
    foreach ($manifest['files'] as $entry) {
        $path = $entry['path'];
        if (!preg_match('~^[a-zA-Z0-9_./-]+$~', $path) || str_contains($path, '..') || str_starts_with($path, '/')) throw new RuntimeException('Invalid release path');
        if ($actualHash($release.'/runtime/'.$path) !== $entry['after']) throw new RuntimeException('Candidate hash mismatch: '.$path);
        if ($actualHash($web.'/'.$path) !== $entry['before']) throw new RuntimeException('Live baseline mismatch: '.$path);
    }
    foreach (['bank.json','migration.sql'] as $file) {
        if ($actualHash($release.'/'.$file) !== $manifest['private_files'][$file]) throw new RuntimeException('Private candidate mismatch: '.$file);
    }
    if (is_file($bankPath) && $actualHash($bankPath) !== $manifest['private_files']['bank.json']) throw new RuntimeException('Existing private bank differs');
    require_once $web.'/lib/config.php';
    require_once $web.'/lib/db.php';
    require_once $web.'/lib/validate.php';
    require_once $web.'/lib/rate-limit.php';
    require_once $web.'/lib/portal-auth.php';
    $config = fam_load_config();
    $pdo = fam_db($config);
    $owners = $pdo->query("SELECT a.id FROM attendee_accounts a JOIN portal_staff_memberships m ON m.attendee_id=a.id WHERE a.status='active' AND a.email_verified_at IS NOT NULL AND m.status='active' AND m.role='site_owner'")->fetchAll(PDO::FETCH_COLUMN);
    if (count($owners) !== 1) throw new RuntimeException('Expected exactly one existing active owner audit identity');
    if ($argv[1] === 'check') {
        echo json_encode(['status'=>'ready','source_commit'=>$manifest['source_commit'],'runtime_files'=>count($manifest['files']),'protected_pins'=>'matched','php'=>PHP_VERSION,'gd'=>extension_loaded('gd')], JSON_PRETTY_PRINT)."\n";
        exit;
    }
    if (is_file($release.'/receipt.json')) throw new RuntimeException('Release already applied');
    $snapshot = fopen($release.'/activity-before.private.jsonl', 'x');
    if (!$snapshot) throw new RuntimeException('Snapshot already exists; inspect partial release before retry');
    foreach (['trivia_games','trivia_questions','trivia_attempts','trivia_answers','attendee_media_submissions','attendee_suggestions'] as $table) {
        fwrite($snapshot, json_encode(['table'=>$table,'ddl'=>$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1]])."\n");
        $rows=$pdo->query('SELECT * FROM `'.$table.'`');
        while ($row=$rows->fetch(PDO::FETCH_ASSOC)) fwrite($snapshot,json_encode(['table'=>$table,'row'=>$row],JSON_THROW_ON_ERROR)."\n");
    }
    fclose($snapshot);
    foreach ($manifest['files'] as $entry) {
        if ($entry['before'] === null) continue;
        $dest=$release.'/before/'.$entry['path'];
        if (!is_dir(dirname($dest))) mkdir(dirname($dest),0700,true);
        if (!copy($web.'/'.$entry['path'],$dest) || $actualHash($dest)!==$entry['before']) throw new RuntimeException('Cannot retain baseline: '.$entry['path']);
    }
    $pdo->exec(file_get_contents($release.'/migration.sql'));
    if (!is_dir(dirname($bankPath))) mkdir(dirname($bankPath),0700,true);
    if (!copy($release.'/bank.json',$bankPath)) throw new RuntimeException('Cannot install private bank');
    chmod($bankPath,0600);
    foreach ($manifest['files'] as $entry) {
        $dest=$web.'/'.$entry['path'];
        if (!is_dir(dirname($dest))) mkdir(dirname($dest),0755,true);
        $tmp=tempnam(dirname($dest),'.experience-release-');
        if (!copy($release.'/runtime/'.$entry['path'],$tmp)) throw new RuntimeException('Stage failed: '.$entry['path']);
        chmod($tmp,0644);
        if (!rename($tmp,$dest) || $actualHash($dest)!==$entry['after']) throw new RuntimeException('Promotion failed: '.$entry['path']);
    }
    require_once $web.'/lib/reunion-trivia-bank.php';
    if ((int)$pdo->query("SELECT GET_LOCK('mbsh_trivia_lifecycle',5)")->fetchColumn()!==1) throw new RuntimeException('Trivia lifecycle busy');
    try {
        $pdo->beginTransaction();
        $seed=fam_import_trivia_starter($pdo,$config,['attendee_id'=>(int)$owners[0],'role'=>'site_owner']);
        fam_portal_staff_audit($pdo,['attendee_id'=>(int)$owners[0],'role'=>'site_owner'],'release_starter_seed','trivia_game',$seed['id'],['release'=>$manifest['release_id'],'source_commit'=>$manifest['source_commit'],'execution'=>'owner-authorized CLI release']);
        $pdo->commit();
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $pdo->query("SELECT RELEASE_LOCK('mbsh_trivia_lifecycle')");
    }
    $q=$pdo->query("SELECT g.status,COUNT(q.id) questions FROM trivia_games g JOIN trivia_questions q ON q.game_id=g.id WHERE g.bank_key='class-of-1996-starter-v1' GROUP BY g.id,g.status");
    $draft=$q->fetch(PDO::FETCH_ASSOC);
    if (($draft['status']??'')!=='draft' || (int)($draft['questions']??0)!==30) throw new RuntimeException('Starter draft verification failed');
    $checkPins();
    if (hash('sha256',(string)shell_exec('crontab -l 2>/dev/null'))!==$cronHash) throw new RuntimeException('Cron changed during release');
    $state=json_decode(file_get_contents($campaign.'/state.private.json'),true);
    $receipt=['release_id'=>$manifest['release_id'],'source_commit'=>$manifest['source_commit'],'deployed_at'=>gmdate('c'),'classification'=>'deployed','owner_acceptance'=>'pending','files'=>$manifest['files'],'trivia_starter'=>$draft,'private_bank_sha256'=>$actualHash($bankPath),'campaign_pins'=>'unchanged','protected_runtime'=>'unchanged','cron_sha256'=>$cronHash,'campaign_heartbeat_at'=>$state['heartbeat_at']??null,'base_deployed_commit_preserved'=>trim(file_get_contents($web.'/DEPLOYED_COMMIT'))];
    file_put_contents($release.'/receipt.json',json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
    echo json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
} catch (Throwable $e) {
    fwrite(STDERR,'Release stopped: '.$e->getMessage()."\n");
    exit(1);
}
