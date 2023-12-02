<?php
/**
 * Plugin Name: Was This Article Helpful? - Find.co
 * Plugin URI: https://find.co/
 * Description: A simple voting system for your articles.
 * Version: 1.0
 * Author: Mateusz Bajak
 */

if ( ! defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}

global $wpdb;
define( 'FINDCO_TABLE_NAME', $wpdb->prefix . 'simple_voting' );
define( 'FINDCO_TEXT_DOMAIN', 'findco' );

// Include autoloader for loading classes
if ( file_exists( plugin_dir_path(__FILE__) . 'vendor/autoload.php' ) ) {
	require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
} else {
	error_log( 'Autoloader file not found. Please run "composer install" to install dependencies.' );
}

/**
 * Initialize necessary classes.
 */
function findco_plugins_loaded() {
	if ( class_exists( '\App\VotingSystem' ) ) {
		$votingSystem = new \App\VotingSystem();
	} else {
		error_log( 'The VotingSystem class is not available. Please check your plugin files.' );
	}
}
add_action( 'plugins_loaded', 'findco_plugins_loaded', 10, 0 );

/**
 * Function to create the database table during plugin activation
 */
function findco_create_voting_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'simple_voting';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		post_id mediumint(9) NOT NULL,
		user_ip VARCHAR(64) NOT NULL,
		vote_option TINYINT(1) NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY unique_vote (post_id, user_ip)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta($sql);
}

// Hook the function to plugin activation
register_activation_hook( __FILE__, 'findco_create_voting_table' );
