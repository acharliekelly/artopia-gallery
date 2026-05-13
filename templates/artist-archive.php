<?php

if (!defined('ABSPATH')) {
    exit;
}

\Artopia_Gallery\Shortcodes::enqueue_public_assets();

get_header();
?>

<main class="artopia-artist-archive">
    <div class="artopia-artist-archive__inner">
        <header class="artopia-artist-archive__header">
            <h1 class="artopia-artist-archive__title">
                <?php post_type_archive_title(); ?>
            </h1>

            <?php $description = get_the_archive_description(); ?>
            <?php if ($description !== '') : ?>
                <div class="artopia-artist-archive__description">
                    <?php echo wp_kses_post($description); ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>
            <div class="artopia-artist-archive__grid">
                <?php while (have_posts()) : the_post(); ?>
                    <article <?php post_class('artopia-artist-card'); ?>>
                        <a class="artopia-artist-card__link" href="<?php the_permalink(); ?>">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="artopia-artist-card__image">
                                    <?php the_post_thumbnail('medium_large'); ?>
                                </div>
                            <?php endif; ?>

                            <h2 class="artopia-artist-card__title">
                                <?php the_title(); ?>
                            </h2>
                        </a>

                        <?php if (has_excerpt()) : ?>
                            <div class="artopia-artist-card__excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>

            <div class="artopia-artist-archive__pagination">
                <?php the_posts_navigation(); ?>
            </div>
        <?php else : ?>
            <div class="artopia-artist-archive__empty">
                <p><?php esc_html_e('No artists found.', 'artopia-gallery'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
