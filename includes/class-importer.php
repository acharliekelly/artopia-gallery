<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
    exit;
}

class Importer
{
    private array $required_columns = [
        'filename',
        'title',
    ];

    private array $optional_columns = [
        'medium',
        'year',
        'dimensions',
        'price',
        'status',
        'description',
    ];

    private array $column_aliases = [
        'name' => 'title',
        'artwork_name' => 'title',
        'artwork_title' => 'title',
        'file' => 'filename',
        'image' => 'filename',
        'image_filename' => 'filename',
        'dimension' => 'dimensions',
        'size' => 'dimensions',
        'artist_statement' => 'description',
        'notes' => 'description',
    ];

    public function handle_request(): array
    {
        $result = [
            'submitted' => false,
            'did_import' => false,
            'artist_id' => 0,
            'gallery_name' => '',
            'messages' => [],
            'warnings' => [],
            'errors' => [],
            'columns' => [],
            'original_columns' => [],
            'rows' => [],
            'row_count' => 0,
            'import_summary' => [
                'created' => 0,
                'skipped' => 0,
                'duplicates' => 0,
                'invalid_rows' => 0,
                'failed_creates' => 0,
                'matched_images' => 0,
                'missing_images' => 0,
                'gallery_term_id' => 0,
                'created_ids' => [],
                'skipped_rows' => [],
                'image_messages' => [],
            ],
        ];

        /** @disregard undefined variable $_SERVER */
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        if (!isset($_POST['artopia_import_nonce'])) {
            return $result;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST['artopia_import_nonce']));

        if (!wp_verify_nonce($nonce, 'artopia_import_csv')) {
            $result['errors'][] = __('Security check failed. Please try again.', 'artopia-gallery');
            return $result;
        }

        if (!current_user_can('edit_posts')) {
            $result['errors'][] = __('You do not have permission to import artwork.', 'artopia-gallery');
            return $result;
        }

        $result['submitted'] = true;
        $result['artist_id'] = isset($_POST['artopia_artist_id']) ? absint(wp_unslash($_POST['artopia_artist_id'])) : 0;
        $result['gallery_name'] = isset($_POST['artopia_gallery_name'])
            ? sanitize_text_field(wp_unslash($_POST['artopia_gallery_name']))
            : '';

        $action = isset($_POST['artopia_import_action'])
            ? sanitize_text_field(wp_unslash($_POST['artopia_import_action']))
            : 'validate';

        if ($result['artist_id'] <= 0) {
            $result['errors'][] = __('Please select an artist.', 'artopia-gallery');
        } else {
            $artist = get_post($result['artist_id']);

            if (!$artist || $artist->post_type !== 'artist') {
                $result['errors'][] = __('Selected artist is invalid.', 'artopia-gallery');
            }
        }

        if ($result['gallery_name'] === '') {
            $result['errors'][] = __('Please enter a gallery name.', 'artopia-gallery');
        }

        if (
            !isset($_FILES['artopia_csv_file']) ||
            empty($_FILES['artopia_csv_file']['tmp_name'])
        ) {
            $result['errors'][] = __('Please upload a CSV file.', 'artopia-gallery');
            return $result;
        }

        $file = $_FILES['artopia_csv_file'];

        if (!empty($file['error'])) {
            $result['errors'][] = sprintf(
                __('Upload failed with error code %d.', 'artopia-gallery'),
                (int) $file['error']
            );
            return $result;
        }

        $original_name = isset($file['name']) ? sanitize_file_name((string) $file['name']) : '';

        if (substr(strtolower($original_name), -4) !== '.csv') {
            $result['errors'][] = __('Uploaded file must be a CSV.', 'artopia-gallery');
            return $result;
        }

        $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';

        if ($tmp_name === '') {
            $result['errors'][] = __('Uploaded CSV file is missing a temporary path.', 'artopia-gallery');
            return $result;
        }

        $parsed = $this->parse_csv_file($tmp_name);

        if (!empty($parsed['errors'])) {
            $result['errors'] = array_merge($result['errors'], $parsed['errors']);
            return $result;
        }

        $result['original_columns'] = $parsed['original_columns'];
        $result['columns'] = $parsed['columns'];
        $result['rows'] = $parsed['rows'];
        $result['warnings'] = array_merge($result['warnings'], $parsed['warnings']);
        $result['row_count'] = count($parsed['rows']);

        $missing_columns = array_diff($this->required_columns, $parsed['columns']);

        if (!empty($missing_columns)) {
            $result['errors'][] = sprintf(
                __('Missing required columns: %s', 'artopia-gallery'),
                implode(', ', $missing_columns)
            );
        } else {
            $result['messages'][] = __('CSV structure looks valid.', 'artopia-gallery');
        }

        $unknown_columns = $this->find_unknown_columns($parsed['columns']);

        if (!empty($unknown_columns)) {
            $result['warnings'][] = sprintf(
                __('Unknown columns will be ignored for now: %s', 'artopia-gallery'),
                implode(', ', $unknown_columns)
            );
        }

        if ($result['row_count'] === 0) {
            $result['errors'][] = __('CSV contains no data rows.', 'artopia-gallery');
        } else {
            $result['messages'][] = sprintf(
                __('Parsed %d data row(s).', 'artopia-gallery'),
                $result['row_count']
            );
        }

        $this->validate_rows($result);

        if ($action === 'import' && empty($result['errors'])) {
            $this->import_rows($result);
        }

        return $result;
    }

    private function parse_csv_file(string $tmp_path): array
    {
        $result = [
            'original_columns' => [],
            'columns' => [],
            'rows' => [],
            'warnings' => [],
            'errors' => [],
        ];

        $handle = fopen($tmp_path, 'r');

        if ($handle === false) {
            $result['errors'][] = __('Could not open uploaded CSV file.', 'artopia-gallery');
            return $result;
        }

        $header = fgetcsv($handle);

        if (!is_array($header) || empty($header)) {
            fclose($handle);
            $result['errors'][] = __('CSV appears to be empty or missing a header row.', 'artopia-gallery');
            return $result;
        }

        $original_columns = array_map(function ($column) {
            return is_string($column) ? trim($column) : '';
        }, $header);

        $normalized_columns = [];
        $seen_columns = [];

        foreach ($original_columns as $original_column) {
            $normalized = $this->normalize_column_name($original_column);

            if ($normalized !== $original_column && $original_column !== '') {
                $result['warnings'][] = sprintf(
                    __('Normalized column "%1$s" to "%2$s".', 'artopia-gallery'),
                    $original_column,
                    $normalized
                );
            }

            if (isset($seen_columns[$normalized])) {
                $result['warnings'][] = sprintf(
                    __('Duplicate normalized column detected: %s', 'artopia-gallery'),
                    $normalized
                );
            }

            $normalized_columns[] = $normalized;
            $seen_columns[$normalized] = true;
        }

        $result['original_columns'] = $original_columns;
        $result['columns'] = $normalized_columns;

        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row) || $this->row_is_empty($row)) {
                continue;
            }

            $assoc = [];

            foreach ($normalized_columns as $index => $column_name) {
                $assoc[$column_name] = isset($row[$index]) ? trim((string) $row[$index]) : '';
            }

            $result['rows'][] = $assoc;
        }

        fclose($handle);

        return $result;
    }

    private function validate_rows(array &$result): void
    {
        // Note: parsed rows are normalized at row-processing time, not at CSV-parse time
        $allowed_statuses = array_keys(Helpers::artwork_statuses());
        $artist_id = (int) $result['artist_id'];

        foreach ($result['rows'] as $index => $row) {
            $row_number = $index + 2;
            $data = $this->normalize_import_row($row, $artist_id);

            $raw_status = isset($row['status']) ? trim((string) $row['status']) : '';
            $raw_year = isset($row['year']) ? trim((string) $row['year']) : '';

            if ($data['filename'] === '') {
                $result['errors'][] = sprintf(
                    __('Row %d: filename is required.', 'artopia-gallery'),
                    $row_number
                );
            }

            if ($data['title'] === '') {
                $result['errors'][] = sprintf(
                    __('Row %d: title is required.', 'artopia-gallery'),
                    $row_number
                );
            }

            if ($raw_status !== '' && !in_array($raw_status, $allowed_statuses, true)) {
                $result['warnings'][] = sprintf(
                    __('Row %d: status "%s" is unknown and will default to "available".', 'artopia-gallery'),
                    $row_number,
                    $raw_status
                );
            }

            /** @disregard unknown function ctype_digit */
            if ($raw_year !== '' && !ctype_digit($raw_year)) {
                $result['warnings'][] = sprintf(
                    __('Row %d: year "%s" is not a plain integer and may be sanitized.', 'artopia-gallery'),
                    $row_number,
                    $raw_year
                );
            }
        }
    }

    private function create_artwork_post(array $data)
    {
        // Imports currently create published artwork posts for faster manual testing.
        return wp_insert_post([
            'post_type' => 'artwork',
            'post_status' => 'publish',
            'post_title' => $data['title'],
            'post_content' => $data['description'],
        ], true);
    }


    private function import_rows(array &$result): void
    {
        $gallery = term_exists($result['gallery_name'], 'gallery');

        if ($gallery === 0 || $gallery === null) {
            $gallery = wp_insert_term($result['gallery_name'], 'gallery');
        }

        if (is_wp_error($gallery)) {
            $result['errors'][] = sprintf(
                __('Could not create or find gallery term: %s', 'artopia-gallery'),
                $gallery->get_error_message()
            );
            return;
        }

        $gallery_term_id = is_array($gallery) ? (int) $gallery['term_id'] : (int) $gallery;
        $result['import_summary']['gallery_term_id'] = $gallery_term_id;

        $artist_id = (int) $result['artist_id'];

        foreach ($result['rows'] as $index => $row) {
            $row_number = $index + 2;
            $data = $this->normalize_import_row($row, $artist_id);
            $import_key = $this->build_row_import_key($data);

            // Missing values
            if ($data['filename'] === '' || $data['title'] === '') {
                $result['import_summary']['skipped']++;
                $result['import_summary']['invalid_rows']++;
                $result['import_summary']['skipped_rows'][] = sprintf(
                    __('Row %d skipped due to missing required values.', 'artopia-gallery'),
                    $row_number
                );
                continue;
            }

            // Duplicate artwork
            if ($this->imported_artwork_exists($data)) {
                $result['import_summary']['skipped']++;
                $result['import_summary']['duplicates']++;
                $result['import_summary']['skipped_rows'][] = sprintf(
                    __('Row %1$d skipped because a matching imported artwork already exists for this artist.', 'artopia-gallery'),
                    $row_number
                );
                continue;
            }

            $post_id = $this->create_artwork_post($data);

            // Failed post creation
            if (is_wp_error($post_id)) {
                $result['import_summary']['skipped']++;
                $result['import_summary']['failed_creates']++;
                $result['import_summary']['skipped_rows'][] = sprintf(
                    __('Row %1$d failed to import: %2$s', 'artopia-gallery'),
                    $row_number,
                    $post_id->get_error_message()
                );
                continue;
            }

            update_post_meta($post_id, '_artopia_artist_id', $data['artist_id']);
            update_post_meta($post_id, '_artopia_filename', $data['filename']);
            update_post_meta($post_id, '_artopia_medium', $data['medium']);
            update_post_meta($post_id, '_artopia_year', $data['year']);
            update_post_meta($post_id, '_artopia_dimensions', $data['dimensions']);
            update_post_meta($post_id, '_artopia_price', $data['price']);
            update_post_meta($post_id, '_artopia_status', $data['status']);
            update_post_meta($post_id, '_artopia_import_key', $import_key);

            wp_set_object_terms($post_id, [$gallery_term_id], 'gallery');

            $attachment_id = $this->find_attachment_by_filename($data['filename']);

            if ($attachment_id > 0) {
                set_post_thumbnail($post_id, $attachment_id);

                wp_update_post([
                    'ID' => $attachment_id,
                    'post_parent' => $post_id,
                ]);

                $result['import_summary']['matched_images']++;
                $result['import_summary']['image_messages'][] = sprintf(
                    __('Row %1$d: matched image "%2$s" to attachment ID %3$d.', 'artopia-gallery'),
                    $row_number,
                    $data['filename'],
                    $attachment_id
                );
            } else {
                $result['import_summary']['missing_images']++;
                $result['import_summary']['image_messages'][] = sprintf(
                    __('Row %1$d: no Media Library image found for "%2$s".', 'artopia-gallery'),
                    $row_number,
                    $data['filename']
                );
            }

            $result['import_summary']['created']++;
            $result['import_summary']['created_ids'][] = $post_id;
        }

        $result['did_import'] = true;
        $result['messages'][] = sprintf(
            __('Import complete. Created %1$d artwork(s), skipped %2$d row(s).', 'artopia-gallery'),
            $result['import_summary']['created'],
            $result['import_summary']['skipped']
        );

    }

    protected function normalize_import_row(array $row, int $artist_id): array
    {
        return Artwork_Data::normalize([
            'artist_id' => $artist_id,
            'title' => $row['title'] ?? '',
            'filename' => $row['filename'] ?? '',
            'medium' => $row['medium'] ?? '',
            'year' => $row['year'] ?? '',
            'dimensions' => $row['dimensions'] ?? '',
            'price' => $row['price'] ?? '',
            'status' => $row['status'] ?? '',
            'description' => $row['description'] ?? '',
        ]);
    }

    protected function build_row_import_key(array $data): string
    {
        return Artwork_Data::build_import_key($data);
    }

    private function imported_artwork_exists(array $data): bool
    {
        $import_key = $this->build_row_import_key($data);

        if ($this->artwork_exists_by_import_key($import_key)) {
            return true;
        }

        return $this->artwork_exists_by_artist_filename(
            (int) $data['artist_id'],
            (string) $data['filename']
        );
    }

    private function artwork_exists_by_import_key(string $import_key): bool
    {
        if ($import_key === '') {
            return false;
        }

        $query = new \WP_Query([
            'post_type' => 'artwork',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => '_artopia_import_key',
                    'value' => $import_key,
                    'compare' => '=',
                ],
            ],
        ]);

        return $query->have_posts();
    }

    private function artwork_exists_by_artist_filename(int $artist_id, string $filename): bool
    {
        $query = new \WP_Query([
            'post_type' => 'artwork',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_artopia_artist_id',
                    'value' => $artist_id,
                    'compare' => '=',
                ],
                [
                    'key' => '_artopia_filename',
                    'value' => $filename,
                    'compare' => '=',
                ],
            ],
        ]);

        return $query->have_posts();
    }

    private function find_attachment_by_filename(string $filename): int
    {
        $filename = sanitize_file_name($filename);

        if ($filename === '') {
            return 0;
        }

        $candidates = $this->build_attachment_filename_candidates($filename);
        $pathinfo = pathinfo($filename);
        $basename = isset($pathinfo['filename']) ? $pathinfo['filename'] : '';
        if ($basename === '') {
            return 0;
        }

        $query = new \WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                    [
                        'key' => '_wp_attached_file',
                        'value' => $basename,
                        'compare' => 'LIKE',
                    ],
                ],
        ]);

        if (empty($query->posts)) {
            return 0;
        }

        // Prefer an exact original filename match first, then the scaled fallback.
        foreach ($candidates as $candidate) {
            foreach ($query->posts as $attachment_id) {
                $attached_file = get_post_meta((int) $attachment_id, '_wp_attached_file', true);

                if (!is_string($attached_file) || $attached_file === '') {
                    continue;
                }

                if (wp_basename($attached_file) === $candidate) {
                    return (int) $attachment_id;
                }
            }
        }

        return 0;
    }

    private function build_attachment_filename_candidates(string $filename): array {
        $candidates = [$filename];
        $pathinfo = pathinfo($filename);
        $basename = isset($pathinfo['filename']) ? $pathinfo['filename'] : '';
        $extension = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        if ($basename !== '' && $extension !== '') {
            $candidates[] = $basename . '-scaled.' . $extension;
        }

        return array_values(array_unique($candidates));
    }

    protected function normalize_column_name(string $column_name): string
    {
        $normalized = strtolower(trim($column_name));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        if (isset($this->column_aliases[$normalized])) {
            return $this->column_aliases[$normalized];
        }

        return $normalized;
    }

    protected function find_unknown_columns(array $columns): array
    {
        $known_columns = array_merge(
            $this->required_columns,
            $this->optional_columns
        );

        return array_values(array_filter($columns, function ($column) use ($known_columns) {
            return !in_array($column, $known_columns, true);
        }));
    }

    protected function row_is_empty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
