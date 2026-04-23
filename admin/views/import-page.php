<?php
/**
 * @var array $result
 */

if (!defined('ABSPATH')) {
    exit;
}

$artists = get_posts([
    'post_type'      => 'artist',
    'post_status'    => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$preview_rows = array_slice($result['rows'], 0, 10);
?>

<div class="wrap">
    <h1><?php esc_html_e('Import Artwork CSV', 'artopia-gallery'); ?></h1>

    <p>
        <?php esc_html_e('Validate a CSV first, then import it into Artwork posts.', 'artopia-gallery'); ?>
    </p>

    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('artopia_import_csv', 'artopia_import_nonce'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="artopia_artist_id"><?php esc_html_e('Artist', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <select name="artopia_artist_id" id="artopia_artist_id">
                            <option value="0"><?php esc_html_e('Select an artist', 'artopia-gallery'); ?></option>
                            <?php foreach ($artists as $artist) : ?>
                                <option value="<?php echo esc_attr((string) $artist->ID); ?>" <?php selected($result['artist_id'], $artist->ID); ?>>
                                    <?php echo esc_html($artist->post_title ?: sprintf(__('Artist #%d', 'artopia-gallery'), $artist->ID)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_gallery_name"><?php esc_html_e('Gallery Name', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="artopia_gallery_name"
                            id="artopia_gallery_name"
                            class="regular-text"
                            value="<?php echo esc_attr($result['gallery_name']); ?>"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="artopia_csv_file"><?php esc_html_e('CSV File', 'artopia-gallery'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="artopia_csv_file" id="artopia_csv_file" accept=".csv,text/csv" />
                        <p class="description">
                            <?php esc_html_e('Required columns: filename, title', 'artopia-gallery'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" name="artopia_import_action" value="validate" class="button button-secondary">
                <?php esc_html_e('Validate CSV', 'artopia-gallery'); ?>
            </button>
            <button type="submit" name="artopia_import_action" value="import" class="button button-primary">
                <?php esc_html_e('Import CSV', 'artopia-gallery'); ?>
            </button>
        </p>
    </form>

    <?php if ($result['submitted']) : ?>
        <hr>

        <h2><?php esc_html_e('Validation Results', 'artopia-gallery'); ?></h2>

        <?php if (!empty($result['errors'])) : ?>
            <div class="notice notice-error">
                <ul>
                    <?php foreach ($result['errors'] as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['messages'])) : ?>
            <div class="notice notice-success">
                <ul>
                    <?php foreach ($result['messages'] as $message) : ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['warnings'])) : ?>
            <div class="notice notice-warning">
                <ul>
                    <?php foreach ($result['warnings'] as $warning) : ?>
                        <li><?php echo esc_html($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($result['original_columns'])) : ?>
            <h3><?php esc_html_e('Original Columns', 'artopia-gallery'); ?></h3>
            <p><?php echo esc_html(implode(', ', $result['original_columns'])); ?></p>
        <?php endif; ?>

        <?php if (!empty($result['columns'])) : ?>
            <h3><?php esc_html_e('Normalized Columns', 'artopia-gallery'); ?></h3>
            <p><?php echo esc_html(implode(', ', $result['columns'])); ?></p>
        <?php endif; ?>

        <?php if (!empty($preview_rows)) : ?>
            <h3><?php esc_html_e('Preview (first 10 rows)', 'artopia-gallery'); ?></h3>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <?php foreach (array_keys($preview_rows[0]) as $column_name) : ?>
                            <th><?php echo esc_html($column_name); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_rows as $row) : ?>
                        <tr>
                            <?php foreach ($row as $value) : ?>
                                <td><?php echo esc_html($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($result['did_import']) : ?>
            <h3><?php esc_html_e('Import Summary', 'artopia-gallery'); ?></h3>

            <table class="widefat striped">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Created', 'artopia-gallery'); ?></th>
                        <td><?php echo esc_html((string) $result['import_summary']['created']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Skipped', 'artopia-gallery'); ?></th>
                        <td><?php echo esc_html((string) $result['import_summary']['skipped']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Matched Images', 'artopia-gallery'); ?></th>
                        <td><?php echo esc_html((string) $result['import_summary']['matched_images']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Missing Images', 'artopia-gallery'); ?></th>
                        <td><?php echo esc_html((string) $result['import_summary']['missing_images']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Gallery Term ID', 'artopia-gallery'); ?></th>
                        <td><?php echo esc_html((string) $result['import_summary']['gallery_term_id']); ?></td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($result['import_summary']['created_ids'])) : ?>
                <h4><?php esc_html_e('Created Artwork IDs', 'artopia-gallery'); ?></h4>
                <p><?php echo esc_html(implode(', ', $result['import_summary']['created_ids'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($result['import_summary']['image_messages'])) : ?>
                <h4><?php esc_html_e('Image Matching', 'artopia-gallery'); ?></h4>
                <ul>
                    <?php foreach ($result['import_summary']['image_messages'] as $image_message) : ?>
                        <li><?php echo esc_html($image_message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (!empty($result['import_summary']['skipped_rows'])) : ?>
                <h4><?php esc_html_e('Skipped Rows', 'artopia-gallery'); ?></h4>
                <ul>
                    <?php foreach ($result['import_summary']['skipped_rows'] as $skipped) : ?>
                        <li><?php echo esc_html($skipped); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>