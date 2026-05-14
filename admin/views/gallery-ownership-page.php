<?php
/**
 * @var array $report
 * @var array $artists
 * @var array $messages
 * @var array $errors
 */

if (!defined('ABSPATH')) {
    exit;
}

$summary = $report['summary'] ?? [
    'total' => 0,
    'owned' => 0,
    'unowned' => 0,
];

$rows = $report['rows'] ?? [];
$artists = $artists ?? [];
$messages = $messages ?? [];
$errors = $errors ?? [];
?>

<div class="wrap">
    <h1><?php esc_html_e('Gallery Ownership', 'artopia-gallery'); ?></h1>

    <p>
        <?php esc_html_e('Review gallery terms and assign artist owners to legacy unowned galleries.', 'artopia-gallery'); ?>
    </p>

    <form method="post" style="margin: 1rem 0 2rem;">
        <?php wp_nonce_field('artopia_backfill_gallery_owners', 'artopia_gallery_backfill_nonce'); ?>
        <input type="hidden" name="artopia_gallery_ownership_action" value="backfill_owners" />

        <p class="description" style="max-width: 760px; margin-bottom: 0.75rem;">
            <?php esc_html_e('Backfill ownership for legacy unowned gallery terms only when all attached artworks resolve to the same artist. Terms with no usable artist signal or multiple artists will be skipped.', 'artopia-gallery'); ?>
        </p>

        <button type="submit" class="button button-secondary">
            <?php esc_html_e('Backfill Unowned Galleries', 'artopia-gallery'); ?>
        </button>
    </form>


    <?php if (!empty($errors)) : ?>
        <div class="notice notice-error">
            <ul>
                <?php foreach ($errors as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($messages)) : ?>
        <div class="notice notice-success">
            <ul>
                <?php foreach ($messages as $message) : ?>
                    <li><?php echo esc_html($message); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <table class="widefat striped" style="max-width: 640px; margin-bottom: 2rem;">
        <tbody>
            <tr>
                <th><?php esc_html_e('Total Galleries', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $summary['total']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Owned Galleries', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $summary['owned']); ?></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Legacy Unowned Galleries', 'artopia-gallery'); ?></th>
                <td><?php echo esc_html((string) $summary['unowned']); ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (empty($rows)) : ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No gallery terms found.', 'artopia-gallery'); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Term ID', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Name', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Slug', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Artist', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Artist ID', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Ownership', 'artopia-gallery'); ?></th>
                    <th><?php esc_html_e('Actions', 'artopia-gallery'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <?php
                    $term_id = isset($row['term_id']) ? (int) $row['term_id'] : 0;
                    $name = isset($row['name']) ? (string) $row['name'] : '';
                    $slug = isset($row['slug']) ? (string) $row['slug'] : '';
                    $artist_name = isset($row['artist_name']) ? (string) $row['artist_name'] : '';
                    $artist_id = isset($row['artist_id']) ? (int) $row['artist_id'] : 0;
                    $is_owned = !empty($row['is_owned']);
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) $term_id); ?></td>
                        <td><?php echo esc_html($name); ?></td>
                        <td><code><?php echo esc_html($slug); ?></code></td>
                        <td><?php echo esc_html($artist_name !== '' ? $artist_name : '—'); ?></td>
                        <td><?php echo esc_html($artist_id > 0 ? (string) $artist_id : '—'); ?></td>
                        <td>
                            <?php echo esc_html($is_owned ? __('Owned', 'artopia-gallery') : __('Legacy / Unowned', 'artopia-gallery')); ?>
                        </td>
                        <td>
                            <?php if ($is_owned) : ?>
                                <?php echo esc_html('—'); ?>
                            <?php else : ?>
                                <form method="post">
                                    <?php wp_nonce_field('artopia_assign_gallery_owner', 'artopia_gallery_owner_nonce'); ?>
                                    <input type="hidden" name="artopia_gallery_ownership_action" value="assign_owner" />
                                    <input type="hidden" name="artopia_gallery_term_id" value="<?php echo esc_attr((string) $term_id); ?>" />

                                    <select name="artopia_gallery_artist_id">
                                        <option value="0"><?php esc_html_e('Select artist', 'artopia-gallery'); ?></option>
                                        <?php foreach ($artists as $artist) : ?>
                                            <option value="<?php echo esc_attr((string) $artist->ID); ?>">
                                                <?php echo esc_html($artist->post_title ?: sprintf(__('Artist #%d', 'artopia-gallery'), $artist->ID)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="button button-secondary">
                                        <?php esc_html_e('Assign Owner', 'artopia-gallery'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
