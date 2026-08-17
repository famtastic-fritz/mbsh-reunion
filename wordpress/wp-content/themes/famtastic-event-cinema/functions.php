<?php
/** FAMtastic Event Cinema theme bootstrap. */
declare(strict_types=1);

add_action('after_setup_theme', static function (): void {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('responsive-embeds');
  add_theme_support('html5', ['style', 'script', 'search-form', 'gallery', 'caption']);
});

add_action('wp_enqueue_scripts', static function (): void {
  wp_enqueue_style('famtastic-event-cinema', get_stylesheet_uri(), [], '1.0.0');
});

add_filter('wp_generator', '__return_empty_string');
add_filter('show_admin_bar', '__return_false');
remove_action('wp_head', 'wp_generator');
