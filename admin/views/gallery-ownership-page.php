<?php
/**
 * @var array $report
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
?>

<div class="wrap">
    <h1><?php esc_html_e('Gallery Ownership', 'artopia-gallery'); ?></h1>

    <p>
        <?php esc_html_e('Review gallery terms and see which ones are artist-owned versus legacy unowned terms.', 'artopia-gallery'); ?>
    </p>

    <?php // TODO: move style to CSS ?>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($row['term_id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($row['name'] ?? '')); ?></td>
                        <td><code><?php echo esc_html((string) ($row['slug'] ?? '')); ?></code></td>
                        <td>
                            <?php
                            $artist_name = isset($row['artist_name']) ? (string) $row['artist_name'] : '';
                            echo esc_html($artist_name !== '' ? $artist_name : '—');
                            ?>
                        </td>
                        <td>
                            <?php
                            $artist_id = isset($row['artist_id']) ? (int) $row['artist_id'] : 0;
                            echo esc_html($artist_id > 0 ? (string) $artist_id : '—');
                            ?>
                        </td>
                        <td>
                            <?php
                            $is_owned = !empty($row['is_owned']);
                            echo esc_html($is_owned ? __('Owned', 'artopia-gallery') : __('Legacy / Unowned', 'artopia-gallery'));
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
