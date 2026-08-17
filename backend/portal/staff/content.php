<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);$staff=fam_require_portal_staff($pdo,'manage_event_content');
$types=['reunion_event','reunion_component','reunion_faq','reunion_announcement','reunion_sponsor','reunion_memory','reunion_tribute'];
$marks=implode(',',array_fill(0,count($types),'?'));
$q=$pdo->prepare("SELECT ID id,post_type type,post_title title,post_status status,post_modified updated_at FROM wp_posts WHERE post_type IN ($marks) AND post_status NOT IN ('auto-draft','trash','inherit') ORDER BY post_type,menu_order,post_modified DESC");
$q->execute($types);fam_json_response(200,['items'=>$q->fetchAll(),'authority'=>'WordPress structured content']);
