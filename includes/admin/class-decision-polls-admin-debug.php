<?php
/**
 * Admin Debug Class
 *
 * Adds debugging tools to the WordPress admin.
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Debug class.
 */
class Decision_Polls_Admin_Debug {

	/**
	 * Initialize the class.
	 */
	public static function init() {
		// Add menu items.
		add_action( 'admin_menu', array( __CLASS__, 'add_debug_menu_items' ), 20 );
	}

	/**
	 * Add debug menu items to admin menu.
	 */
	public static function add_debug_menu_items() {
		// Add "Refresh Rules" submenu item.
		add_submenu_page(
			'decision-polls',
			__( 'Refresh Rules', 'decision-polls' ),
			__( 'Refresh Rules', 'decision-polls' ),
			'manage_options',
			'decision-polls-refresh-rules',
			array( __CLASS__, 'render_refresh_rules_page' )
		);

		// Add "Test Endpoints" submenu item.
		add_submenu_page(
			'decision-polls',
			__( 'Test Endpoints', 'decision-polls' ),
			__( 'Test Endpoints', 'decision-polls' ),
			'manage_options',
			'decision-polls-test-endpoints',
			array( __CLASS__, 'render_test_endpoints_page' )
		);
	}

	/**
	 * Render the refresh rules page.
	 */
	public static function render_refresh_rules_page() {
		require_once DECISION_POLLS_PLUGIN_DIR . 'refresh-rules.php';
	}

	/**
	 * Render the test endpoints page.
	 */
	public static function render_test_endpoints_page() {
		require_once DECISION_POLLS_PLUGIN_DIR . 'test-endpoints.php';
	}
}

// Initialize the class.
Decision_Polls_Admin_Debug::init();
