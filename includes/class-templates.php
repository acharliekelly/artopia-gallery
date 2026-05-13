<?php

namespace Artopia_Gallery;

if (!defined('ABSPATH')) {
    exit;
}

class Templates
{
    public function run(): void
    {
        add_filter('template_include', [$this, 'filter_template'], 20);
    }

    public function filter_template(string $template): string
    {
        if (is_tax('gallery')) {
            $plugin_template = $this->gallery_taxonomy_template_path();

            if ($this->template_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_singular('artwork')) {
            $plugin_template = $this->single_artwork_template_path();

            if ($this->template_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_post_type_archive('artist')) {
            $plugin_template = $this->artist_archive_template_path();

            if ($this->template_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    private function gallery_taxonomy_template_path(): string
    {
        return ARTOPIA_GALLERY_PLUGIN_PATH . 'templates/taxonomy-gallery.php';
    }

    private function single_artwork_template_path(): string
    {
        return ARTOPIA_GALLERY_PLUGIN_PATH . 'templates/single-artwork.php';
    }

    private function artist_archive_template_path(): string
    {
        return ARTOPIA_GALLERY_PLUGIN_PATH . 'templates/artist-archive.php';
    }

    private function template_exists(string $path): bool
    {
        return file_exists($path);
    }
}
