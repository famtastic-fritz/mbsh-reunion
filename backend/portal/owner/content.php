<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);$staff=fam_require_portal_staff($pdo,'manage_site_structure');
$q=$pdo->query("SELECT p.ID id,p.post_title title,p.post_status status,p.menu_order sort_order,p.post_modified updated_at,MAX(CASE WHEN m.meta_key='_famtastic_component_type' THEN m.meta_value END) component_type,MAX(CASE WHEN m.meta_key='_famtastic_visibility' THEN m.meta_value END) visibility,MAX(CASE WHEN m.meta_key='_famtastic_cta_label' THEN m.meta_value END) cta_label,MAX(CASE WHEN m.meta_key='_famtastic_cta_url' THEN m.meta_value END) cta_url FROM wp_posts p LEFT JOIN wp_postmeta m ON m.post_id=p.ID WHERE p.post_type='reunion_component' AND p.post_status NOT IN ('auto-draft','trash','inherit') GROUP BY p.ID ORDER BY p.menu_order,p.post_title");
fam_json_response(200,['components'=>$q->fetchAll(),'authority'=>'WordPress Page Components','edit_url'=>'http://localhost:8096/wp-admin/edit.php?post_type=reunion_component']);
