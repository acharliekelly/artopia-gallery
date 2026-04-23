<?php
/**
 * Plugin Name: Artopia Gallery
 * Plugin URI: https://artopiagallery.com
 * Description: Custom gallery platform plugin for Artopia.
 * Version: 0.1.0
 * Author: aCharlieKelly
 * Text Domain: artopia-gallery
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ARTOPIA_GALLERY_VERSION', '0.1.0');
define('ARTOPIA_GALLERY_PLUGIN_FILE', __FILE__);
define('ARTOPIA_GALLERY_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ARTOPIA_GALLERY_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once ARTOPIA_GALLERY_PLUGIN_PATH . 'includes/class-plugin.php';
require_once ARTOPIA_GALLERY_PLUGIN_PATH . 'includes/class-post-types.php';
require_once ARTOPIA_GALLERY_PLUGIN_PATH . 'includes/class-taxonomies.php';
require_once ARTOPIA_GALLERY_PLUGIN_PATH . 'includes/class-meta-boxes.php';
require_once ARTOPIA_GALLERY_PLUGIN_PATH . 'includes/class-meta.php';

register_activation_hook(ARTOPIA_GALLERY_PLUGIN_FILE, ['Artopia_Gallery\\Plugin', 'activate']);
register_deactivation_hook(ARTOPIA_GALLERY_PLUGIN_FILE, ['Artopia_Gallery\\Plugin', 'deactivate']);

function artopia_gallery_run(): void {
    $plugin = new Artopia_Gallery\Plugin();
    $plugin->run();
}

artopia_gallery_run();