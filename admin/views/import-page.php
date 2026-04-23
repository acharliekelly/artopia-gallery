<?php
/**
 * @var array $result
 */

if (!defined('ABSPATH')) {
  exit;
}

$artists = get_posts([
  'post_type'       => 'artist',
  'post_status'     => ['publish', 'draft', 'pending', 'private'],
  'posts_per_page'  => -1,
  'orderby'         => 'title',
  'order'           => 'ASC',
]);

$preview_rows = array_slice($result['rows'], 0, 10);
?>

<div class="wrap">
  <h1><?php esc_html_e('Import Artwork CSV', 'artopia-gallery'); ?></h1>
  <p>
    <?php esc_html_e('This first version only validates and previews a CSV. It does not create artwork yet.', 'artopia-gallery'); ?>
  </p>

  <form method="post" enctype="multipart/form-data">
    <?php wp_nonce_field('artopia-import-csv', 'artopia_import_nonce'); ?>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="artopia_artist_id"><?php esc_html_e('Artist', 'artopia-gallery'); ?></label>
          </th>
          <td>
            <select name="artopia_artist_id" id="artopia_artist_id">
              <option value="0"><?php esc_html_e('Select an artist'); ?></option>
              <?php foreach ($artists as $artist) : ?>
                <option value="<?php esc_attr((string) $artist->ID); ?>" <?php selected($result['artist_id'], $artist->ID); ?>>
                  <?php echo esc_html($artist->post_title ?: sprintf(__('Artist #%d', 'artopia-gallery'), $artist->ID)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="artopia_gallery_name">
              <?php esc_html_e('Gallery Name', 'artopia-gallery'); ?>
            </label>
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
            <label for="artopia_csv_file">
              <?php esc_html_e('CSV File', 'artopia-gallery'); ?>
            </label>
          </th>
          <td>
            <input type="file" name="artopia_csv_file" id="artopia_csv_file" accept=".csv,text/csv" />
            <p class="description">
              <?php esc_html_e('Required columns for now: filename, title', 'artopia-gallery'); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button(__('Validate CSV', 'artopia-gallery')); ?>
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

    <?php if (!empty($result['columns'])) : ?>
      <h3><?php esc_html_e('Detected Columns', 'artopia-gallery'); ?></h3>
      <p><?php esc_html(implode(', ', $result['columns'])); ?></p>
    <?php endif; ?>

    <?php if (!empty($preview_rows)) : ?>
      <h3><?php esc_html_e('Preview (first 10 rows)', 'artopia-gallery'); ?></h3>

      <table>
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

  <?php endif; ?>

</div>