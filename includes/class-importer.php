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
            'artist_id' => 0,
            'gallery_name' => '',
            'messages' => [],
            'warnings' => [],
            'errors' => [],
            'columns' => [],
            'original_columns' => [],
            'rows' => [],
            'row_count' => 0,
        ];

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

        if ($result['artist_id'] > 0) {
            $artist = get_post($result['artist_id']);

            if (!$artist || $artist->post_type !== 'artist') {
                $result['errors'][] = __('Selected artist is invalid.', 'artopia-gallery');
            }
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

    private function normalize_column_name(string $column_name): string
    {
        $normalized = strtolower(trim($column_name));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        if (isset($this->column_aliases[$normalized])) {
            return $this->column_aliases[$normalized];
        }

        return $normalized;
    }

    private function find_unknown_columns(array $columns): array
    {
        $known_columns = array_merge($this->required_columns, $this->optional_columns);

        return array_values(array_filter($columns, function ($column) use ($known_columns) {
            return !in_array($column, $known_columns, true);
        }));
    }

    private function row_is_empty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}