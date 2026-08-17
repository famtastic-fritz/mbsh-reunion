<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/backend/lib/portal-auth.php';

$failures=[];
function check(bool $condition,string $message): void { global $failures; if(!$condition)$failures[]=$message; }

$uuid=fam_uuid_v4();
check((bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',$uuid),'UUID v4 format');
$token=fam_random_token();
check(strlen($token)>=40,'auth token entropy');
check(strlen(fam_token_hash($token))===64,'token hash is sha256');
check(!fam_password_valid('short1'),'short password rejected');
check(!fam_password_valid('thishasnoDigits'),'password without digit rejected');
check(fam_password_valid('ReunionProof2026!'),'strong password accepted');
$secret=str_repeat('s',32);
$now=1700000000; $credential=fam_ticket_credential($uuid,$secret,$now);
check(str_starts_with($credential,'mbsh96_'),'ticket credential prefix');
check(fam_ticket_credential_valid($credential,$uuid,$secret,$now),'valid ticket credential accepted');
check(fam_ticket_credential_valid($credential,$uuid,$secret,$now+300),'previous ticket window accepted');
check(!fam_ticket_credential_valid($credential,$uuid,$secret,$now+600),'expired ticket credential rejected');
check(!fam_ticket_credential_valid($credential.'x',$uuid,$secret,$now),'tampered ticket credential rejected');
check(!fam_ticket_credential_valid($credential,fam_uuid_v4(),$secret,$now),'credential cannot move between tickets');
check(!str_contains($credential,'='),'URL-safe credential');

$schema=file_get_contents(dirname(__DIR__,2).'/backend/schema.sql') ?: '';
foreach(['attendee_accounts','attendee_auth_tokens','ticket_wallet_items','attendee_media_submissions','attendee_suggestions','attendee_notifications','attendee_record_links','portal_staff_memberships','portal_staff_audit_log'] as $table) check(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table),'schema contains '.$table);
check(in_array('moderate_media',fam_portal_staff_capabilities('committee_member'),true),'committee can moderate media');
check(!in_array('manage_committee',fam_portal_staff_capabilities('committee_member'),true),'committee cannot manage committee access');
check(in_array('manage_committee',fam_portal_staff_capabilities('site_owner'),true),'site owner can manage committee access');
check(fam_portal_staff_client_access(null)['authorized']===false,'ordinary attendee has no staff UI authorization');
check(fam_portal_staff_client_access(['role'=>'committee_lead','capabilities'=>[]])['role']==='committee_admin','committee membership maps to committee admin UI');
check(fam_portal_staff_client_access(['role'=>'site_owner','capabilities'=>[]])['role']==='site_owner','site owner remains distinct in UI');
check(!str_contains(file_get_contents(dirname(__DIR__,2).'/backend/portal/login.php') ?: '','email_not_verified'),'login does not reveal verification state');
$mail = file_get_contents(dirname(__DIR__,2).'/backend/lib/portal-email.php') ?: '';
check(str_contains($mail, "'/verify?token='"), 'verification email opens branded clean frontend route');
check(str_contains($mail, "'/reset?token='"), 'reset email opens branded clean frontend route');
check(!str_contains($mail, '/portal/verify-email.php?token='), 'verification email does not expose JSON endpoint');
check(!str_contains($mail, '/portal/reset-password.php?token='), 'reset email does not expose JSON endpoint');
foreach(['dashboard.php','review-queue.php','people.php','inbox.php','communications.php','operations.php','action.php'] as $endpoint) check(is_file(dirname(__DIR__,2).'/backend/portal/staff/'.$endpoint),'staff endpoint exists: '.$endpoint);

if($failures){ fwrite(STDERR,"FAIL\n - ".implode("\n - ",$failures)."\n"); exit(1); }
echo "PASS portal security primitives\n";
