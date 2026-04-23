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

  public function sanitize_year($value): string {
    $value = is_string($value) ? $value : (string) $value;
    $value = trim($value);

    // Keep digits and decimal points only for now
    $value = preg_replace('/[^0-9.]/', '', $value);

    return is_string($value) ? $value : '';
  }

  public function sanitize_status($value): string {
    $allowed_statuses = ['available', 'sold', 'inquiry', 'print_available'];
    $value = is_string($value) ? sanitize_text_field( $value ) : 'available';

    if (!in_array($value, $allowed_statuses, true)) {
      return 'available';
    }

    return $value;
  }
}