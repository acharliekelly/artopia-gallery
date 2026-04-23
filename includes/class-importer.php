<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
  exit;
}

class Importer {
  private array $required_columns = [
    'filename',
    'title',
  ];

  public function handle_request(): array {
    $result = [
      'submitted' => false,
      'artist_id' => 0,
      'gallery_name' => '',
      'messages' => [],
      'errors' => [],
      'columns' => [],
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
    $result['artist_id'] = isset($_POST['artopia_artist_id']) ?absint(wp_unslash($_POST['artopia_artist_id'])) : 0;
    $result['gallery_name'] = isset($_POST['artopia_gallery_name']) ? sanitize_text_field(wp_unslash($_POST['artopia_gallery_name'])) : '';

    if (empty($_FILES['artopia_csv_file']['tmp_name'])) {
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

    $original_name = isset($file['name']) ? sanitize_file_name(wp_unslash($file['name'])) : '';

    if (!str_ends_with(strtolower($original_name), '.csv')) {
      $result['errors'][] = __('Uploaded file must be a CSV.', 'artopia-gallery');
      return $result;
    }

    $parsed = $this->parse_csv_file($file['tmp_name']);

    if (!empty($parsed['errors'])) {
      $result['errors'] = array_merge($result['errors'], $parsed['errors']);
      return $result;
    }

    $result['columns'] = $parsed['columns'];
    $result['rows'] = $parsed['rows'];
    $result['row_count'] = $parsed['row_count'];

    $missing_columns = array_diff($this->required_columns, $parsed['columns']);

    if (!empty($missing_columns)) {
      $result['errors'][] = sprintf(
        __('Missing required columns: %s', 'artopia-gallery'),
        implode(', ', $missing_columns)
      );
    } else {
      $result['messages'][] = __('CSV structure looks valid.', 'artopia-gallery');
      return $result;
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

  private function parse_csv_file(string $tmp_path): array {
    $result = [
      'columns' => [],
      'rows' => [],
      'errors' => [],
    ];

    $handle = fopen($tmp_path, 'r');

    if ($handle === false) {
      fclose($handle);
      $result['errors'][] = __('Could not open unloaded CSV file.', 'artopia-gallery');
      return $result;
    }

    $columns = array_map(function ($column) {
      $column = is_string($column) ? trim($column) : '';
      return strtolower($column);
    }, $$handle);

    $result['columns'] = $columns;

    while (($row = fgetcsv($handle)) !== false) {
      if ($this->row_is_empty($row)) {
        continue;
      }

      $assoc = [];

      foreach ($columns as $index => $column_name) {
        $assoc[$column_name] = isset($row[$index]) ? trim((string) $row[$index]) : '';
      }

      $result['rows'][] = $assoc;
    }

    fclose($handle);

    return $result;
  }

  private function row_is_empty(array $row): bool {
    foreach ($row as $value) {
      if (trim((string) $value) !== '') {
        return false;
      }
    }
    return true;
  }

    
}