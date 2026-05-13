# Artopia Gallery

Custom WordPress plugin for managing artist portfolios, importing artwork catalogs, and powering gallery websites for Artopia Giclée clients.

## Overview

Artopia Gallery is a purpose-built WordPress plugin designed to streamline the process of turning artist inventory data into polished web galleries.

Many artists maintain artwork records in spreadsheets or Lightroom exports but have little interest in building or maintaining websites. This plugin bridges that gap by allowing administrators to bulk import artwork data, organize artists and galleries, and manage collections inside WordPress.

## Current Features

### Custom Content Model

- **Artists** custom post type
- **Artworks** custom post type
- **Gallery** taxonomy for organizing artist-scoped collections

### Artwork Metadata

Each artwork supports:

- Artist relationship
- Filename
- Medium
- Year
- Dimensions
- Price
- Status

### CSV Importer

Admin import workflow includes:

- Select Artist
- Enter Gallery Name
- Upload CSV file
- Validate before import
- Header normalization / alias handling
- Required field enforcement
- Duplicate prevention for repeated imports using a normalized import identity, with legacy fallback checks for older imported records
- Artist-scoped gallery lookup and creation
- Legacy unowned gallery adoption when a matching gallery is reused during import
- Bulk creation of Artwork posts
- Import summary reporting

Gallery terms are treated as artist-owned collections by the plugin. Two artists may use the same gallery name without sharing the same logical gallery record.

## Shortcode behavior

The preferred shortcode pattern for gallery-specific output is:

```text
[artopia-gallery gallery="landscapes" artist_id="123"]
```

When both `gallery` and `artist_id` are provided, the plugin resolves the gallery in artist context. Using `gallery` alone remains supported for backward compatibility, but can be ambiguous if multiple artists reuse the same gallery name.

## Public templates

The plugin now manages its own public templates for:

- artist archive pages
- gallery taxonomy pages
- single artwork pages

Template routing is handled inside the plugin, and gallery taxonomy pages reuse the same shared gallery rendering path used by the shortcode. The shortcode remains available and is still the preferred portable way to embed gallery output inside other content.

Future enhancement:

- Make import status selectable on the import page, such as publish vs draft

### Supported CSV Columns

#### Required

- `filename`
- `title`

#### Optional

- `medium`
- `year`
- `dimensions`
- `price`
- `status`
- `description`

#### Supported Aliases

Examples:

- `dimension` → `dimensions`
- `size` → `dimensions`
- `name` → `title`
- `image` → `filename`

## Example CSV

```csv
filename,title,medium,year,dimensions,price,status
marsh-sunset.jpg,Marsh Sunset,Oil on Canvas,2024,24x36,1800,available
cat-study.jpg,Cat Study,Pastel,2025,12x16,450,sold
```

## Development

Install dev dependencies from the plugin directory:

```bash
cd wp-content/plugins/artopia-gallery
composer install
```

Run unit tests:

```bash
composer test
```

## Testing

Unit tests live in `tests/Unit`.

Manual importer regression fixtures live in `fixtures/`:

- `import_min.csv`: minimal valid import
- `import_default.csv`: standard happy-path import with real image filenames
- `import_bad.csv`: invalid rows and warning scenarios
- `import_abnormal.csv`: normalization and alias-header edge cases
- `import_duplicates.csv`: repeat-import duplicate checks
- `import_same_filename_diff_title.csv`: same filename, different title duplicate behavior

CI runs PHPUnit and PHP syntax linting through GitHub Actions in `.github/workflows/phpunit.yml`.
