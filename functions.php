<?php
/**
 * Woodmart Child – Elixir Arabesc.
 */
defined( 'ABSPATH' ) || exit;

define( 'EA_MB_VERSION', '1.0.1' );
define( 'EA_MB_DIR', get_stylesheet_directory() );
define( 'EA_MB_URI', get_stylesheet_directory_uri() );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'woodmart-child-style', get_stylesheet_uri(), array(), EA_MB_VERSION );
}, 20 );

require_once EA_MB_DIR . '/inc/mystery-box.php';
