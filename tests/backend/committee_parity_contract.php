<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$matrix = $root . '/docs/architecture/COMMITTEE_OPERATIONS_PARITY_MATRIX_2026-08-16.md';
$capabilityFile = $root . '/wordpress/contracts/committee-capabilities.json';

function contract_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

contract_assert(is_file($matrix), 'committee parity matrix is missing');
contract_assert(is_file($capabilityFile), 'committee capability contract is missing');

$document = (string) file_get_contents($matrix);
$capabilities = json_decode((string) file_get_contents($capabilityFile), true, 512, JSON_THROW_ON_ERROR);

foreach ([
    'RSVP', 'dinner', 'Harry', 'messages', 'tickets', 'sponsors', 'capsules',
    'surveys', 'alerts', 'media', 'memories', 'email', 'polls', 'reports',
] as $workflow) {
    contract_assert(stripos($document, $workflow) !== false, "matrix does not cover {$workflow}");
}

foreach ([
    'inbox.reply', 'media.review', 'rsvp.edit', 'menu.edit_sensitive',
    'tickets.check_in', 'outreach.send_transactional',
] as $capability) {
    contract_assert(in_array($capability, $capabilities['roles']['committee'] ?? [], true), "committee capability missing: {$capability}");
}

foreach ([
    'owner.manage_roles', 'owner.manage_integrations', 'owner.view_audit',
    'owner.manage_payments', 'campaign.send',
] as $capability) {
    contract_assert(in_array($capability, $capabilities['roles']['owner'] ?? [], true), "owner capability missing: {$capability}");
}

foreach (['wordpress.plugins', 'wordpress.themes', 'wordpress.settings', 'wordpress.users'] as $forbidden) {
    contract_assert(in_array($forbidden, $capabilities['forbiddenForCommittee'] ?? [], true), "committee forbidden rule missing: {$forbidden}");
}

contract_assert(str_contains($document, '/portal/admin/'), 'clean permission-filtered admin route is not specified');
contract_assert(str_contains($document, 'Email equality alone never grants a role'), 'safe membership rule is not specified');
contract_assert(str_contains($document, 'Direct WordPress') && str_contains($document, 'owner only'), 'WordPress owner-only boundary is missing');

fwrite(STDOUT, "PASS: committee workflow parity and role capability contracts are complete.\n");
