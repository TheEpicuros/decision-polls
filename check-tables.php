<?php
/**
 * Database Table Check Script
 *
 * Run this script to check if the plugin tables exist and recreate them if needed.
 */

// Load WordPress - find the wp-load.php file by traversing up the directory tree
$wp_load_file = '';
$path         = __DIR__;
for ( $i = 0; $i < 10; $i++ ) { // Try up to 10 levels up
	$wp_load_path = $path . '/wp-load.php';
	if ( file_exists( $wp_load_path ) ) {
		$wp_load_file = $wp_load_path;
		break;
	}
	$path = dirname( $path );
}

if ( empty( $wp_load_file ) ) {
	$wp_load_file = '../../../../../../wp-load.php'; // Try this common path as a fallback
}

require_once $wp_load_file;

// Include our install class
require_once 'includes/core/class-install.php';

echo "Starting database check...\n";

// Check if tables exist
global $wpdb;
$tables = array(
	$wpdb->prefix . 'decision_polls',
	$wpdb->prefix . 'decision_poll_answers',
	$wpdb->prefix . 'decision_poll_votes',
	$wpdb->prefix . 'decision_poll_results',
);

$tables_exist = true;
foreach ( $tables as $table ) {
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
	echo "Table $table " . ( $table_exists ? 'exists' : 'does not exist' ) . "\n";

	if ( ! $table_exists ) {
		$tables_exist = false;
	}
}

if ( ! $tables_exist ) {
	echo "Some tables are missing. Attempting to recreate...\n";

	// Activate plugin tables
	Decision_Polls_Install::create_tables();

	echo "Tables recreated. Checking again...\n";

	$all_recreated = true;
	foreach ( $tables as $table ) {
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
		echo "Table $table " . ( $table_exists ? 'now exists' : 'still does not exist' ) . "\n";

		if ( ! $table_exists ) {
			$all_recreated = false;
		}
	}

	if ( $all_recreated ) {
		echo "All tables have been successfully created.\n";
	} else {
		echo "Failed to create all tables. Please check database permissions.\n";
	}
} else {
	echo "All required tables exist.\n";
}

// Check if options have been added
$options = array(
	'decision_polls_version',
	'decision_polls_allow_guests',
	'decision_polls_results_view',
	'decision_polls_default_poll_type',
	'decision_polls_require_login_to_create',
	'decision_polls_allow_frontend_creation',
);

$options_exist = true;
foreach ( $options as $option ) {
	$option_exists = get_option( $option ) !== false;
	echo "Option $option " . ( $option_exists ? 'exists' : 'does not exist' ) . "\n";

	if ( ! $option_exists ) {
		$options_exist = false;
	}
}

if ( ! $options_exist ) {
	echo "Some options are missing. Adding default options...\n";

	// Add default options
	Decision_Polls_Install::add_options();

	echo "Options added.\n";
}

echo "Database check complete!\n";
