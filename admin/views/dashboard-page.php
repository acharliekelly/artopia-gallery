<?php
/**
 * @var array $stats
 * @var array $links
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = $stats ?? [
    'artists' => 0,
    'artworks' => 0,
    'artworks_published' => 0,
    'artworks_draft' => 0,
    'gallery_terms_total' => 0,
    'gallery_terms_owned' => 0,
    'gallery_terms_unowned' => 0,
];

$links = $links ?? [
    'import' => '',
    'ownership' => '',
];
?>

<div class="wrap">
    <h1><?php esc_html_e('Artopia Gallery', 'artopia-gallery'); ?></h1>

    <p>
        <?php esc_html_e('Operational overview for artist, artwork, and gallery content.', 'artopia-gallery'); ?>
    </p>

    <h2><?php esc_html_e('Content Overview', 'artopia-gallery'); ?></h2>

    <table class="widefat striped" style="max-width: 720px; margin-bottom: 2rem;">
        <tbody>
            <tr>
                <th><?php esc_html_e('Artists', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['artists']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Artworks', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['artworks']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Published Artworks', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['artworks_published']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Draft Artworks', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['artworks_draft']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Gallery Terms', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['gallery_terms_total']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Owned Galleries', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['gallery_terms_owned']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Legacy Unowned Galleries', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $stats['gallery_terms_unowned']); ?></td>
            </tr>
        </tbody>
    </table>

    <h2><?php esc_html_e('Quick Actions', 'artopia-gallery'); ?></h2>

    <p>
        <?php if ($links['import'] !== '') : ?>
            <a class="button button-primary" href="<?php echo esc_url($links['import']); ?>">
                <?php esc_html_e('Import CSV', 'artopia-gallery'); ?>
            </a>
        <?php endif; ?>

        <?php if ($links['ownership'] !== '') : ?>
            <a class="button button-secondary" href="<?php echo esc_url($links['ownership']); ?>">
                <?php esc_html_e('Gallery Ownership', 'artopia-gallery'); ?>
            </a>
        <?php endif; ?>
    </p>

    <?php if ($stats['gallery_terms_unowned'] > 0 || $stats['artworks_draft'] > 0) : ?>
        <h2><?php esc_html_e('Attention Needed', 'artopia-gallery'); ?></h2>

        <?php if ($stats['gallery_terms_unowned'] > 0) : ?>
            <div class="notice notice-warning inline">
                <p>
                    <?php
                    echo esc_html(sprintf(
                        __('There are %d legacy unowned gallery term(s) that may need review or backfill.', 'artopia-gallery'),
                        (int) $stats['gallery_terms_unowned']
                    ));
                    ?>
                    <?php if ($links['ownership'] !== '') : ?>
                        <a href="<?php echo esc_url($links['ownership']); ?>">
                            <?php esc_html_e('Review gallery ownership', 'artopia-gallery'); ?>
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($stats['artworks_draft'] > 0) : ?>
            <div class="notice notice-info inline">
                <p>
                    <?php
                    echo esc_html(sprintf(
                        __('There are %d draft artwork post(s). Review unpublished imports before launch.', 'artopia-gallery'),
                        (int) $stats['artworks_draft']
                    ));
                    ?>
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
