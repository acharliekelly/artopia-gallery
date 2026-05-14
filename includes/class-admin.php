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
      $feedback = $this->handle_gallery_ownership_actions($gallery_terms);
      $report = $gallery_terms->get_gallery_ownership_report();
      $artists = get_posts([
            'post_type'      => 'artist',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
      $messages = $feedback['messages'];
      $errors = $feedback['errors'];

      require ARTOPIA_GALLERY_PLUGIN_PATH . 'admin/views/gallery-ownership-page.php';
  }

  private function handle_gallery_ownership_actions(Gallery_Terms $gallery_terms): array
  {
      $feedback = [
          'messages' => [],
          'errors' => [],
      ];

      /** @disregard undefined variable $_SERVER */
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
          return $feedback;
      }

      if (!isset($_POST['artopia_gallery_ownership_action'])) {
          return $feedback;
      }

      $action = sanitize_text_field(wp_unslash($_POST['artopia_gallery_ownership_action']));

      if ($action !== 'assign_owner') {
          return $feedback;
      }

      if (!current_user_can('edit_posts')) {
          $feedback['errors'][] = __('You do not have permission to update gallery ownership.', 'artopia-gallery');
          return $feedback;
      }

      if (
          !isset($_POST['artopia_gallery_owner_nonce']) ||
          !wp_verify_nonce(
              sanitize_text_field(wp_unslash($_POST['artopia_gallery_owner_nonce'])),
              'artopia_assign_gallery_owner'
          )
      ) {
          $feedback['errors'][] = __('Security check failed. Please try again.', 'artopia-gallery');
          return $feedback;
      }

      $term_id = isset($_POST['artopia_gallery_term_id']) ? absint(wp_unslash($_POST['artopia_gallery_term_id'])) : 0;
      $artist_id = isset($_POST['artopia_gallery_artist_id']) ? absint(wp_unslash($_POST['artopia_gallery_artist_id'])) : 0;

      $result = $gallery_terms->assign_artist_to_term($term_id, $artist_id);

      if (is_wp_error($result)) {
          $feedback['errors'][] = $result->get_error_message();
          return $feedback;
      }

      $feedback['messages'][] = __('Gallery ownership updated.', 'artopia-gallery');

      return $feedback;
  }

}
