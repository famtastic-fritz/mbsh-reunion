<?php
declare(strict_types=1);
require __DIR__.'/../../backend/lib/portal-preferences.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE attendee_preferences(attendee_id INTEGER,event_updates INTEGER,memory_updates INTEGER,promotional_email INTEGER,sms_notifications INTEGER,updated_at TEXT)');
$db->exec("INSERT INTO attendee_preferences VALUES(1,1,1,0,0,'2000-01-01 00:00:00'),(2,1,1,1,0,'2000-01-01 00:00:00')");
fam_save_attendee_preferences($db,1,true,true,false,false);
$r=$db->query('SELECT * FROM attendee_preferences WHERE attendee_id=1')->fetch(PDO::FETCH_ASSOC);
if($r['updated_at']==='2000-01-01 00:00:00'||(int)$r['promotional_email']!==0)throw new RuntimeException('Explicit false preference not recorded');
if($db->query('SELECT updated_at FROM attendee_preferences WHERE attendee_id=2')->fetchColumn()!=='2000-01-01 00:00:00')throw new RuntimeException('Cross-account preference modification');
echo "PASS explicit unchanged off preference records save time; other account preserved\n";
