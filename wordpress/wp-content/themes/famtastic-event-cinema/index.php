<?php
/** Headless-safe branded fallback. */
declare(strict_types=1);
$frontend = defined('FAMTASTIC_FRONTEND_URL') ? FAMTASTIC_FRONTEND_URL : '/';
$isWooUtilityPage = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
$isWooCatalogPage = function_exists('is_woocommerce') && is_woocommerce();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,noarchive">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">
  <header class="site-header">
    <a class="brand" href="<?php echo esc_url($frontend); ?>">
      <img src="/assets/premiere/brand-mark-foil.png" alt="" width="48" height="48">
      <span><strong>FAMtastic Event Cinema</strong><small>MBSH '96 Committee Studio</small></span>
    </a>
    <a class="button" href="<?php echo esc_url(wp_login_url()); ?>">Committee sign in</a>
  </header>
  <main class="site-main">
<?php if ($isWooUtilityPage && have_posts()): while (have_posts()): the_post(); ?>
    <article class="cinema-card cinema-card--commerce">
      <h1><?php the_title(); ?></h1>
      <div class="entry-content"><?php the_content(); ?></div>
    </article>
<?php endwhile; elseif ($isWooCatalogPage): ?>
    <section class="cinema-card cinema-card--commerce">
      <?php woocommerce_content(); ?>
    </section>
<?php elseif (have_posts()): while (have_posts()): the_post(); ?>
    <article class="cinema-card">
      <h1><?php the_title(); ?></h1>
      <div class="entry-content"><?php the_content(); ?></div>
    </article>
<?php endwhile; else: ?>
    <section class="cinema-card">
      <p class="eyebrow">The editorial studio behind the experience</p>
      <h1>The story lives on the main stage.</h1>
      <p>This private content and commerce system powers the MBSH Class of '96 reunion experience. Public stories, tickets, memories, and event details are presented through the cinematic reunion site.</p>
      <div class="actions">
        <a class="button button--primary" href="<?php echo esc_url($frontend); ?>">Enter the reunion experience</a>
        <a class="button" href="<?php echo esc_url(wp_login_url()); ?>">Open committee studio</a>
      </div>
    </section>
<?php endif; ?>
  </main>
  <footer class="site-footer">A FAMtastic Designs Event Cinema experience.</footer>
</div>
<?php wp_footer(); ?>
<!-- famtastic-creator-credit:start -->
<style>@media(max-width:600px){body:has(#chatbot) [data-famtastic-creator-credit="v1"]{padding-bottom:160px!important}}</style>
<div data-famtastic-creator-credit="v1" style="box-sizing:border-box;clear:both;position:relative;width:100%;padding:16px 16px 24px;text-align:center;background:#080a08;color:#f5f5ee;grid-column:1 / -1">
  <p style="margin:0 0 6px;font:11px/1.5 Arial,sans-serif;color:#f5f5ee">Created by FAMtasticDesigns.com</p>
  <a href="https://famtasticdesigns.com/?utm_source=mbsh96reunion&amp;utm_medium=creator_credit&amp;utm_campaign=created_by_famtastic" aria-label="Created by FAMtastic Designs — visit our website" style="display:inline-flex;align-items:center;justify-content:center;min-height:44px;max-width:100%;border-radius:6px">
    <img src="/assets/famtastic/famtastic-designs-logo-v1.png" alt="FAMtastic Designs" width="2172" height="724" loading="lazy" style="display:block;width:160px;max-width:100%;height:auto;border:0">
  </a>
</div>
<!-- famtastic-creator-credit:end -->
</body></html>
