<?php
/**
 * Bootstrap for PHPUnit tests.
 */

// Define WordPress constants for testing environment
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'BIHRWI_PLUGIN_DIR', ABSPATH );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Load plugin files for testing
require_once BIHRWI_PLUGIN_DIR . 'includes/class-bihr-logger.php';
