<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Admin 
{
  
  public function run(): void 
  {
      add_action('admin_menu', [$this, 'register_admin_menu']);
  }

  public function register_admin_menu(): void 
  {
    add_menu_page(
      __('Artopia Gallery', 'artopia-gallery'),
      __('Artopia Gallery', 'artopia-gallery'),
      'edit_posts',
      'artopia-gallery',
      [$this, 'render_dashboard_page'],
      'dashicons-format-gallery',
      25
    );

    add_submenu_page(
      'artopia-gallery',
      __('Import Artwork', 'artopia-gallery'),
      __('Import', 'artopia-gallery'),
      'edit_posts',
      'artopia-gallery-import',
      [$this, 'render_import_page']
    );

    add_submenu_page(
      'artopia-gallery',
      __('Gallery Ownership', 'artopia-gallery'),
      __('Gallery Ownership', 'artopia-gallery'),
      'edit_posts',
      'artopia-gallery-ownership',
      [$this, 'render_gallery_ownership_page']
    );

  }

  public function render_dashboard_page(): void 
  {
    if (!current_user_can('edit_posts')) {
      wp_die(esc_html__('You do not have permission to access this page.', 'artopia-gallery'));
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Artopia Gallery', 'artopia-gallery') . '</h1>';
    echo '<p>' . esc_html__('Welcome. The gallery engine is warming up nicely.', 'artopia-gallery') . '</p>';
    echo '</div>';
  }

  public function render_import_page(): void 
  {
    if (!current_user_can('edit_posts')) {
      wp_die(esc_html__('You do not have permission to access this page.', 'artopia-gallery'));
    }

    $importer = new Importer();
    $result = $importer->handle_request();

    $view_path = ARTOPIA_GALLERY_PLUGIN_PATH . 'admin/views/import-page.php';

    if (file_exists($view_path)) {
      include $view_path;
    }
  }

  public function render_gallery_ownership_page(): void
  {
      if (!current_user_can('edit_posts')) {
          wp_die(esc_html__('You do not have permission to view this page.', 'artopia-gallery'));
      }

      $gallery_terms = new Gallery_Terms();
      $report = $gallery_terms->get_gallery_ownership_report();

      require ARTOPIA_GALLERY_PLUGIN_PATH . 'admin/views/gallery-ownership-page.php';
  }

}
