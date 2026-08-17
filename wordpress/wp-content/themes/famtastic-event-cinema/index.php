<?php
/** Headless-safe branded fallback. */
declare(strict_types=1);
$frontend = defined('FAMTASTIC_FRONTEND_URL') ? FAMTASTIC_FRONTEND_URL : '/';
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
    <section class="cinema-card">
      <p class="eyebrow">The editorial studio behind the experience</p>
      <h1>The story lives on the main stage.</h1>
      <p>This private content and commerce system powers the MBSH Class of '96 reunion experience. Public stories, tickets, memories, and event details are presented through the cinematic reunion site.</p>
      <div class="actions">
        <a class="button button--primary" href="<?php echo esc_url($frontend); ?>">Enter the reunion experience</a>
        <a class="button" href="<?php echo esc_url(wp_login_url()); ?>">Open committee studio</a>
      </div>
    </section>
  </main>
  <footer class="site-footer">A FAMtastic Designs Event Cinema experience.</footer>
</div>
<?php wp_footer(); ?>
</body></html>
