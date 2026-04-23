<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Taxonomies {
  public function register(): void {
    register_taxonomy('gallery', ['artwork'], [
      'labels' => [
        'name' => __('Galleries', 'artopia-gallery'),
        'singular_name' => __('Gallery', 'artopia-gallery'),
      ],
      'public' => true,
      'hierarchical' => true,
      'show_ui' => true,
      'show_admin_column' => true,
      'show_in_rest' => true,
      'rewrite' => ['slug' => 'gallery'],
    ]);
  }
}