<?php
declare(strict_types=1);
function fam_mixer_offer(): array {
    $path = __DIR__ . '/../config/site-config.json';
    $offer = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
    if (($offer['TICKET_OFFER_ID'] ?? '') !== 'mbsh-mixer-2026-11-07-v1' || ($offer['TICKET_CURRENCY'] ?? '') !== 'USD' || (float) ($offer['TICKET_PRICE'] ?? 0) !== 30.0) {
        throw new RuntimeException('Mixer offer is unavailable.');
    }
    return $offer;
}
function fam_mixer_checkout_payload(string $code, string $email, int $quantity, int $expires): string {
    $offer = fam_mixer_offer();
    return implode('|', [$code, $email, $quantity, $expires, $offer['TICKET_OFFER_ID']]);
}
