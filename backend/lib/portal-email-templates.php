<?php
declare(strict_types=1);

require_once __DIR__ . '/creator-credit.php';

/**
 * Act I branded email templates for the MBSH Class of 1996 reunion.
 * Images are served from the public site so email clients receive stable URLs.
 */

function fam_reunion_email_layout(array $options): string
{
    $title = htmlspecialchars((string)$options['title'], ENT_QUOTES, 'UTF-8');
    $preheader = htmlspecialchars((string)($options['preheader'] ?? ''), ENT_QUOTES, 'UTF-8');
    $eyebrow = htmlspecialchars((string)($options['eyebrow'] ?? 'CALLING THE HI-TIDES HOME'), ENT_QUOTES, 'UTF-8');
    $body = (string)$options['body'];
    $harry = htmlspecialchars((string)($options['harry'] ?? 'act-1/trailer-curtain-host.png'), ENT_QUOTES, 'UTF-8');
    $draft = !empty($options['draft']);

    return fam_creator_credit_email('<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . $title . '</title></head><body style="margin:0;background:#090909;color:#f8f1e3;font-family:Arial,Helvetica,sans-serif">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . $preheader . '</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#090909"><tr><td align="center" style="padding:24px 12px">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#151112;border:1px solid #7b5a22;border-radius:18px;overflow:hidden">'
        . ($draft ? '<tr><td style="padding:10px 20px;background:#f3c76d;color:#19110a;text-align:center;font-size:12px;font-weight:800;letter-spacing:.12em">DRAFT PREVIEW — NOT SENT TO COMMITTEE</td></tr>' : '')
        . '<tr><td style="padding:28px 30px 8px;text-align:center;background:linear-gradient(135deg,#24090e,#090909)">'
        . '<img src="https://mbsh96reunion.com/assets/brand-mark/brand-mark.png" width="132" alt="MBSH Class of 1996 reunion medallion honoring 30 reunion years and 100 school years" style="display:block;margin:0 auto 16px;max-width:132px;height:auto">'
        . '<div style="color:#e5bd67;font-size:12px;font-weight:800;letter-spacing:.18em">' . $eyebrow . '</div>'
        . '<h1 style="margin:10px 0 8px;color:#fff8e9;font-family:Georgia,Times,serif;font-size:34px;line-height:1.1">' . $title . '</h1>'
        . '<p style="margin:0;color:#cdbfae;font-size:15px">Miami Beach Senior High · Class of 1996</p></td></tr>'
        . '<tr><td style="padding:24px 30px 0"><div style="font-size:16px;line-height:1.65;color:#f4eadb">' . $body . '</div></td></tr>'
        . '<tr><td align="center" style="padding:8px 30px 0"><img src="https://mbsh96reunion.com/assets/mascot/' . $harry . '" width="320" alt="Hi-Tide Harry" style="display:block;width:320px;max-width:100%;height:auto;border-radius:14px"></td></tr>'
        . '<tr><td style="padding:22px 30px;background:#0c0b0b;border-top:1px solid #4a3517;text-align:center">'
        . '<p style="margin:0 0 8px;color:#e5bd67;font-family:Georgia,Times,serif;font-size:18px">30 years later. 100 years strong.</p>'
        . '<p style="margin:0 0 14px;color:#998f82;font-size:12px;line-height:1.5">Saturday, November 7, 2026 · Twisted Tonic · 7–10 PM · Food on your own<br>Questions? Reply to reach the reunion committee.</p>'
        . '<table role="presentation" width="100%" style="border-top:1px solid #4a3517"><tr><td style="padding:16px 0 0;text-align:left"><strong style="color:#fff8e9">Your business deserves its premiere.</strong><br><span style="color:#cdbfae;font-size:13px">Launch a FAMtastic Designs Starter Website for $199.</span></td><td style="padding:16px 0 0;text-align:right"><a href="https://famtasticdesigns.com" style="display:inline-block;padding:10px 14px;border:1px solid #e5bd67;border-radius:7px;color:#e5bd67;text-decoration:none;font-size:12px;font-weight:800">START AT $199</a></td></tr></table>'
        . '<p style="margin:13px 0 0;color:#746d65;font-size:10px">Reunion experience created by <a href="https://famtasticdesigns.com" style="color:#a98b50">FAMtasticDesigns.com</a>.</p>'
        . '</td></tr></table></td></tr></table></body></html>');
}

function fam_committee_member_email(string $firstName, bool $draft = false): array
{
    $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $body = '<p style="margin-top:0">Hi ' . $name . ',</p>'
        . '<p>Welcome backstage. You have been invited to serve as a <strong style="color:#f3c76d">Committee Member</strong> for the Class of 1996 reunion.</p>'
        . '<p>Your Committee Desk helps you:</p><ul style="padding-left:20px">'
        . '<li>review the classmate roster and RSVP details;</li><li>view historical meal records and current access notes;</li>'
        . '<li>respond to attendee questions and Hi-Tide Harry escalations;</li><li>review submitted memories and suggestions before anything is published.</li></ul>'
        . '<p>Your access is permission-controlled. Financial administration, staff permissions, site structure, brand controls, and owner audit tools remain restricted to the Site Owner.</p>'
        . '<p style="margin:26px 0"><a href="https://mbsh96reunion.com/portal/auth/login.html" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:14px 20px;border-radius:8px">Open My Reunion &amp; Committee Desk</a></p>'
        . '<p>Sign in with the email address that received your invitation. After login, choose <strong>Committee Desk</strong>. If you have not activated your account yet, use registration with that same email address.</p>'
        . '<p>Thank you for helping us call the Hi-Tides home.</p>';
    return [
        'subject' => ($draft ? '[DRAFT] ' : '') . 'Welcome backstage — your Committee Desk',
        'html' => fam_reunion_email_layout(['title'=>'Welcome to the Committee Desk','preheader'=>'Your committee-member access, responsibilities, and first step.','eyebrow'=>'ACT I · CALLING THE HI-TIDES HOME','body'=>$body,'harry'=>'act-1/trailer-curtain-host.png','draft'=>$draft]),
    ];
}

function fam_committee_lead_email(string $firstName, bool $draft = false): array
{
    $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $body = '<p style="margin-top:0">Hi ' . $name . ',</p>'
        . '<p>You have been invited to serve as a <strong style="color:#f3c76d">Committee Lead</strong> for the Class of 1996 reunion. This role includes Committee Member access plus greater responsibility for keeping reunion operations moving.</p>'
        . '<p>As a Committee Lead, you can:</p><ul style="padding-left:20px">'
        . '<li>review the roster, current RSVP and accessibility notes, and historical meal records;</li><li>help correct current attendance and contact information;</li>'
        . '<li>manage attendee conversations, memory review, suggestions, and Harry escalations;</li>'
        . '<li>prepare official communications and monitor delivery;</li><li>support ticket operations and event-night check-in;</li>'
        . '<li>help manage event content assigned to the committee.</li></ul>'
        . '<p><strong>Owner boundary:</strong> Fritz remains the Site Owner. The owner account supersedes every committee role and exclusively controls staff access, full-site administration, site structure, brand authority, owner audit records, and exceptional manual ticket issuance. A Committee Lead cannot reduce, replace, or assume Site Owner authority.</p>'
        . '<p style="margin:26px 0"><a href="https://mbsh96reunion.com/portal/auth/login.html" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:14px 20px;border-radius:8px">Open My Reunion &amp; Lead Desk</a></p>'
        . '<p>Sign in with the email address that received your invitation, then choose <strong>Committee Desk</strong>. Use attendee information only for reunion responsibilities, preserve private dietary and contact details, and record decisions through the portal so the committee has one accountable history.</p>'
        . '<p>Thank you for helping lead the production.</p>';
    return [
        'subject' => ($draft ? '[DRAFT] ' : '') . 'Your Committee Lead access and responsibilities',
        'html' => fam_reunion_email_layout(['title'=>'Your Committee Lead Briefing','preheader'=>'Your expanded portal privileges, responsibilities, and owner boundary.','eyebrow'=>'ACT I · CALLING THE HI-TIDES HOME','body'=>$body,'harry'=>'act-1/trailer-curtain-host.png','draft'=>$draft]),
    ];
}

function fam_attendee_welcome_email(string $firstName, bool $draft = false): array
{
    $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $body = '<p style="margin-top:0">Hi ' . $name . ',</p><p><strong style="color:#f3c76d">Your private reunion account is ready. Admission is confirmed only after ticket payment.</strong> Saturday, November 7 at Twisted Tonic · 7–10 PM · Food on your own is going to be an exciting night for the Class of 1996.</p>'
        . '<p><strong>Here is what to do next:</strong></p><ul style="padding-left:20px">'
        . '<li><strong>Claim or open your private reunion account</strong> using this same email address, to retrieve your paid ticket. No separate RSVP is needed.</li>'
        . '<li><strong>Plan your evening.</strong> The Reunion Mixer is at Twisted Tonic on November 7, 7–10 PM. Food is on your own; no meal selection is needed.</li>'
        . '<li><strong>Secure your ticket.</strong> Mixer admission is <strong style="color:#f3c76d">$30 per person</strong>, with no additional checkout fee.</li>'
        . '<li><strong>Call another Hi-Tide home.</strong> Reach out personally and share <a href="https://mbsh96reunion.com" style="color:#f3c76d">mbsh96reunion.com</a> with classmates who may not know the reunion is coming.</li>'
        . '<li><strong>Bring the story with you.</strong> Submit a favorite photo or memory for private committee review—nothing publishes automatically.</li></ul>'
        . '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:28px auto"><tr><td><a href="https://mbsh96reunion.com/tickets.html" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:15px 22px;border-radius:8px">GET MY $30 MIXER TICKET</a></td></tr></table>'
        . '<p style="text-align:center"><a href="https://mbsh96reunion.com/portal/register" style="color:#f3c76d;font-weight:800">Claim my private reunion account</a> &nbsp;·&nbsp; <a href="https://mbsh96reunion.com/index.html#venue" style="color:#f3c76d;font-weight:800">Food & location</a> &nbsp;·&nbsp; <a href="https://mbsh96reunion.com/through-years.html#submit-memory" style="color:#f3c76d;font-weight:800">Share a memory</a></p>'
        . '<p>Act I has begun. Help us call the Hi-Tides home.</p>'
        . '<p style="color:#998f82;font-size:12px">You can manage optional reunion stories and promotional email in your private profile without affecting essential account, payment, ticket, or event-service messages. You may also reply “unsubscribe” for committee assistance.</p>';
    return [
        'subject' => ($draft ? '[DRAFT] ' : '') . 'Welcome home, Hi-Tide — your reunion portal',
        'html' => fam_reunion_email_layout(['title'=>'Welcome Home, Hi-Tide','preheader'=>'Thank you for registering. Your private reunion account is ready.','eyebrow'=>'ACT I · CALLING THE HI-TIDES HOME','body'=>$body,'harry'=>'act-1/registration-welcome.png','draft'=>$draft]),
    ];
}

/** Render branded ticket-service content without sending or changing order state. */
function fam_ticket_transaction_email(string $state, array $order, bool $draft = false): array
{
    $allowed = ['order_received', 'payment_confirmed', 'ticket_issued', 'payment_failed', 'order_cancelled', 'refunded'];
    if (!in_array($state, $allowed, true)) throw new InvalidArgumentException('Unsupported ticket email state.');
    $name = htmlspecialchars((string)($order['first_name'] ?? 'Hi-Tide'), ENT_QUOTES, 'UTF-8');
    $number = htmlspecialchars((string)($order['order_number'] ?? 'Pending'), ENT_QUOTES, 'UTF-8');
    $quantity = max(1, (int)($order['quantity'] ?? 1));
    $total = htmlspecialchars((string)($order['total'] ?? 'Not yet charged'), ENT_QUOTES, 'UTF-8');
    $paymentUrl = filter_var((string)($order['payment_url'] ?? ''), FILTER_VALIDATE_URL) ? htmlspecialchars((string)$order['payment_url'], ENT_QUOTES, 'UTF-8') : '';
    $paymentReference = htmlspecialchars((string)($order['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8');
    $details = '<table role="presentation" width="100%" style="margin:20px 0;border-collapse:collapse"><tr><td style="padding:9px;border-bottom:1px solid #4a3517;color:#cdbfae">Order</td><td style="padding:9px;border-bottom:1px solid #4a3517;text-align:right"><strong>' . $number . '</strong></td></tr><tr><td style="padding:9px;border-bottom:1px solid #4a3517;color:#cdbfae">Admissions</td><td style="padding:9px;border-bottom:1px solid #4a3517;text-align:right"><strong>' . $quantity . '</strong></td></tr><tr><td style="padding:9px;color:#cdbfae">Total</td><td style="padding:9px;text-align:right"><strong>' . $total . '</strong></td></tr>' . ($paymentReference !== '' ? '<tr><td style="padding:9px;border-top:1px solid #4a3517;color:#cdbfae">Stripe reference</td><td style="padding:9px;border-top:1px solid #4a3517;text-align:right"><strong>' . $paymentReference . '</strong></td></tr>' : '') . '</table>';
    $wallet = '<p style="margin:26px 0"><a href="https://mbsh96reunion.com/portal/#ticket" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:14px 20px;border-radius:8px">Open My Reunion Wallet</a></p>';
    $paymentCta = $paymentUrl !== '' ? '<p style="margin:26px 0;text-align:center"><a href="' . $paymentUrl . '" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:15px 22px;border-radius:8px">PAY SECURELY WITH STRIPE</a></p><p style="text-align:center;color:#cdbfae;font-size:13px">Use the same email address as your reunion request. Stripe sends its own card receipt. Your reunion ticket is issued only after the payment is verified.</p>' : '';
    $copy = [
        'order_received' => ['We received your ticket request', 'Your request is recorded; payment has not been confirmed.', 'act-1/ticket-premiere.png', '<p>Hi ' . $name . ',</p><p>We received your reunion ticket request. <strong>This is not a paid ticket or admission confirmation.</strong></p>' . $details . $paymentCta . '<p>After payment is verified, your personalized digital ticket will appear under <strong>My Reunion → My Ticket</strong>. The live check-in credential stays protected inside your account.</p>'],
        'payment_confirmed' => ['Payment confirmed — welcome home', 'Your reunion payment is confirmed.', 'act-1/ticket-premiere.png', '<p>Hi ' . $name . ',</p><p>Payment for your Class of 1996 reunion order has been verified against Stripe.</p>' . $details . '<p>Keep this branded confirmation with the separate Stripe receipt for your records. If you have not activated your private reunion account with this same email address, do that now so the admission can enter your wallet.</p><p style="margin:26px 0"><a href="https://mbsh96reunion.com/portal/register" style="display:inline-block;background:#c8102e;color:#fff;text-decoration:none;font-weight:800;padding:14px 20px;border-radius:8px">ACTIVATE MY REUNION ACCOUNT</a></p>' . $wallet],
        'ticket_issued' => ['Your reunion ticket is ready', 'Your private admission ticket is now in your reunion wallet.', 'act-1/ticket-premiere.png', '<p>Hi ' . $name . ',</p><p>Your reunion admission ticket is ready in your private wallet.</p>' . $details . $wallet . '<p><strong>For your protection, this email does not contain a check-in credential.</strong> Open the live pass from your account when you arrive.</p>'],
        'payment_failed' => ['Your reunion payment needs attention', 'No admission ticket was issued because payment was not completed.', '12-clipboard.png', '<p>Hi ' . $name . ',</p><p>Your payment was not completed, so no admission ticket was issued. Please return to the official reunion site and try again, or reply for committee help.</p>' . $details],
        'order_cancelled' => ['Your reunion ticket order was cancelled', 'The cancelled order does not provide admission.', '06-listening.png', '<p>Hi ' . $name . ',</p><p>Your reunion ticket order was cancelled. It does not provide admission. If this was unexpected, reply so the committee can review the order history.</p>' . $details],
        'refunded' => ['Your reunion ticket refund was recorded', 'Refunded admissions are no longer valid for entry.', '06-listening.png', '<p>Hi ' . $name . ',</p><p>Your refund was recorded. Any admission tickets connected to the refunded quantity are revoked and cannot be used for check-in.</p>' . $details . '<p>If the quantity or amount shown is not what you expected, reply to this message.</p>'],
    ][$state];
    return ['subject' => ($draft ? '[DRAFT] ' : '') . $copy[0], 'html' => fam_reunion_email_layout(['title'=>$copy[0], 'preheader'=>$copy[1], 'eyebrow'=>'ACT I · YOUR REUNION TICKET', 'body'=>$copy[3], 'harry'=>$copy[2], 'draft'=>$draft])];
}
