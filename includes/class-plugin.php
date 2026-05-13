<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {
    public function run(): void {
        add_action('init', [$this, 'register_content_types']);
        add_action('init', [$this, 'register_meta']);

        (new Meta_Boxes())->run();
        (new Admin())->run();
        (new Shortcodes())->run();
        (new Templates())->run();
    }

    public function register_content_types(): void {
        (new Post_Types())->register();
        (new Taxonomies())->register();
    }

    public function register_meta(): void {
        (new Meta())->register();
    }

    public static function activate(): void {
        (new Post_Types())->register();
        (new Taxonomies())->register();
        (new Meta())->register();

        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}