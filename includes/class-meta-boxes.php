<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Meta_Boxes {
  public function run(): void {
    add_action( 'add_meta_boxes', [$this, 'register_artwork_meta_box']);
    add_action( 'save_post', [$this, 'save_artwork_details']);
  }

  public function register_artwork_meta_box(): void {
    add_meta_box(
      'artopia_artwork_details',
      __('Artwork Details', 'artopia-gallery'),
      [$this, 'render_artwork_details_meta_box'],
      'artwork',
      'normal',
      'default'
    );
  }

  public function render_artwork_details_meta_box(\WP_Post $post): void {
    wp_nonce_field('artopia_save_artwork_details', 'artopia_artwork_details_nonce');

    $medium     = get_post_meta( $post->ID, '_artopia_medium', true );
    $year       = get_post_meta( $post->ID, '_artopia_year', true );
    $dimensions = get_post_meta( $post->ID, '_artopia_dimensions', true );
    $price      = get_post_meta( $post->ID, '_artopia_price', true );
    $status     = get_post_meta( $post->ID, '_artopia_status', true);

    if (empty($status)) {
      $status = 'available';
    }
    ?>
    <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="artopia_medium"><?php esc_html_e('Medium', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="artopia_medium"
                            name="artopia_medium"
                            value="<?php echo esc_attr($medium); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_year"><?php esc_html_e('Year', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input
                            type="number"
                            id="artopia_year"
                            name="artopia_year"
                            value="<?php echo esc_attr($year); ?>"
                            class="small-text"
                            min="0"
                            max="9999"
                            step="1"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_dimensions"><?php esc_html_e('Dimensions', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="artopia_dimensions"
                            name="artopia_dimensions"
                            value="<?php echo esc_attr($dimensions); ?>"
                            class="regular-text"
                            placeholder="e.g. 24 x 36 in"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_price"><?php esc_html_e('Price', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="artopia_price"
                            name="artopia_price"
                            value="<?php echo esc_attr($price); ?>"
                            class="regular-text"
                            placeholder="e.g. 1200.00"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_status"><?php esc_html_e('Status', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <select id="artopia_status" name="artopia_status">
                            <option value="available" <?php selected($status, 'available'); ?>>
                                <?php esc_html_e('Available', 'artopia-gallery'); ?>
                            </option>
                            <option value="sold" <?php selected($status, 'sold'); ?>>
                                <?php esc_html_e('Sold', 'artopia-gallery'); ?>
                            </option>
                            <option value="inquiry" <?php selected($status, 'inquiry'); ?>>
                                <?php esc_html_e('Inquiry', 'artopia-gallery'); ?>
                            </option>
                            <option value="print_available" <?php selected($status, 'print_available'); ?>>
                                <?php esc_html_e('Print Available', 'artopia-gallery'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php
  }

  public function save_artwork_details(int $post_id): void
{
    if (!isset($_POST['artopia_artwork_details_nonce'])) {
        return;
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['artopia_artwork_details_nonce']));

    if (!wp_verify_nonce($nonce, 'artopia_save_artwork_details')) {
        return;
    }

    if (wp_is_post_autosave($post_id)) {
        return;
    }

    if ($parent_id = wp_is_post_revision($post_id)) {
        $post_id = $parent_id;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $medium = isset($_POST['artopia_medium'])
        ? sanitize_text_field(wp_unslash($_POST['artopia_medium']))
        : '';

    $year = isset($_POST['artopia_year'])
        ? absint(wp_unslash($_POST['artopia_year']))
        : '';

    $dimensions = isset($_POST['artopia_dimensions'])
        ? sanitize_text_field(wp_unslash($_POST['artopia_dimensions']))
        : '';

    $price = isset($_POST['artopia_price'])
        ? sanitize_text_field(wp_unslash($_POST['artopia_price']))
        : '';

    $allowed_statuses = ['available', 'sold', 'inquiry', 'print_available'];

    $status = isset($_POST['artopia_status'])
        ? sanitize_text_field(wp_unslash($_POST['artopia_status']))
        : 'available';

    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'available';
    }

    update_post_meta($post_id, '_artopia_medium', $medium);
    update_post_meta($post_id, '_artopia_year', $year);
    update_post_meta($post_id, '_artopia_dimensions', $dimensions);
    update_post_meta($post_id, '_artopia_price', $price);
    update_post_meta($post_id, '_artopia_status', $status);
  }
}