<?php

if (!defined('ABSPATH')) {
    exit;
}

\Artopia_Gallery\Shortcodes::enqueue_public_assets();

get_header();
?>

<main class="artopia-single-artwork">
    <div class="artopia-single-artwork__inner">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $artwork_id = get_the_ID();
                $artist_id = (int) get_post_meta($artwork_id, '_artopia_artist_id', true);
                $medium = get_post_meta($artwork_id, '_artopia_medium', true);
                $year = get_post_meta($artwork_id, '_artopia_year', true);
                $dimensions = get_post_meta($artwork_id, '_artopia_dimensions', true);
                $price = get_post_meta($artwork_id, '_artopia_price', true);
                $status = get_post_meta($artwork_id, '_artopia_status', true);
                $status_label = \Artopia_Gallery\Helpers::artwork_statuses()[$status] ?? __('Available', 'artopia-gallery');
                $artist = $artist_id > 0 ? get_post($artist_id) : null;
                $gallery_terms = get_the_terms($artwork_id, 'gallery');
                ?>
                <article <?php post_class('artopia-single-artwork__article'); ?>>
                    <div class="artopia-single-artwork__media">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', ['class' => 'artopia-single-artwork__image']); ?>
                        <?php endif; ?>
                    </div>

                    <div class="artopia-single-artwork__details">
                        <header class="artopia-single-artwork__header">
                            <h1 class="artopia-single-artwork__title"><?php the_title(); ?></h1>

                            <?php if ($artist && $artist->post_type === 'artist') : ?>
                                <div class="artopia-single-artwork__artist">
                                    <a href="<?php echo esc_url(get_permalink($artist)); ?>">
                                        <?php echo esc_html(get_the_title($artist)); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="artopia-single-artwork__status">
                                <?php echo esc_html($status_label); ?>
                            </div>
                        </header>

                        <dl class="artopia-single-artwork__meta">
                            <?php if ($medium !== '') : ?>
                                <div class="artopia-single-artwork__meta-row">
                                    <dt><?php esc_html_e('Medium', 'artopia-gallery'); ?></dt>
                                    <dd><?php echo esc_html($medium); ?></dd>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($year)) : ?>
                                <div class="artopia-single-artwork__meta-row">
                                    <dt><?php esc_html_e('Year', 'artopia-gallery'); ?></dt>
                                    <dd><?php echo esc_html((string) $year); ?></dd>
                                </div>
                            <?php endif; ?>

                            <?php if ($dimensions !== '') : ?>
                                <div class="artopia-single-artwork__meta-row">
                                    <dt><?php esc_html_e('Dimensions', 'artopia-gallery'); ?></dt>
                                    <dd><?php echo esc_html($dimensions); ?></dd>
                                </div>
                            <?php endif; ?>

                            <?php if ($price !== '') : ?>
                                <div class="artopia-single-artwork__meta-row">
                                    <dt><?php esc_html_e('Price', 'artopia-gallery'); ?></dt>
                                    <dd><?php echo esc_html('$' . $price); ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>

                        <?php if (get_the_content() !== '') : ?>
                            <div class="artopia-single-artwork__content">
                                <?php the_content(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($gallery_terms) && !is_wp_error($gallery_terms)) : ?>
                            <div class="artopia-single-artwork__galleries">
                                <h2 class="artopia-single-artwork__galleries-title">
                                    <?php esc_html_e('Galleries', 'artopia-gallery'); ?>
                                </h2>
                                <ul class="artopia-single-artwork__gallery-list">
                                    <?php foreach ($gallery_terms as $term) : ?>
                                <li>
                                    <?php
                                    $term_link = get_term_link($term);

                                    if (!is_wp_error($term_link)) :
                                    ?>
                                        <a href="<?php echo esc_url($term_link); ?>">
                                            <?php echo esc_html($term->name); ?>
                                        </a>
                                            <?php else : ?>
                                                <?php echo esc_html($term->name); ?>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>

                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="artopia-single-artwork__empty">
                <p><?php esc_html_e('Artwork not found.', 'artopia-gallery'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
