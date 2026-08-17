<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'send_communications');
$delivery=[];
foreach(['queued'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE status IN ('pending','processing')",'sent'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE status='sent'",'failed'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE status='dead'",'retried'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE attempts>1"] as $key=>$sql) $delivery[$key]=(int)$pdo->query($sql)->fetchColumn();
$delivery['last_worker_at']=$pdo->query("SELECT MAX(COALESCE(sent_at,created_at)) FROM portal_email_jobs WHERE status IN ('sent','dead')")->fetchColumn()?:null;
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'delivery'=>$delivery]);
