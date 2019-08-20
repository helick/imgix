<?php
/*
Plugin Name:    Helick Imgix
Author:         Evgenii Nasyrov
Author URI:     https://helick.io/
*/

// Require Composer autoloader if installed on it's own
if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

// Constants & helpers
require_once __DIR__ . '/src/constants.php';
require_once __DIR__ . '/src/helpers.php';

// Services
\Helick\Imgix\Content::boot();
