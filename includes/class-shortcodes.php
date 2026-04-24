<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Shortcodes {
  public function run(): void {
    add_shortcode('artopia-gallery', [$this, 'render_gallery_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'register_assets']);
  }

  public function register_assets(): void {
    wp_register_style(
      'artopia-gallery-public',
      ARTOPIA_GALLERY_PLUGIN_URL . 'public/css/artopia-gallery.css',
      [],
      ARTOPIA_GALLERY_VERSION
    );

    wp_register_script(
      'artopia-gallery-public',
      ARTOPIA_GALLERY_PLUGIN_URL . 'public/js/artopia-gallery.js',
      [],
      ARTOPIA_GALLERY_VERSION,
      true
    );
  }

  public function render_gallery_shortcode(array $atts = []): string {
    $atts = shortcode_atts([
      'gallery' => '',
      'artist_id' => 0,
      'limit' => -1,
    ], $atts, 'artopia-gallery');

    $gallery_slug = sanitize_title((string) $atts['gallery']);
    $artist_id = absint($atts['artist_id']);
    $limit = (int) $atts['limit'];

    $tax_query = [];
    if ($gallery_slug !== '') {
      $tax_query = [
        'taxonomy' => 'gallery',
        'field'    => 'slug',
        'terms'    => $gallery_slug,
      ];
    }

    $meta_query = [];
    if ($artist_id > 0) {
      $meta_query[] = [
        'key' => '_artopia_artist_id',
        'value' => $artist_id,
        'compare' => '=',
      ];
    }

    $query_args = [
      'post_type'       => 'artwork',
      'post_status'     => 'publish',
      'posts_per_page'  => $limit > 0 ? $limit : -1,
      'orderby'         => 'title',
      'order'           => 'ASC',
    ];

    if (!empty($tax_query)) {
      $query_args['tax_query'] = $tax_query;
    }

    if (!empty($meta_query)) {
      $query_args['meta_query'] = $meta_query;
    }

    $artworks = get_posts($query_args);

    if (empty($artworks)) {
      return '<div class="artopia-gallery-empty">' .
          esc_html__('No artwork found for this gallery.', 'artopia-gallery') .
          '</div>';
    }

    wp_enqueue_style('artopia-gallery-public');
    wp_enqueue_script('artopia-gallery-public');

    $gallery_title = '';
    $gallery_description = '';
    $gallery_term = null;

    if ($gallery_slug !== '') {
      $term = get_term_by('slug', $gallery_slug, 'gallery');

      if ($term && !is_wp_error($term)) {
        $gallery_term = $term;
        $gallery_title = $term->name;
        $gallery_description = term_description((int) $term->term_id, 'gallery');
      }
    }

    $artist_name = '';
    if ($artist_id > 0) {
      $artist = get_post($artist_id);

      if ($artist && $artist->post_type === 'artist') {
        $artist_name = get_the_title($artist);
      }
    }

    if ($artist_name === '' && !empty($artworks)) {
      $first_artist_id = (int) get_post_meta((int) $artworks[0]->ID, '_artopia_artist_id', true);

      if ($first_artist_id > 0) {
        $artist = get_post($first_artist_id);

        if ($artist && $artist->post_type === 'artist') {
          $artist_name = get_the_title($artist);
        }
      }
    }

    $artwork_count = count($artworks);

    ob_start();
  ?>
    <div class="artopia-gallery-wrapper">
      <header class="artopia-gallery-header">
        <div class="artopia-gallery-eyebrow">
          <?php esc_html_e('Artopia Gallery', 'artopia-gallery'); ?>
        </div>

        <?php if ($gallery_title !== '') : ?>
          <h2 class="artopia-gallery-title"><?php echo esc_html($gallery_title); ?></h2>
        <?php endif; ?>

        <?php if ($artist_name !== '') : ?>
          <div class="artopia-gallery-artist">
            <?php echo esc_html($artist_name); ?>
          </div>
        <?php endif; ?>

        <?php if ($gallery_description !== '') : ?>
          <div class="artopia-gallery-description">
            <?php echo wp_kses_post($gallery_description); ?>
          </div>
        <?php endif; ?>

        <div class="artopia-gallery-count">
          <?php
            printf(
              esc_html(
                _n(
                  '%d work',
                  '%d works',
                  $artwork_count,
                  'artopia-gallery'
                )
              ),
              (int) $artwork_count
            );
          ?>
        </div>
      </header>
      
      <div class="artopia-gallery-grid">
        <?php foreach ($artworks as $artwork) : ?>
          <?php
            $artwork_id = (int) $artwork->ID;
            $medium = get_post_meta($artwork_id, '_artopia_medium', true);
            $year = get_post_meta($artwork_id, '_artopia_year', true);
            $dimensions = get_post_meta($artwork_id, '_artopia_dimensions', true);
            $price = get_post_meta($artwork_id, '_artopia_price', true);
            $status = get_post_meta($artwork_id, '_artopia_status', true);
            $status_label = Helpers::artwork_statuses()[$status] ?? __('Available', 'artopia-gallery');
            $thumbnail_id = get_post_thumbnail_id($artwork_id);

            $thumb_html = $thumbnail_id
                ? wp_get_attachment_image($thumbnail_id, 'medium_large', false, [
                  'class' => 'artopia-gallery-image',
                  'loading' => 'lazy',
                ])
                : '<div class="artopia-gallery-placeholder">' . esc_html('No image', 'artopia-gallery') . '</div>';
            
            $lightbox_url = '';

            if ($thumbnail_id) {
              $lightbox_url = 
                  wp_get_original_image_url($thumbnail_id) ?:
                  wp_get_attachment_image_url($thumbnail_id, 'full') ?:
                  wp_get_attachment_image_url($thumbnail_id, 'large') ?:
                  wp_get_attachment_image_url($thumbnail_id, 'medium_large') ?:
                  wp_get_attachment_image_url($thumbnail_id, 'medium') ?:
                  '';
            }

          ?>
          <article
              class="artopia-gallery-card"
              data-full-image="<?php echo esc_url($lightbox_url); ?>"
              data-title="<?php echo esc_attr(get_the_title($artwork_id)); ?>"
              data-medium="<?php echo esc_attr((string) $medium); ?>"
              data-year="<?php echo esc_attr((string) $year); ?>"
              data-dimensions="<?php echo esc_attr((string) $dimensions); ?>"
              data-price="<?php echo esc_attr((string) $price); ?>"
              data-status="<?php echo esc_attr((string) $status_label); ?>"
              data-description="<?php echo esc_attr(wp_strip_all_tags((string) $artwork->post_content)); ?>"
          >
            <button type="button" class="artopia-gallery-card-button">
              <div class="artopia-gallery-card-media">
                <?php echo $thumb_html; ?>
              </div>
              <div class="artopia-gallery-card-body">
                <h3 class="artopia-gallery-card-title">
                  <?php echo esc_html(get_the_title($artwork_id)); ?>
                </h3>

                <div class="artopia-gallery-card-meta">
                  <?php if ($medium !== '') : ?>
                    <div><?php echo esc_html($medium); ?></div>
                  <?php endif; ?>
                  <?php if (!empty($year)) : ?>
                    <div><?php echo esc_html((string) $year) ?></div>
                  <?php endif; ?>
                  <?php if ($price !== '') : ?>
                    <div>$<?php echo esc_html($price); ?></div>
                  <?php endif; ?>
                  <div class="artopia-gallery-card-status">
                    <?php echo esc_html($status_label); ?>
                  </div>
                </div>
              </div>
            </button>
        </article>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="artopia-gallery-lightbox" hidden>
      <div class="artopia-gallery-lightbox-backdrop"></div>

      <div class="artopia-gallery-lightbox-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Artwork details', 'artopia-gallery'); ?>">
        
        <!-- CLOSE BUTTON -->
        <button type="button" class="artopia-gallery-lightbox-close" aria-label="<?php esc_attr_e('Close', 'artopia-gallery'); ?>">
          x
        </button>

        <!-- PREV BUTTON -->
        <button type="button" class="artopia-gallery-lightbox-nav artopia-gallery-lightbox-prev" aria-label="<?php esc_attr_e('Next image', 'artopia-gallery'); ?>">
          &lt;
        </button>

        <!-- NEXT BUTTON -->
        <button type="button" class="artopia-gallery-lightbox-nav artopia-gallery-lightbox-next" aria-label="<?php esc_attr_e('Next image', 'artopia-gallery'); ?>">
          &gt;
        </button>


        <div class="artopia-gallery-lightbox-content">
          <div class="artopia-gallery-lightbox-image-wrap">
            <img src="" alt="" class="artopia-gallery-lightbox-image" />
          </div>
          <div class="artopia-gallery-lightbox-details">
            <div class="artopia-gallery-lightbox-counter"></div>
            <h3 class="artopia-gallery-lightbox-title"></h3>
            <p class="artopia-gallery-lightbox-medium"></p>
            <p class="artopia-gallery-lightbox-year"></p>
            <p class="artopia-gallery-lightbox-dimensions"></p>
            <p class="artopia-gallery-lightbox-price"></p>
            <p class="artopia-gallery-lightbox-status"></p>
            <p class="artopia-gallery-lightbox-description"></p>
          </div>
        </div>
      </div>
    </div>
  <?php
    return (string) ob_get_clean();
  }
}