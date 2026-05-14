<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/*
 * Artopia Gallery uses a non-destructive uninstall policy.
 *
 * The plugin leaves artists, artworks, gallery terms, and related post/term meta
 * in place when uninstalled. These records are treated as site content and must
 * not be removed automatically.
 */
