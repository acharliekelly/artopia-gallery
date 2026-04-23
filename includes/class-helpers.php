<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Helpers {
  public static function artwork_statuses(): array {
    return [
      'available'       => __('Available', 'artopia-gallery'),
      'sold'            => __('Sold', 'artopia-gallery'),
      'inquiry'         => __('Inquiry', 'artopia-gallery'),
      'print_available' => __('Print Available', 'artopia-gallery'),
    ];
  }

  public static function valid_artwork_status(string $status): bool {
    return array_key_exists($status, self::artwork_statuses());
  }

  public static function normalize_artwork_status(string $status): string {
    return self::valid_artwork_status($status)
        ? $status
        : 'available';
  }
}