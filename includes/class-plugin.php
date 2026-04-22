<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Plugin {
  public function run(): void {
    add_action('init', [$this, 'register_content_types']);
  }

  public function register_content_types(): void {
    $post_types = new Post_Types();
    $post_types->register();

    $taxonomies = new Taxonomies();
    $taxonomies->register();
  }

  public static function activate(): void {
    $post_types = new Post_Types();
    $post_types->register();

    $taxonomies = new Taxonomies();
    $taxonomies->register();
  }

  public function deactivate(): void {
    flush_rewrite_rules();
  }
}