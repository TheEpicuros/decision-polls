<?php
/**
 * Admin Menus Class
 *
 * @package Decision_Polls
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling admin menu registration.
 */
class Decision_Polls_Admin_Menus {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	/**
	 * Register admin menus.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'Decision Polls', 'decision-polls' ),
			__( 'Decision Polls', 'decision-polls' ),
			'manage_decision_polls',
			'decision-polls',
			array( 'Decision_Polls_Admin_Polls_List', 'render_page' ),
			'dashicons-chart-pie',
			25
		);

		add_submenu_page(
			'decision-polls',
			__( 'All Polls', 'decision-polls' ),
			__( 'All Polls', 'decision-polls' ),
			'manage_decision_polls',
			'decision-polls',
			array( 'Decision_Polls_Admin_Polls_List', 'render_page' )
		);

		add_submenu_page(
			'decision-polls',
			__( 'Add New Poll', 'decision-polls' ),
			__( 'Add New', 'decision-polls' ),
			'create_decision_polls',
			'decision-polls-add-new',
			array( 'Decision_Polls_Admin_Poll_Editor', 'render_page' )
		);

		add_submenu_page(
			'decision-polls',
			__( 'Settings', 'decision-polls' ),
			__( 'Settings', 'decision-polls' ),
			'manage_decision_polls',
			'decision-polls-settings',
			array( 'Decision_Polls_Admin_Settings', 'render_page' )
		);
	}
}
