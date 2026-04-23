<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Meta {
  public function register(): void {
    $this->register_artwork_meta();
  }

  private function register_artwork_meta(): void {
    register_post_meta('artwork', '_artopia_artist_id', [
      'type' => 'integer',
      'single' => 'true',
      'default' => 0,
      'sanitize_callback' => [$this, 'sanitize_artist_id'],
      'show_in_rest' => true,
      'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_medium', [
        'type' => 'string',
        'single' => true,
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest' => true,
        'auth_callback' => [$this, 'can_edit_post_meta'],
    ]);

    register_post_meta('artwork', '_artopia_year', [
          'type' => 'integer',
          'single' => true,
          'default' => 0,
          'sanitize_callback' => [$this, 'sanitize_year'],
          'show_in_rest' => true,
          'auth_callback' => [$this, 'can_edit_post_meta'],
      ]);

      register_post_meta('artwork', '_artopia_dimensions', [
          'type' => 'string',
          'single' => true,
          'default' => '',
          'sanitize_callback' => 'sanitize_text_field',
          'show_in_rest' => true,
          'auth_callback' => [$this, 'can_edit_post_meta'],
      ]);

      register_post_meta('artwork', '_artopia_price', [
          'type' => 'string',
          'single' => true,
          'default' => '',
          'sanitize_callback' => [$this, 'sanitize_price'],
          'show_in_rest' => true,
          'auth_callback' => [$this, 'can_edit_post_meta'],
      ]);

      register_post_meta('artwork', '_artopia_status', [
          'type' => 'string',
          'single' => true,
          'default' => 'available',
          'sanitize_callback' => [$this, 'sanitize_status'],
          'show_in_rest' => true,
          'auth_callback' => [$this, 'can_edit_post_meta'],
      ]);
  }

  public function can_edit_post_meta(): bool {
    return current_user_can('edit_posts');
  }

  public function sanitize_artist_id($value): int {
    $artist_id = absint($value);
    
    if (!$artist_id) {
      return 0;
    }

    $artist = get_post($artist_id);

    if (!$artist || $artist->post_type !== 'artist') {
      return 0;
    }

    return $artist_id;
  }

  public function sanitize_year($value): int {
    $year = absint($value);

    if ($year > 9999) {
      return 9999;
    }

    return $year;
  }

  public function sanitize_price($value): string {
    $value = is_string($value) ? $value : (string) $value;
    $value = trim($value);
    $value = preg_replace('/[^0-9.]/', '', $value);

    return is_string($value) ? $value : '';
  }

  public function sanitize_status($value): string {
    $status = Helpers::normalize_artwork_status($value);
    return $status;
  }
}