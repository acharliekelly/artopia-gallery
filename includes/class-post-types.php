<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Post_Types {
  public function register(): void {
    $this->register_artist_post_type();
    $this->register_artwork_post_type();
  }

  private function register_artist_post_type(): void {
    register_post_type('artist', [
      'labels' => [
        'name' => __('Artists', 'artopia-gallery'),
        'singular_name' => __('Artist', 'artopia-gallery'),
      ],
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_rest' => true,
      'menu_icon' => 'dashicons-admin-users',
      'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
      'rewrite' => ['slug' => 'artists'],
    ]);
  }

  private function register_artwork_post_type(): void {
    register_post_type('artwork', [
      'labels' => [
          'name' => __('Artworks', 'artopia-gallery'),
          'singular_name' => __('Artwork', 'artopia-gallery'),
      ],
      'public' => true,
      'show_ui' => true,
      'show_in_menu' => true,
      'show_in_rest' => true,
      'menu_icon' => 'dashicons-format-image',
      'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
      'has_archive' => true,
      'rewrite' => ['slug' => 'artworks'],
    ]);
  }
    
}