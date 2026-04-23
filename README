# Artopia Gallery

Custom WordPress plugin for managing artist portfolios, importing artwork catalogs, and powering gallery websites for Artopia Giclée clients.

## Overview

Artopia Gallery is a purpose-built WordPress plugin designed to streamline the process of turning artist inventory data into polished web galleries.

Many artists maintain artwork records in spreadsheets or Lightroom exports but have little interest in building or maintaining websites. This plugin bridges that gap by allowing administrators to bulk import artwork data, organize artists and galleries, and manage collections inside WordPress.

## Current Features

### Custom Content Model

- **Artists** custom post type
- **Artworks** custom post type
- **Gallery** taxonomy for organizing collections

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
- Duplicate prevention by filename + artist
- Bulk creation of Artwork posts
- Import summary reporting

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