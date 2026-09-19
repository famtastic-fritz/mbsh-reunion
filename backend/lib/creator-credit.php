<?php
declare(strict_types=1);

/** Add the owner's final-row credit without changing message content or transport. */
function fam_creator_credit_email(string $html): string {
  if (str_contains($html, 'data-famtastic-email-credit="v1"')) return $html;
  $credit = '<div data-famtastic-email-credit="v1" style="padding:24px 16px;text-align:center;background:#080a08;color:#f5f5ee;font-family:Arial,sans-serif">'
    . '<p style="margin:0 0 10px;font-size:12px">Created by FAMtasticDesigns.com</p>'
    . '<a href="https://famtasticdesigns.com/?utm_source=mbsh96reunion-email&amp;utm_medium=creator_credit&amp;utm_campaign=created_by_famtastic" aria-label="Visit FAMtastic Designs" style="display:inline-block;min-height:44px">'
    . '<img src="https://mbsh96reunion.com/assets/famtastic/famtastic-designs-logo-v1.png" alt="FAMtastic Designs" width="190" style="display:block;width:190px;max-width:100%;height:auto;border:0"></a></div>';
  $position = strripos($html, '</body>');
  return $position === false ? $html . $credit : substr($html, 0, $position) . $credit . substr($html, $position);
}
